<?php

namespace App\EventSubscriber;

use ApiPlatform\Validator\Exception\ConstraintViolationListAwareExceptionInterface;
use App\ApiResource\Tool\ToolResource;
use App\Exception\Http\ToolNotFoundException;
use App\Http\ApiResponse;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    /**
     * URIs (relatives au prefix API) dont les exceptions sont normalisées ici.
     * Chaque URI correspond à la base d'une ressource (les sous-paths type /{id} sont couverts par str_starts_with).
     */
    private const HANDLED_RESOURCE_URIS = [
        ToolResource::URI_BASE,
    ];

    /**
     * Priorité du listener sur kernel.exception.
     * Doit rester > à celle d'API Platform (-96) pour intercepter avant son listener natif.
     */
    private const LISTENER_PRIORITY = 10;

    private NameConverterInterface $nameConverter;

    public function __construct(
        #[Autowire('%api_prefix%')]
        private readonly string $apiPrefix,
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
        if (!$this->handles($event->getRequest())) {
            return;
        }

        $event->setResponse($this->buildResponse($event->getThrowable()));
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

    private function buildResponse(Throwable $exception): JsonResponse
    {
        $violations = $this->extractViolations($exception);
        if ($violations !== null) {
            return $this->validationResponse($violations);
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

        return ApiResponse::internalError($exception->getMessage());
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

    private function validationResponse(ConstraintViolationListInterface $violations): JsonResponse
    {
        $details = [];
        foreach ($violations as $violation) {
            $propertyPath = $this->nameConverter->normalize($violation->getPropertyPath());
            $details[$propertyPath] = (string) $violation->getMessage();
        }

        return ApiResponse::validationFailed($details);
    }
}
