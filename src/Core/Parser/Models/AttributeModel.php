<?php

namespace App\Core\Parser\Models;

use App\Core\Parser\Util\TypeParser;

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
     * @var array|null Type of the attribute as a structured type object
     */
    private ?array $type = null;

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
     * @param string|null $type The attribute type string
     * @return self
     */
    public function setType(?string $type): self
    {
        $this->type = $type ? TypeParser::parseType($type) : null;
        return $this;
    }

    /**
     * Get the type of the attribute
     *
     * @return array|null The attribute type as a structured type object
     */
    public function getType(): ?array
    {
        return $this->type;
    }

    /**
     * Get the type as a string
     *
     * @return string|null The type string
     */
    public function getTypeString(): ?string
    {
        return $this->type ? $this->typeToString($this->type) : null;
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
     * Convert a type object back to its string representation
     * 
     * @param array|null $type The type object
     * @return string The string representation
     */
    private function typeToString(?array $type): string
    {
        if (!$type) {
            return '';
        }

        switch ($type['kind']) {
            case 'Primitive':
            case 'Class':
                return $type['name'];
            case 'Array':
                return $this->typeToString($type['elementType']) . '[]';
            case 'Generic':
                $typeArgs = array_map([$this, 'typeToString'], $type['typeArguments']);
                return $type['base'] . '<' . implode(',', $typeArgs) . '>';
            default:
                return '';
        }
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
