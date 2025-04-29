<?php

namespace App\Core\Parser\Util;

/**
 * Utility class for parsing type strings into structured type objects
 */
class TypeParser
{
    /**
     * Parse a type string into a structured type object
     *
     * @param string|null $typeStr The type string to parse
     * @return array|null The structured type object
     */
    public static function parseType(?string $typeStr): ?array
    {
        if (!$typeStr) {
            return null;
        }

        // Handle array types (e.g. "byte[]", "Role[]")
        if (str_ends_with($typeStr, '[]')) {
            $baseType = substr($typeStr, 0, -2);
            return [
                'kind' => 'Array',
                'elementType' => self::parseType($baseType)
            ];
        }

        // Handle generic types (e.g. "Map<string,object>", "List<LogEntry>")
        if (preg_match('/^(\w+)\s*<(.+)>$/', $typeStr, $matches)) {
            $baseType = $matches[1];
            $typeArgs = array_map('trim', self::splitTypeArguments($matches[2]));
            
            return [
                'kind' => 'Generic',
                'base' => $baseType,
                'typeArguments' => array_map([self::class, 'parseType'], $typeArgs)
            ];
        }

        // Handle primitive and class types
        return [
            'kind' => self::isPrimitiveType($typeStr) ? 'Primitive' : 'Class',
            'name' => $typeStr
        ];
    }

    /**
     * Parse method parameters string into structured parameter objects
     *
     * @param string $paramsStr The parameters string to parse
     * @return array The structured parameter objects
     */
    public static function parseParameters(string $paramsStr): array
    {
        if (empty($paramsStr)) {
            return [];
        }

        $params = [];
        $paramPairs = self::splitParameters($paramsStr);

        foreach ($paramPairs as $pair) {
            if (preg_match('/^(\w+)\s*:\s*(.+)$/', $pair, $matches)) {
                $params[] = [
                    'name' => $matches[1],
                    'type' => self::parseType($matches[2])
                ];
            }
        }

        return $params;
    }

    /**
     * Split parameters string into individual parameter strings, handling nested generics
     *
     * @param string $paramsStr The parameters string to split
     * @return array The individual parameter strings
     */
    private static function splitParameters(string $paramsStr): array
    {
        $result = [];
        $current = '';
        $depth = 0;
        $inGeneric = false;

        for ($i = 0; $i < strlen($paramsStr); $i++) {
            $char = $paramsStr[$i];

            if ($char === '<') {
                $depth++;
                $inGeneric = true;
            } else if ($char === '>') {
                $depth--;
                if ($depth === 0) {
                    $inGeneric = false;
                }
            }

            if ($char === ',' && $depth === 0 && !$inGeneric) {
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
     * Check if a type is a primitive type
     *
     * @param string $type The type to check
     * @return bool True if the type is primitive
     */
    private static function isPrimitiveType(string $type): bool
    {
        $primitiveTypes = [
            'string', 'int', 'integer', 'float', 'double',
            'bool', 'boolean', 'void', 'array', 'object',
            'resource', 'null', 'mixed', 'callable', 'iterable',
            'byte', 'short', 'long', 'char'
        ];

        return in_array(strtolower($type), $primitiveTypes);
    }

    /**
     * Split type arguments in generic types, handling nested generics
     *
     * @param string $typeArgs The type arguments string
     * @return array The split type arguments
     */
    private static function splitTypeArguments(string $typeArgs): array
    {
        $result = [];
        $current = '';
        $depth = 0;

        for ($i = 0; $i < strlen($typeArgs); $i++) {
            $char = $typeArgs[$i];

            if ($char === '<') {
                $depth++;
            } else if ($char === '>') {
                $depth--;
            }

            if ($char === ',' && $depth === 0) {
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
} 
