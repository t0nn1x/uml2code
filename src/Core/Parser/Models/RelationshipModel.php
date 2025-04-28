<?php

namespace App\Core\Parser\Models;

/**
 * Represents a relationship between classes in a UML diagram
 */
class RelationshipModel
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
     * @var string Type of the relationship
     */
    private string $type;

    /**
     * @var string|null Label of the relationship
     */
    private ?string $label = null;

    /**
     * @var string|null Multiplicity of the source
     */
    private ?string $sourceMultiplicity = null;

    /**
     * @var string|null Multiplicity of the target
     */
    private ?string $targetMultiplicity = null;

    /**
     * Set the source class of the relationship
     *
     * @param string $source The source class
     * @return self
     */
    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Get the source class of the relationship
     *
     * @return string The source class
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Set the target class of the relationship
     *
     * @param string $target The target class
     * @return self
     */
    public function setTarget(string $target): self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Get the target class of the relationship
     *
     * @return string The target class
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Set the type of the relationship
     *
     * @param string $type The relationship type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the type of the relationship
     *
     * @return string The relationship type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the label of the relationship
     *
     * @param string|null $label The relationship label
     * @return self
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Get the label of the relationship
     *
     * @return string|null The relationship label
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set the multiplicity of the source
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
     * Get the multiplicity of the source
     *
     * @return string|null The source multiplicity
     */
    public function getSourceMultiplicity(): ?string
    {
        return $this->sourceMultiplicity;
    }

    /**
     * Set the multiplicity of the target
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
     * Get the multiplicity of the target
     *
     * @return string|null The target multiplicity
     */
    public function getTargetMultiplicity(): ?string
    {
        return $this->targetMultiplicity;
    }

    /**
     * Convert the relationship to an array
     *
     * @return array The relationship as an array
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'target' => $this->target,
            'type' => $this->type,
            'label' => $this->label,
            'sourceMultiplicity' => $this->sourceMultiplicity,
            'targetMultiplicity' => $this->targetMultiplicity
        ];
    }
}
