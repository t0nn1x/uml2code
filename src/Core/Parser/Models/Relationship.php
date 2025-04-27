<?php

namespace App\Core\Parser\Models;

/**
 * Represents a relationship between classes in a class diagram
 */
class Relationship
{
    // Relationship type constants
    public const TYPE_ASSOCIATION = 'association';
    public const TYPE_INHERITANCE = 'inheritance';
    public const TYPE_IMPLEMENTATION = 'implementation';
    public const TYPE_DEPENDENCY = 'dependency';
    public const TYPE_AGGREGATION = 'aggregation';
    public const TYPE_COMPOSITION = 'composition';
    public const TYPE_BIDIRECTIONAL = 'bidirectional';

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $target;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $label;

    /**
     * @var array
     */
    private $metadata = [];

    /**
     * Get source class name
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Set source class name
     *
     * @param string $source
     * @return self
     */
    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Get target class name
     *
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Set target class name
     *
     * @param string $target
     * @return self
     */
    public function setTarget(string $target): self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Get relationship type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set relationship type
     *
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get relationship label
     *
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set relationship label
     *
     * @param string|null $label
     * @return self
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Get metadata
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Set metadata
     *
     * @param array $metadata
     * @return self
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Add metadata item
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get a specific metadata value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if this is an inheritance relationship
     *
     * @return bool
     */
    public function isInheritance(): bool
    {
        return $this->type === self::TYPE_INHERITANCE;
    }

    /**
     * Check if this is an implementation relationship
     *
     * @return bool
     */
    public function isImplementation(): bool
    {
        return $this->type === self::TYPE_IMPLEMENTATION;
    }

    /**
     * Check if this is a composition relationship
     *
     * @return bool
     */
    public function isComposition(): bool
    {
        return $this->type === self::TYPE_COMPOSITION;
    }

    /**
     * Check if this is an aggregation relationship
     *
     * @return bool
     */
    public function isAggregation(): bool
    {
        return $this->type === self::TYPE_AGGREGATION;
    }
}
