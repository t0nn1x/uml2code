<?php

namespace App\Entity;

use App\Repository\SystemLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SystemLogRepository::class)]
#[ORM\Table(name: 'system_logs')]
#[ORM\Index(columns: ['level', 'created_at'], name: 'idx_level_created')]
#[ORM\Index(columns: ['channel', 'created_at'], name: 'idx_channel_created')]
class SystemLog
{
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_NOTICE = 'notice';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_ALERT = 'alert';
    public const LEVEL_EMERGENCY = 'emergency';

    public const VALID_LEVELS = [
        self::LEVEL_DEBUG,
        self::LEVEL_INFO,
        self::LEVEL_NOTICE,
        self::LEVEL_WARNING,
        self::LEVEL_ERROR,
        self::LEVEL_CRITICAL,
        self::LEVEL_ALERT,
        self::LEVEL_EMERGENCY,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $level;

    #[ORM\Column(length: 100)]
    private string $channel;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $context = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $extra = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $requestUri = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        if (!in_array($level, self::VALID_LEVELS)) {
            throw new \InvalidArgumentException(sprintf('Invalid log level "%s". Valid levels are: %s', $level, implode(', ', self::VALID_LEVELS)));
        }
        
        $this->level = $level;
        return $this;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): static
    {
        $this->context = $context;
        return $this;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function setExtra(?array $extra): static
    {
        $this->extra = $extra;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getRequestUri(): ?string
    {
        return $this->requestUri;
    }

    public function setRequestUri(?string $requestUri): static
    {
        $this->requestUri = $requestUri;
        return $this;
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

    public function getLevelPriority(): int
    {
        return match($this->level) {
            self::LEVEL_DEBUG => 100,
            self::LEVEL_INFO => 200,
            self::LEVEL_NOTICE => 250,
            self::LEVEL_WARNING => 300,
            self::LEVEL_ERROR => 400,
            self::LEVEL_CRITICAL => 500,
            self::LEVEL_ALERT => 550,
            self::LEVEL_EMERGENCY => 600,
            default => 0,
        };
    }

    public function isError(): bool
    {
        return $this->getLevelPriority() >= 400;
    }
} 
