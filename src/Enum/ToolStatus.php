<?php

namespace App\Enum;

enum ToolStatus: string
{
    case Active = 'active';
    case Deprecated = 'deprecated';
    case Trial = 'trial';
}
