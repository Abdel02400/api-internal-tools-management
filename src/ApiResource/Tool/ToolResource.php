<?php

namespace App\ApiResource\Tool;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\ApiResource\QueryParameter\EnumQueryParameter;
use App\ApiResource\QueryParameter\PositiveNumberQueryParameter;
use App\ApiResource\QueryParameter\StringQueryParameter;
use App\Dto\Tool\Output\ToolCollectionOutput;
use App\Dto\Tool\Output\ToolDetailOutput;
use App\Entity\Tool;
use App\Enum\Department;
use App\Enum\ToolStatus;
use App\State\Provider\ToolCollectionProvider;
use App\State\Provider\ToolItemProvider;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

#[ApiResource(
    class: Tool::class,
    shortName: self::SHORT_NAME,
    formats: [JsonEncoder::FORMAT],
    operations: [
        new GetCollection(
            uriTemplate: self::URI_BASE,
            parameters: [
                self::PARAM_DEPARTMENT => new EnumQueryParameter(Department::VALUES),
                self::PARAM_STATUS => new EnumQueryParameter(ToolStatus::VALUES),
                self::PARAM_MIN_COST => new PositiveNumberQueryParameter(),
                self::PARAM_MAX_COST => new PositiveNumberQueryParameter(),
                self::PARAM_CATEGORY => new StringQueryParameter(),
            ],
            provider: ToolCollectionProvider::class,
            output: ToolCollectionOutput::class,
            paginationEnabled: false,
        ),
        new Get(
            uriTemplate: self::URI_ITEM,
            requirements: [self::ID_PARAM => Requirement::POSITIVE_INT],
            provider: ToolItemProvider::class,
            output: ToolDetailOutput::class,
        ),
    ],
)]
final class ToolResource
{
    public const SHORT_NAME = 'Tool';
    public const ID_PARAM = 'id';
    public const URI_BASE = '/' . Tool::TABLE_NAME;
    public const URI_ITEM = self::URI_BASE . '/{' . self::ID_PARAM . '}';

    public const PARAM_DEPARTMENT = 'department';
    public const PARAM_STATUS = 'status';
    public const PARAM_MIN_COST = 'min_cost';
    public const PARAM_MAX_COST = 'max_cost';
    public const PARAM_CATEGORY = 'category';
}
