<?php

namespace App\Exception\Http;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ToolNotFoundException extends NotFoundHttpException
{
    public const ERROR_TITLE = 'Tool not found';
    private const MESSAGE_TEMPLATE = 'Tool with ID %d does not exist';

    public static function withId(int $id): self
    {
        return new self(sprintf(self::MESSAGE_TEMPLATE, $id));
    }
}
