<?php

namespace App\EventSubscriber;

use ApiPlatform\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use App\ApiResource\Tool\ToolResource;
use App\Exception\Http\ToolNotFoundException;
use App\Http\ApiResponse;
use App\Validator\Message\ValidationMessage;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * URIs (relatives au prefix API) dont les exceptions sont normalisées ici.
     * Chaque URI correspond à la base d'une ressource (les sous-paths type /{id} sont couverts par str_starts_with).
     */
    private const ANALYTICS_PREFIX = '/analytics';

    private const HANDLED_RESOURCE_URIS = [
        ToolResource::URI_BASE,
        self::ANALYTICS_PREFIX,
    ];

    /**
     * Priorité du listener sur kernel.exception.
     * Doit rester > à celle d'API Platform (-96) pour intercepter avant son listener natif.
     */
    private const LISTENER_PRIORITY = 10;

    private const BODY_FIELD = 'body';

    private NameConverterInterface $nameConverter;

    public function __construct(
        #[Autowire('%api_prefix%')]
        private readonly string $apiPrefix,
        #[Autowire('%kernel.debug%')]
        private readonly bool $isDebug = false,
    ) {
        $this->nameConverter = new CamelCaseToSnakeCaseNameConverter();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', self::LISTENER_PRIORITY],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->handles($request)) {
            return;
        }

        $event->setResponse($this->buildResponse($event->getThrowable(), $this->isAnalyticsPath($request)));
    }

    private function handles(Request $request): bool
    {
        $path = $request->getPathInfo();

        foreach (self::HANDLED_RESOURCE_URIS as $uri) {
            if (str_starts_with($path, $this->apiPrefix . $uri)) {
                return true;
            }
        }

        return false;
    }

    private function isAnalyticsPath(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), $this->apiPrefix . self::ANALYTICS_PREFIX);
    }

    private function buildResponse(Throwable $exception, bool $analytics): JsonResponse
    {
        $violations = $this->extractViolations($exception);
        if ($violations !== null) {
            return $this->validationResponse($violations, $analytics);
        }

        $deserializationDetails = $this->extractDeserializationDetails($exception);
        if ($deserializationDetails !== null) {
            return $analytics
                ? ApiResponse::invalidAnalyticsParameter($deserializationDetails)
                : ApiResponse::validationFailed($deserializationDetails);
        }

        if ($exception instanceof ToolNotFoundException) {
            return ApiResponse::notFound(
                ToolNotFoundException::ERROR_TITLE,
                $exception->getMessage(),
            );
        }

        if ($exception instanceof NotFoundHttpException) {
            return ApiResponse::resourceNotFound($exception->getMessage() ?: null);
        }

        if ($exception instanceof DbalException) {
            return ApiResponse::internalError(ApiResponse::MESSAGE_DATABASE_CONNECTION_FAILED);
        }

        return ApiResponse::internalError(
            $this->isDebug ? $exception->getMessage() : ApiResponse::MESSAGE_INTERNAL_ERROR,
        );
    }

    private function extractViolations(Throwable $exception): ?ConstraintViolationListInterface
    {
        if ($exception instanceof ValidationFailedException) {
            return $exception->getViolations();
        }

        if ($exception instanceof ConstraintViolationListAwareExceptionInterface) {
            return $exception->getConstraintViolationList();
        }

        return null;
    }

    /**
     * @return array<string, string>|null
     */
    private function extractDeserializationDetails(Throwable $exception): ?array
    {
        if ($exception instanceof NotEncodableValueException) {
            return [self::BODY_FIELD => ValidationMessage::MALFORMED_JSON];
        }

        if ($exception instanceof ExtraAttributesException) {
            $details = [];
            foreach ($exception->getExtraAttributes() as $attribute) {
                $details[$this->nameConverter->normalize($attribute)] = ValidationMessage::UNKNOWN_FIELD;
            }
            return $details;
        }

        if ($exception instanceof MissingConstructorArgumentsException) {
            $details = [];
            foreach ($exception->getMissingConstructorArguments() as $argument) {
                $details[$this->nameConverter->normalize($argument)] = ValidationMessage::FIELD_REQUIRED;
            }
            return $details;
        }

        if ($exception instanceof PartialDenormalizationException) {
            $details = [];
            foreach ($exception->getErrors() as $error) {
                $path = $error->getPath() ?? self::BODY_FIELD;
                $details[$this->nameConverter->normalize($path)] = ValidationMessage::INVALID_VALUE;
            }
            return $details;
        }

        return null;
    }

    private function validationResponse(ConstraintViolationListInterface $violations, bool $analytics): JsonResponse
    {
        $details = [];
        foreach ($violations as $violation) {
            $propertyPath = $this->nameConverter->normalize($violation->getPropertyPath());
            $details[$propertyPath] = $analytics
                ? $this->analyticsMessage($propertyPath)
                : $this->violationMessage($violation);
        }

        return $analytics
            ? ApiResponse::invalidAnalyticsParameter($details)
            : ApiResponse::validationFailed($details);
    }

    /**
     * Messages normalisés par nom de paramètre pour les endpoints Analytics (spec Part 2).
     * Les messages d'origine (AP schema ou Asserts) sont remplacés pour coller au format du spec
     * et éviter de leaker des détails d'implémentation.
     */
    private function analyticsMessage(string $field): string
    {
        return match ($field) {
            'limit' => ValidationMessage::ANALYTICS_LIMIT,
            'min_cost', 'max_cost' => ValidationMessage::ANALYTICS_POSITIVE_NUMBER,
            'max_users' => ValidationMessage::ANALYTICS_POSITIVE_INTEGER,
            default => ValidationMessage::INVALID_VALUE,
        };
    }

    /**
     * Asserts violations have a non-null root (the DTO being validated) and keep their raw message.
     * Denormalization errors converted by API Platform into violations have root=null and carry the
     * raw Symfony type message that may leak internal class names — replaced by a generic message.
     */
    private function violationMessage(ConstraintViolationInterface $violation): string
    {
        if ($violation->getRoot() === null) {
            return ValidationMessage::INVALID_VALUE;
        }

        return (string) $violation->getMessage();
    }
}
