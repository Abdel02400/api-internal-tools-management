<?php

namespace App\Entity;

use App\Enum\Department;
use App\Enum\ToolStatus;
use App\Repository\ToolRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ToolRepository::class)]
#[ORM\Table(name: 'tools')]
#[ORM\Index(name: 'idx_tools_category', columns: ['category_id'])]
#[ORM\Index(name: 'idx_tools_department', columns: ['owner_department'])]
#[ORM\Index(name: 'idx_tools_cost_desc', columns: ['monthly_cost'])]
#[ORM\Index(name: 'idx_tools_status', columns: ['status'])]
#[ORM\Index(name: 'idx_tools_active_users', columns: ['active_users_count'])]
#[ORM\HasLifecycleCallbacks]
final class Tool
{
    public const int MIN_NAME_LENGTH = 2;
    public const int MAX_NAME_LENGTH = 100;
    public const int MAX_VENDOR_LENGTH = 100;
    public const int MAX_URL_LENGTH = 255;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: self::MAX_NAME_LENGTH)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: self::MAX_VENDOR_LENGTH, nullable: true)]
    private ?string $vendor = null;

    #[ORM\Column(length: self::MAX_URL_LENGTH, nullable: true)]
    private ?string $websiteUrl = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(name: 'category_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private Category $category;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $monthlyCost;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $activeUsersCount = 0;

    #[ORM\Column(type: Types::ENUM, enumType: Department::class)]
    private Department $ownerDepartment;

    #[ORM\Column(type: Types::ENUM, nullable: true, options: ['default' => 'active'], enumType: ToolStatus::class)]
    private ?ToolStatus $status = ToolStatus::Active;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        string $name,
        Category $category,
        string $monthlyCost,
        Department $ownerDepartment,
    ) {
        $this->name = $name;
        $this->category = $category;
        $this->monthlyCost = $monthlyCost;
        $this->ownerDepartment = $ownerDepartment;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Tool
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Tool
    {
        $this->description = $description;
        return $this;
    }

    public function getVendor(): ?string
    {
        return $this->vendor;
    }

    public function setVendor(?string $vendor): Tool
    {
        $this->vendor = $vendor;
        return $this;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(?string $websiteUrl): Tool
    {
        $this->websiteUrl = $websiteUrl;
        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): Tool
    {
        $this->category = $category;
        return $this;
    }

    public function getMonthlyCost(): string
    {
        return $this->monthlyCost;
    }

    public function setMonthlyCost(string $monthlyCost): Tool
    {
        $this->monthlyCost = $monthlyCost;
        return $this;
    }

    public function getActiveUsersCount(): int
    {
        return $this->activeUsersCount;
    }

    public function getOwnerDepartment(): Department
    {
        return $this->ownerDepartment;
    }

    public function setOwnerDepartment(Department $ownerDepartment): Tool
    {
        $this->ownerDepartment = $ownerDepartment;
        return $this;
    }

    public function getStatus(): ?ToolStatus
    {
        return $this->status;
    }

    public function setStatus(?ToolStatus $status): Tool
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
