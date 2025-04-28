<?php

namespace App\Core\Parser\Models;

/**
 * Represents an attribute/property in a class
 */
class AttributeModel
{
    /**
     * @var string Name of the attribute
     */
    private string $name;

    /**
     * @var string Visibility of the attribute
     */
    private string $visibility = 'public';

    /**
     * @var string|null Type of the attribute
     */
    private ?string $type = null;

    /**
     * @var mixed|null Default value of the attribute
     */
    private $defaultValue = null;

    /**
     * Set the name of the attribute
     *
     * @param string $name The attribute name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of the attribute
     *
     * @return string The attribute name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the visibility of the attribute
     *
     * @param string $visibility The attribute visibility
     * @return self
     */
    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    /**
     * Get the visibility of the attribute
     *
     * @return string The attribute visibility
     */
    public function getVisibility(): string
    {
        return $this->visibility;
    }

    /**
     * Set the type of the attribute
     *
     * @param string|null $type The attribute type
     * @return self
     */
    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the type of the attribute
     *
     * @return string|null The attribute type
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set the default value of the attribute
     *
     * @param mixed|null $defaultValue The default value
     * @return self
     */
    public function setDefaultValue($defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * Get the default value of the attribute
     *
     * @return mixed|null The default value
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Convert the attribute to an array
     *
     * @return array The attribute as an array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'visibility' => $this->visibility,
            'type' => $this->type,
            'defaultValue' => $this->defaultValue
        ];
    }
}
