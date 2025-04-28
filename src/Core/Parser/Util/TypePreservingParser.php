<?php

namespace App\Core\Parser\Util;

/**
 * Helper class to handle type parsing while preserving generic type information
 */
class TypePreservingParser
{
    /**
     * Extracts the full type information from a type string, preserving generics and arrays
     *
     * @param string $typeString The type string to parse
     * @return string The parsed type with preserved generics and arrays
     */
    public static function parseType(string $typeString): string
    {
        // Trim any whitespace
        $typeString = trim($typeString);

        // Return the type string as is to preserve all generic and array notation
        return $typeString;
    }

    /**
     * Checks if a given type is a primitive type that should not be treated as a class
     *
     * @param string $type The type to check
     * @return bool True if the type is primitive
     */
    public static function isPrimitiveType(string $type): bool
    {
        // List of primitive types and built-ins that should not be treated as classes
        $primitiveTypes = [
            'string',
            'int',
            'integer',
            'float',
            'double',
            'bool',
            'boolean',
            'array',
            'object',
            'resource',
            'null',
            'mixed',
            'void',
            'callable',
            'iterable',
            'byte',
            'short',
            'long',
            'char',
            'UUID',
            'DateTime',
            // Exclude type parameter names
            'K',
            'V',
            'T'
        ];

        // First extract the base type without generics or array notation
        $baseType = $type;
        if (strpos($type, '<') !== false) {
            $baseType = substr($type, 0, strpos($type, '<'));
        }
        if (strpos($type, '[') !== false) {
            $baseType = substr($type, 0, strpos($type, '['));
        }

        return in_array(strtolower($baseType), $primitiveTypes);
    }

    /**
     * Checks if a type represents a collection like Map, List, etc.
     *
     * @param string $type The type to check
     * @return bool True if the type is a collection
     */
    public static function isCollectionType(string $type): bool
    {
        $collectionTypes = ['Map', 'List', 'Set', 'Collection', 'Dictionary', 'Array'];

        $baseType = $type;
        if (strpos($type, '<') !== false) {
            $baseType = substr($type, 0, strpos($type, '<'));
        }

        return in_array($baseType, $collectionTypes);
    }
}
