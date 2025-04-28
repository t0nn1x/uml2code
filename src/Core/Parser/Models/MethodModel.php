<?php

namespace App\Core\Parser\Models;

/**
 * Represents a method in a class
 */
class MethodModel
{
    /**
     * @var string Name of the method
     */
    private string $name;

    /**
     * @var string Visibility of the method
     */
    private string $visibility = 'public';

    /**
     * @var string Parameters of the method
     */
    private string $parameters = '';

    /**
     * @var string|null Return type of the method
     */
    private ?string $returnType = null;

    /**
     * Set the name of the method
     *
     * @param string $name The method name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of the method
     *
     * @return string The method name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the visibility of the method
     *
     * @param string $visibility The method visibility
     * @return self
     */
    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    /**
     * Get the visibility of the method
     *
     * @return string The method visibility
     */
    public function getVisibility(): string
    {
        return $this->visibility;
    }

    /**
     * Set the parameters of the method
     *
     * @param string $parameters The method parameters
     * @return self
     */
    public function setParameters(string $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Get the parameters of the method
     *
     * @return string The method parameters
     */
    public function getParameters(): string
    {
        return $this->parameters;
    }

    /**
     * Set the return type of the method
     *
     * @param string|null $returnType The method return type
     * @return self
     */
    public function setReturnType(?string $returnType): self
    {
        $this->returnType = $returnType;
        return $this;
    }

    /**
     * Get the return type of the method
     *
     * @return string|null The method return type
     */
    public function getReturnType(): ?string
    {
        return $this->returnType;
    }

    /**
     * Convert the method to an array
     *
     * @return array The method as an array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'visibility' => $this->visibility,
            'parameters' => $this->parameters,
            'returnType' => $this->returnType
        ];
    }
}
