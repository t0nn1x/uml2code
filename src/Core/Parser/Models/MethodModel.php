<?php

namespace App\Core\Parser\Models;

use App\Core\Parser\Util\TypeParser;

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
     * @var array|null Return type of the method as a structured type object
     */
    private ?array $returnType = null;

    /**
     * @var array Parameters of the method as structured objects
     */
    private array $parameters = [];

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
     * Set the return type of the method
     *
     * @param string|null $returnType The method return type string
     * @return self
     */
    public function setReturnType(?string $returnType): self
    {
        $this->returnType = $returnType ? TypeParser::parseType($returnType) : null;
        return $this;
    }

    /**
     * Get the return type of the method
     *
     * @return array|null The method return type as a structured type object
     */
    public function getReturnType(): ?array
    {
        return $this->returnType;
    }

    /**
     * Get the return type as a string
     *
     * @return string|null The return type string
     */
    public function getReturnTypeString(): ?string
    {
        return $this->returnType ? $this->typeToString($this->returnType) : null;
    }

    /**
     * Set the parameters of the method
     *
     * @param string $parameters The parameters string (e.g. "event: string, props: Map<string, object>")
     * @return self
     */
    public function setParameters(string $parameters): self
    {
        $this->parameters = TypeParser::parseParameters($parameters);
        return $this;
    }

    /**
     * Get the parameters of the method
     *
     * @return array The method parameters as structured objects
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get the parameters as a string
     *
     * @return string The parameters string
     */
    public function getParametersString(): string
    {
        $paramStrings = [];
        foreach ($this->parameters as $param) {
            $paramStrings[] = $param['name'] . ': ' . $this->typeToString($param['type']);
        }
        return implode(', ', $paramStrings);
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
