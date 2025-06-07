<?php

namespace App\Entity;

use App\Repository\UserStatisticsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserStatisticsRepository::class)]
#[ORM\Table(name: 'user_statistics')]
#[ORM\Index(columns: ['user_id'], name: 'idx_user_statistics_user')]
class UserStatistics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalParseActions = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalConvertActions = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalGenerateActions = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalFilesGenerated = 0;

    #[ORM\Column(type: 'bigint', options: ['default' => 0])]
    private int $totalLinesOfCode = 0;

    #[ORM\Column(type: 'json')]
    private array $languageStatistics = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $lastUpdated = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lastUpdated = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTotalParseActions(): int
    {
        return $this->totalParseActions;
    }

    public function setTotalParseActions(int $totalParseActions): static
    {
        $this->totalParseActions = $totalParseActions;
        $this->updateLastUpdated();
        return $this;
    }

    public function incrementParseActions(int $increment = 1): static
    {
        $this->totalParseActions += $increment;
        $this->updateLastUpdated();
        return $this;
    }

    public function getTotalConvertActions(): int
    {
        return $this->totalConvertActions;
    }

    public function setTotalConvertActions(int $totalConvertActions): static
    {
        $this->totalConvertActions = $totalConvertActions;
        $this->updateLastUpdated();
        return $this;
    }

    public function incrementConvertActions(int $increment = 1): static
    {
        $this->totalConvertActions += $increment;
        $this->updateLastUpdated();
        return $this;
    }

    public function getTotalGenerateActions(): int
    {
        return $this->totalGenerateActions;
    }

    public function setTotalGenerateActions(int $totalGenerateActions): static
    {
        $this->totalGenerateActions = $totalGenerateActions;
        $this->updateLastUpdated();
        return $this;
    }

    public function incrementGenerateActions(int $increment = 1): static
    {
        $this->totalGenerateActions += $increment;
        $this->updateLastUpdated();
        return $this;
    }

    public function getTotalFilesGenerated(): int
    {
        return $this->totalFilesGenerated;
    }

    public function setTotalFilesGenerated(int $totalFilesGenerated): static
    {
        $this->totalFilesGenerated = $totalFilesGenerated;
        $this->updateLastUpdated();
        return $this;
    }

    public function incrementFilesGenerated(int $increment): static
    {
        $this->totalFilesGenerated += $increment;
        $this->updateLastUpdated();
        return $this;
    }

    public function getTotalLinesOfCode(): int
    {
        return $this->totalLinesOfCode;
    }

    public function setTotalLinesOfCode(int $totalLinesOfCode): static
    {
        $this->totalLinesOfCode = $totalLinesOfCode;
        $this->updateLastUpdated();
        return $this;
    }

    public function incrementLinesOfCode(int $increment): static
    {
        $this->totalLinesOfCode += $increment;
        $this->updateLastUpdated();
        return $this;
    }

    public function getLanguageStatistics(): array
    {
        return $this->languageStatistics;
    }

    public function setLanguageStatistics(array $languageStatistics): static
    {
        $this->languageStatistics = $languageStatistics;
        $this->updateLastUpdated();
        return $this;
    }

    public function incrementLanguageUsage(string $language, int $actions = 1, int $linesOfCode = 0): static
    {
        if (!isset($this->languageStatistics[$language])) {
            $this->languageStatistics[$language] = [
                'count' => 0,
                'totalLines' => 0
            ];
        }

        $this->languageStatistics[$language]['count'] += $actions;
        $this->languageStatistics[$language]['totalLines'] += $linesOfCode;
        $this->updateLastUpdated();
        
        return $this;
    }

    public function getFormattedLanguageStatistics(): array
    {
        $formatted = [];
        foreach ($this->languageStatistics as $language => $stats) {
            $formatted[] = [
                'language' => $language,
                'count' => $stats['count'],
                'totalLines' => $stats['totalLines']
            ];
        }

        // Sort by count descending
        usort($formatted, fn($a, $b) => $b['count'] - $a['count']);

        return $formatted;
    }

    public function getTotalActions(): int
    {
        return $this->totalParseActions + $this->totalConvertActions + $this->totalGenerateActions;
    }

    public function getActionBreakdown(): array
    {
        return [
            'parse' => $this->totalParseActions,
            'convert' => $this->totalConvertActions,
            'generate' => $this->totalGenerateActions
        ];
    }

    public function getLastUpdated(): ?\DateTimeImmutable
    {
        return $this->lastUpdated;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function updateLastUpdated(): void
    {
        $this->lastUpdated = new \DateTimeImmutable();
    }
} 
