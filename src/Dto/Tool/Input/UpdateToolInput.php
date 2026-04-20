<?php

namespace App\Dto\Tool\Input;

use App\Entity\Tool;
use App\Enum\Department;
use App\Enum\ToolStatus;
use App\Validator\Constraint\ExistingCategory;
use App\Validator\Message\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateToolInput
{
    public function __construct(
        #[Assert\Length(
            min: Tool::MIN_NAME_LENGTH,
            max: Tool::MAX_NAME_LENGTH,
            minMessage: ValidationMessage::VALUE_TOO_SHORT,
            maxMessage: ValidationMessage::VALUE_TOO_LONG,
        )]
        public ?string $name = null,

        #[Assert\Positive(message: ValidationMessage::MUST_BE_POSITIVE)]
        #[ExistingCategory]
        public ?int $categoryId = null,

        #[Assert\GreaterThanOrEqual(value: 0, message: ValidationMessage::MUST_BE_POSITIVE_OR_ZERO)]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: ValidationMessage::TOO_MANY_DECIMALS)]
        public ?float $monthlyCost = null,

        public ?Department $ownerDepartment = null,

        public ?ToolStatus $status = null,

        #[Assert\Length(
            max: Tool::MAX_VENDOR_LENGTH,
            maxMessage: ValidationMessage::VALUE_TOO_LONG,
        )]
        public ?string $vendor = null,

        public ?string $description = null,

        #[Assert\Url(message: ValidationMessage::INVALID_URL)]
        #[Assert\Length(
            max: Tool::MAX_URL_LENGTH,
            maxMessage: ValidationMessage::VALUE_TOO_LONG,
        )]
        public ?string $websiteUrl = null,
    ) {
    }
}
