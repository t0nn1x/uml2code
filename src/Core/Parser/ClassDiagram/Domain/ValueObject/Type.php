<?php

namespace App\Core\Parser\ClassDiagram\Domain\ValueObject;

/**
 * Value object representing a type in the UML diagram
 * Enhanced with better support for generic types
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
        'char',
        'UUID',
        'DateTime'
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
        'Dictionary',
        'ArrayList',
        'LinkedList',
        'HashSet',
        'TreeSet',
        'HashMap',
        'TreeMap',
        'Vector',
        'Stack',
        'Queue',
        'Deque'
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

        // Check if it's a generic type with improved regex pattern
        // This will properly handle whitespace variations and nested generics
        if (preg_match('/^(\w+)\s*<\s*(.+?)\s*>\s*$/', $baseType, $matches)) {
            $baseType = $matches[1];
            // Parse type arguments using a more robust approach
            $typeArgStrings = $this->splitGenericParameters($matches[2]);
            foreach ($typeArgStrings as $typeArgString) {
                $this->typeArguments[] = self::fromString(trim($typeArgString));
            }
        }

        // Check if this type is primitive
        $this->isPrimitive = in_array(strtolower($baseType), array_map('strtolower', self::PRIMITIVE_TYPES));

        // Check if this type is a collection
        $this->isCollection = in_array($baseType, self::COLLECTION_TYPES);
    }

    /**
     * Split generic parameters string into individual type arguments
     * This handles nested generics correctly by tracking angle bracket depth
     *
     * @param string $paramsStr The generic parameters string
     * @return array The individual type argument strings
     */
    private function splitGenericParameters(string $paramsStr): array
    {
        $result = [];
        $current = '';
        $depth = 0;

        for ($i = 0; $i < strlen($paramsStr); $i++) {
            $char = $paramsStr[$i];

            if ($char === '<') {
                $depth++;
                $current .= $char;
            } elseif ($char === '>') {
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0) {
                $result[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $result[] = $current;
        }

        return array_map('trim', $result);
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
        // If we have type arguments, reconstruct the full generic type string
        if (!empty($this->typeArguments)) {
            $baseType = $this->getBaseName();
            $typeArgs = [];

            foreach ($this->typeArguments as $typeArg) {
                $typeArgs[] = $typeArg->toString();
            }

            $typeArgString = implode(', ', $typeArgs);
            return $baseType . '<' . $typeArgString . '>' . ($this->isArray ? '[]' : '');
        }

        // Otherwise return the original value
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

    /**
     * Get base type name without generics or array notation
     *
     * @return string
     */
    public function getBaseName(): string
    {
        $value = $this->value;

        // Remove array notation
        if ($this->isArray) {
            $value = substr($value, 0, -2);
        }

        // Remove generic parameters
        if (preg_match('/^(\w+)\s*<.+>\s*$/', $value, $matches)) {
            $value = $matches[1];
        }

        return $value;
    }
}
