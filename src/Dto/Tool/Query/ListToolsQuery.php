<?php

namespace App\Dto\Tool\Query;

use App\Entity\Category;
use App\Validator\Message\ValidationMessage;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListToolsQuery
{
    public function __construct(
        // department and status are validated upstream by API Platform's schema
        // validator via the `enum` declared on their QueryParameter in ToolResource.
        public ?string $department = null,

        public ?string $status = null,

        #[Assert\Type(type: 'numeric', message: ValidationMessage::MUST_BE_NUMBER)]
        #[Assert\PositiveOrZero(message: ValidationMessage::MUST_BE_POSITIVE_OR_ZERO)]
        public ?string $minCost = null,

        #[Assert\Type(type: 'numeric', message: ValidationMessage::MUST_BE_NUMBER)]
        #[Assert\PositiveOrZero(message: ValidationMessage::MUST_BE_POSITIVE_OR_ZERO)]
        public ?string $maxCost = null,

        #[Assert\Length(max: Category::MAX_NAME_LENGTH, maxMessage: ValidationMessage::VALUE_TOO_LONG)]
        public ?string $category = null,
    ) {
    }
}
