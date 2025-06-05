<?php

namespace App\Entity;

use App\Repository\ActionHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActionHistoryRepository::class)]
#[ORM\Table(name: 'action_history')]
#[ORM\Index(columns: ['user_id', 'action_type', 'created_at'], name: 'idx_user_action_created')]
class ActionHistory
{
    public const ACTION_CONVERT = 'convert';
    public const ACTION_PARSE = 'parse';
    public const ACTION_GENERATE = 'generate';
    
    public const VALID_ACTIONS = [
        self::ACTION_CONVERT,
        self::ACTION_PARSE,
        self::ACTION_GENERATE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private ?string $actionType = null;

    #[ORM\Column(length: 50)]
    private string $diagramType = 'ClassDiagram';

    #[ORM\Column(type: 'json')]
    private array $files = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $programmingLanguage = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $generatorVersion = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $totalLinesOfCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $diagramName = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $diagramSize = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getActionType(): ?string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): static
    {
        if (!in_array($actionType, self::VALID_ACTIONS)) {
            throw new \InvalidArgumentException(sprintf('Invalid action type "%s". Valid values are: %s', $actionType, implode(', ', self::VALID_ACTIONS)));
        }
        
        $this->actionType = $actionType;

        return $this;
    }

    public function getDiagramType(): string
    {
        return $this->diagramType;
    }

    public function setDiagramType(string $diagramType): static
    {
        $this->diagramType = $diagramType;

        return $this;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(array $files): static
    {
        // Validate files structure
        foreach ($files as $file) {
            if (!is_array($file) || !isset($file['filename']) || !isset($file['content'])) {
                throw new \InvalidArgumentException('Each file must have "filename" and "content" keys');
            }
        }
        
        $this->files = $files;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
    
    /**
     * Get the total number of files
     */
    public function getFileCount(): int
    {
        return count($this->files);
    }
    
    /**
     * Get a summary of filenames
     */
    public function getFileNames(): array
    {
        return array_map(fn($file) => $file['filename'], $this->files);
    }

    public function getProgrammingLanguage(): ?string
    {
        return $this->programmingLanguage;
    }

    public function setProgrammingLanguage(?string $programmingLanguage): static
    {
        $this->programmingLanguage = $programmingLanguage;

        return $this;
    }

    public function getGeneratorVersion(): ?string
    {
        return $this->generatorVersion;
    }

    public function setGeneratorVersion(?string $generatorVersion): static
    {
        $this->generatorVersion = $generatorVersion;

        return $this;
    }

    public function getTotalLinesOfCode(): ?int
    {
        return $this->totalLinesOfCode;
    }

    public function setTotalLinesOfCode(?int $totalLinesOfCode): static
    {
        $this->totalLinesOfCode = $totalLinesOfCode;

        return $this;
    }

    public function getDiagramName(): ?string
    {
        return $this->diagramName;
    }

    public function setDiagramName(?string $diagramName): static
    {
        $this->diagramName = $diagramName;

        return $this;
    }

    public function getDiagramSize(): ?int
    {
        return $this->diagramSize;
    }

    public function setDiagramSize(?int $diagramSize): static
    {
        $this->diagramSize = $diagramSize;

        return $this;
    }

    /**
     * Calculate total lines of code from files if not set
     */
    public function calculateTotalLinesOfCode(): void
    {
        if ($this->totalLinesOfCode === null && !empty($this->files)) {
            $totalLines = 0;
            foreach ($this->files as $file) {
                if (isset($file['content'])) {
                    $totalLines += substr_count($file['content'], "\n") + 1;
                }
            }
            $this->totalLinesOfCode = $totalLines;
        }
    }
}
