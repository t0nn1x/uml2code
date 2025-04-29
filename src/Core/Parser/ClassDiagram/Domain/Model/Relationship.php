<?php

namespace App\Core\Parser\ClassDiagram\Domain\Model;

use App\Core\Parser\ClassDiagram\Domain\ValueObject\RelationshipType;

/**
 * Represents a relationship between classes in a UML class diagram
 */
class Relationship
{
    /**
     * @var string Source class of the relationship
     */
    private string $source;

    /**
     * @var string Target class of the relationship
     */
    private string $target;

    /**
     * @var RelationshipType Type of the relationship
     */
    private RelationshipType $type;

    /**
     * @var string|null Label for the relationship
     */
    private ?string $label = null;

    /**
     * @var string|null Multiplicity at the source end
     */
    private ?string $sourceMultiplicity = null;

    /**
     * @var string|null Multiplicity at the target end
     */
    private ?string $targetMultiplicity = null;

    /**
     * @var bool Whether this is a bidirectional relationship
     */
    private bool $bidirectional = false;

    /**
     * Create a new relationship
     *
     * @param string $source Source class name
     * @param string $target Target class name
     * @param RelationshipType $type Type of relationship
     */
    public function __construct(string $source, string $target, RelationshipType $type)
    {
        $this->source = $source;
        $this->target = $target;
        $this->type = $type;
    }

    /**
     * Create a relationship from parsed values
     *
     * @param string $source Source class name
     * @param string $target Target class name
     * @param string $type Relationship type as string
     * @return self
     */
    public static function fromParsed(string $source, string $target, string $type): self
    {
        return new self(
            $source,
            $target,
            RelationshipType::fromString($type)
        );
    }

    /**
     * Get the source class
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Get the target class
     *
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Get the relationship type
     *
     * @return RelationshipType
     */
    public function getType(): RelationshipType
    {
        return $this->type;
    }

    /**
     * Set the label for the relationship
     *
     * @param string|null $label The label
     * @return self
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Get the label for the relationship
     *
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set the multiplicity at the source end
     *
     * @param string|null $multiplicity The source multiplicity
     * @return self
     */
    public function setSourceMultiplicity(?string $multiplicity): self
    {
        $this->sourceMultiplicity = $multiplicity;
        return $this;
    }

    /**
     * Get the multiplicity at the source end
     *
     * @return string|null
     */
    public function getSourceMultiplicity(): ?string
    {
        return $this->sourceMultiplicity;
    }

    /**
     * Set the multiplicity at the target end
     *
     * @param string|null $multiplicity The target multiplicity
     * @return self
     */
    public function setTargetMultiplicity(?string $multiplicity): self
    {
        $this->targetMultiplicity = $multiplicity;
        return $this;
    }

    /**
     * Get the multiplicity at the target end
     *
     * @return string|null
     */
    public function getTargetMultiplicity(): ?string
    {
        return $this->targetMultiplicity;
    }

    /**
     * Set whether this is a bidirectional relationship
     *
     * @param bool $bidirectional Whether the relationship is bidirectional
     * @return self
     */
    public function setBidirectional(bool $bidirectional): self
    {
        $this->bidirectional = $bidirectional;
        return $this;
    }

    /**
     * Check if the relationship is bidirectional
     *
     * @return bool
     */
    public function isBidirectional(): bool
    {
        return $this->bidirectional;
    }

    /**
     * Convert to an array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [
            'source' => $this->source,
            'target' => $this->target,
            'type' => (string)$this->type,
        ];

        if ($this->label) {
            $result['label'] = $this->label;
        }

        if ($this->sourceMultiplicity) {
            $result['sourceMultiplicity'] = $this->sourceMultiplicity;
        }

        if ($this->targetMultiplicity) {
            $result['targetMultiplicity'] = $this->targetMultiplicity;
        }

        if ($this->bidirectional) {
            $result['bidirectional'] = true;
        }

        return $result;
    }
}
