<?php

namespace App\Core\Parser\Models;

/**
 * Represents a class, interface, or enum in a UML diagram
 */
class ClassModel
{
    /**
     * @var string Name of the class
     */
    private string $name;

    /**
     * @var string Type of the class (class, interface, abstract class, enum)
     */
    private string $type;

    /**
     * @var array Type parameters for generic classes
     */
    private array $typeParameters = [];

    /**
     * @var array List of attributes
     */
    private array $attributes = [];

    /**
     * @var array List of methods
     */
    private array $methods = [];

    /**
     * @var string|null Name of the parent class
     */
    private ?string $extends = null;

    /**
     * @var array List of implemented interfaces
     */
    private array $implements = [];

    /**
     * Constructor
     *
     * @param string $name Name of the class
     * @param string $type Type of the class
     */
    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Get the name of the class
     *
     * @return string The class name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the type of the class
     *
     * @return string The class type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the type parameters for generic classes
     *
     * @param array $typeParameters List of type parameters
     * @return self
     */
    public function setTypeParameters(array $typeParameters): self
    {
        $this->typeParameters = $typeParameters;
        return $this;
    }

    /**
     * Get the type parameters
     *
     * @return array The type parameters
     */
    public function getTypeParameters(): array
    {
        return $this->typeParameters;
    }

    /**
     * Add an attribute to the class
     *
     * @param AttributeModel $attribute The attribute to add
     * @return self
     */
    public function addAttribute(AttributeModel $attribute): self
    {
        $this->attributes[] = $attribute;
        return $this;
    }

    /**
     * Get all attributes of the class
     *
     * @return array The attributes
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Add a method to the class
     *
     * @param MethodModel $method The method to add
     * @return self
     */
    public function addMethod(MethodModel $method): self
    {
        $this->methods[] = $method;
        return $this;
    }

    /**
     * Get all methods of the class
     *
     * @return array The methods
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Set the parent class
     *
     * @param string|null $extends Name of the parent class
     * @return self
     */
    public function setExtends(?string $extends): self
    {
        $this->extends = $extends;
        return $this;
    }

    /**
     * Get the parent class
     *
     * @return string|null The parent class
     */
    public function getExtends(): ?string
    {
        return $this->extends;
    }

    /**
     * Set the implemented interfaces
     *
     * @param array $implements List of implemented interfaces
     * @return self
     */
    public function setImplements(array $implements): self
    {
        // Clean up interface names by removing any trailing braces
        $cleanedImplements = [];
        foreach ($implements as $interface) {
            // Remove curly braces and trim
            $interface = trim(str_replace(['{', '}'], '', $interface));
            if (!empty($interface)) {
                $cleanedImplements[] = $interface;
            }
        }

        $this->implements = $cleanedImplements;
        return $this;
    }

    /**
     * Get the implemented interfaces
     *
     * @return array The implemented interfaces
     */
    public function getImplements(): array
    {
        return $this->implements;
    }
}
