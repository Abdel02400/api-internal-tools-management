<?php

namespace App\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponse
{
    public const ERROR_RESOURCE_NOT_FOUND = 'Resource not found';
    public const ERROR_INTERNAL = 'Internal server error';
    public const ERROR_VALIDATION_FAILED = 'Validation failed';
    public const ERROR_INVALID_ANALYTICS_PARAMETER = 'Invalid analytics parameter';

    public const MESSAGE_RESOURCE_NOT_FOUND = 'The requested resource could not be found';
    public const MESSAGE_INTERNAL_ERROR = 'Internal server error occurred';
    public const MESSAGE_DATABASE_CONNECTION_FAILED = 'Database connection failed';

    private function __construct()
    {
    }

    public static function notFound(string $error, string $message): JsonResponse
    {
        return self::build(Response::HTTP_NOT_FOUND, $error, message: $message);
    }

    public static function resourceNotFound(?string $message = null): JsonResponse
    {
        return self::notFound(
            self::ERROR_RESOURCE_NOT_FOUND,
            $message ?? self::MESSAGE_RESOURCE_NOT_FOUND,
        );
    }

    /**
     * @param array<string, string> $details
     */
    public static function validationFailed(array $details): JsonResponse
    {
        return self::build(Response::HTTP_BAD_REQUEST, self::ERROR_VALIDATION_FAILED, details: $details);
    }

    /**
     * @param array<string, string> $details
     */
    public static function invalidAnalyticsParameter(array $details): JsonResponse
    {
        return self::build(Response::HTTP_BAD_REQUEST, self::ERROR_INVALID_ANALYTICS_PARAMETER, details: $details);
    }

    public static function internalError(string $message): JsonResponse
    {
        return self::build(Response::HTTP_INTERNAL_SERVER_ERROR, self::ERROR_INTERNAL, message: $message);
    }

    /**
     * @param array<string, string>|null $details
     */
    private static function build(
        int $httpStatus,
        string $error,
        ?string $message = null,
        ?array $details = null,
    ): JsonResponse {
        $body = ['error' => $error];

        if ($message !== null) {
            $body['message'] = $message;
        }

        if ($details !== null) {
            $body['details'] = $details;
        }

        return new JsonResponse($body, $httpStatus);
    }
}
