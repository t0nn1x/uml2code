<?php

namespace App\Core\Parser\ClassDiagram\Domain\ValueObject;

/**
 * Value object representing a type in the UML diagram
 */
class Type
{
    /**
     * @var string The type name/expression
     */
    private string $value;

    /**
     * @var bool Whether this is a primitive type
     */
    private bool $isPrimitive;

    /**
     * @var bool Whether this is a collection type
     */
    private bool $isCollection;

    /**
     * @var bool Whether this is an array type
     */
    private bool $isArray;

    /**
     * @var Type[] Type arguments for generic types
     */
    private array $typeArguments = [];

    /**
     * List of primitive types
     */
    private const PRIMITIVE_TYPES = [
        'string',
        'int',
        'integer',
        'float',
        'double',
        'boolean',
        'bool',
        'array',
        'object',
        'resource',
        'mixed',
        'void',
        'null',
        'callable',
        'iterable',
        'byte',
        'short',
        'long',
        'char'
    ];

    /**
     * List of collection types
     */
    private const COLLECTION_TYPES = [
        'List',
        'Map',
        'Set',
        'Collection',
        'Array',
        'Dictionary'
    ];

    /**
     * Create a new type
     *
     * @param string $value The type expression
     */
    private function __construct(string $value)
    {
        $this->value = $value;
        $this->parseType();
    }

    /**
     * Create a type from a string
     *
     * @param string $type The type string
     * @return self
     */
    public static function fromString(string $type): self
    {
        return new self(trim($type));
    }

    /**
     * Parse the type expression to determine its properties
     */
    private function parseType(): void
    {
        // Extract the base type (without generics or array notation)
        $baseType = $this->value;

        // Check if it's an array type
        $this->isArray = str_ends_with($baseType, '[]');
        if ($this->isArray) {
            $baseType = substr($baseType, 0, -2);
        }

        // Check if it's a generic type
        if (preg_match('/^(\w+)<(.+)>$/', $baseType, $matches)) {
            $baseType = $matches[1];
            // Parse type arguments (simple implementation, might need enhancement for nested generics)
            $typeArgStrings = explode(',', $matches[2]);
            foreach ($typeArgStrings as $typeArgString) {
                $this->typeArguments[] = self::fromString(trim($typeArgString));
            }
        }

        $this->isPrimitive = in_array(strtolower($baseType), self::PRIMITIVE_TYPES);
        $this->isCollection = in_array($baseType, self::COLLECTION_TYPES);
    }

    /**
     * Check if this is a primitive type
     *
     * @return bool
     */
    public function isPrimitive(): bool
    {
        return $this->isPrimitive;
    }

    /**
     * Check if this is a collection type
     *
     * @return bool
     */
    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    /**
     * Check if this is an array type
     *
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->isArray;
    }

    /**
     * Get the type arguments
     *
     * @return Type[]
     */
    public function getTypeArguments(): array
    {
        return $this->typeArguments;
    }

    /**
     * Get the original type string
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Get the string representation
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
