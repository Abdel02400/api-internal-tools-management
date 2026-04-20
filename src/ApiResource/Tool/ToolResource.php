<?php

namespace App\ApiResource\Tool;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\ApiResource\QueryParameter\EnumQueryParameter;
use App\ApiResource\QueryParameter\PositiveIntegerQueryParameter;
use App\ApiResource\QueryParameter\PositiveNumberQueryParameter;
use App\ApiResource\QueryParameter\StringQueryParameter;
use App\Dto\Tool\Input\CreateToolInput;
use App\Dto\Tool\Input\UpdateToolInput;
use App\Dto\Tool\Output\ToolCollectionOutput;
use App\Dto\Tool\Output\ToolDetailOutput;
use App\Dto\Tool\Output\ToolWriteOutput;
use App\Dto\Tool\Query\ListToolsQuery;
use App\Entity\Tool;
use App\Enum\Department;
use App\Enum\SortBy;
use App\Enum\SortOrder;
use App\Enum\ToolStatus;
use App\State\Processor\ToolPersistProcessor;
use App\State\Processor\ToolUpdateProcessor;
use App\State\Provider\ToolCollectionProvider;
use App\State\Provider\ToolItemProvider;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

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
                self::PARAM_PAGE => new PositiveIntegerQueryParameter(),
                self::PARAM_LIMIT => new PositiveIntegerQueryParameter(maximum: ListToolsQuery::MAX_LIMIT),
                self::PARAM_SORT_BY => new EnumQueryParameter(SortBy::VALUES),
                self::PARAM_ORDER => new EnumQueryParameter(SortOrder::VALUES),
            ],
            provider: ToolCollectionProvider::class,
            output: ToolCollectionOutput::class,
            paginationEnabled: false,
        ),
        new Get(
            uriTemplate: self::URI_ITEM,
            provider: ToolItemProvider::class,
            output: ToolDetailOutput::class,
        ),
        new Post(
            uriTemplate: self::URI_BASE,
            input: CreateToolInput::class,
            processor: ToolPersistProcessor::class,
            output: ToolWriteOutput::class,
            denormalizationContext: [
                AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
                DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
            ],
        ),
        new Put(
            uriTemplate: self::URI_ITEM,
            read: false,
            input: UpdateToolInput::class,
            processor: ToolUpdateProcessor::class,
            output: ToolWriteOutput::class,
            denormalizationContext: [
                AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
                DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true,
            ],
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
    public const PARAM_PAGE = 'page';
    public const PARAM_LIMIT = 'limit';
    public const PARAM_SORT_BY = 'sort_by';
    public const PARAM_ORDER = 'order';
}
