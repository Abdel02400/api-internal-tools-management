<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\UniqueConstraint(name: 'name', columns: ['name'])]
#[ORM\HasLifecycleCallbacks]
final class Category
{
    public const TABLE_NAME = 'categories';
    public const MAX_NAME_LENGTH = 50;
    public const COLOR_HEX_LENGTH = 7;
    public const string DEFAULT_COLOR = '#6366f1';
    public const string GROUP_READ = 'category:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups([self::GROUP_READ])]
    private ?int $id = null;

    #[ORM\Column(length: self::MAX_NAME_LENGTH)]
    #[Groups([self::GROUP_READ])]
    private string $name;

    #[ORM\Column(type: Types::TEXT, length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, nullable: true)]
    #[Groups([self::GROUP_READ])]
    private ?string $description = null;

    #[ORM\Column(length: self::COLOR_HEX_LENGTH, nullable: true, options: ['default' => self::DEFAULT_COLOR])]
    #[Groups([self::GROUP_READ])]
    private ?string $colorHex = self::DEFAULT_COLOR;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?DateTimeImmutable $createdAt = null;

    /** @var Collection<int, Tool> */
    #[ORM\OneToMany(targetEntity: Tool::class, mappedBy: 'category')]
    private Collection $tools;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->tools = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Category
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Category
    {
        $this->description = $description;
        return $this;
    }

    public function getColorHex(): ?string
    {
        return $this->colorHex;
    }

    public function setColorHex(?string $colorHex): Category
    {
        $this->colorHex = $colorHex;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, Tool> */
    public function getTools(): Collection
    {
        return $this->tools;
    }
}
