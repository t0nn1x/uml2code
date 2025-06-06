<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Java;

use App\Core\Generator\ClassDiagram\Domain\Exception\GeneratorException;
use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * Java 8 code generator for class diagrams
 * 
 * Features supported in Java 8:
 * - Lambda expressions
 * - Stream API
 * - Optional class
 * - Default methods in interfaces
 * - Method references
 * - java.time API
 * - Functional interfaces
 */
class Java8CodeGenerator extends AbstractJavaCodeGenerator
{
    /**
     * Enhanced type mapping for Java 8 with time API and functional interfaces
     */
    protected const TYPE_MAPPING = [
        'string' => 'String',
        'int' => 'int',
        'integer' => 'int',
        'float' => 'float',
        'double' => 'double',
        'boolean' => 'boolean',
        'bool' => 'boolean',
        'array' => 'Object[]',
        'void' => 'void',
        'object' => 'Object',
        'mixed' => 'Object',
        'DateTime' => 'java.time.LocalDateTime',
        'datetime' => 'java.time.LocalDateTime',
        'LocalDateTime' => 'java.time.LocalDateTime',
        'localdatetime' => 'java.time.LocalDateTime',
        'Date' => 'java.time.LocalDate',
        'date' => 'java.time.LocalDate',
        'Time' => 'java.time.LocalTime',
        'time' => 'java.time.LocalTime',
        'Map' => 'java.util.Map',
        'map' => 'java.util.Map',
        'List' => 'java.util.List',
        'list' => 'java.util.List',
        'Set' => 'java.util.Set',
        'set' => 'java.util.Set',
        'Collection' => 'java.util.Collection',
        'collection' => 'java.util.Collection',
        'Optional' => 'java.util.Optional',
        'optional' => 'java.util.Optional',
        'Stream' => 'java.util.stream.Stream',
        'stream' => 'java.util.stream.Stream',
        'byte[]' => 'byte[]',
        'byte' => 'byte',
        'long' => 'long',
        'short' => 'short',
        'char' => 'char',
        'UUID' => 'java.util.UUID',
        'uuid' => 'java.util.UUID',
        'BigDecimal' => 'java.math.BigDecimal',
        'bigdecimal' => 'java.math.BigDecimal',
        'BigInteger' => 'java.math.BigInteger',
        'biginteger' => 'java.math.BigInteger',
        'Function' => 'java.util.function.Function',
        'Predicate' => 'java.util.function.Predicate',
        'Consumer' => 'java.util.function.Consumer',
        'Supplier' => 'java.util.function.Supplier',
    ];

    /**
     * @var int The index of the current class being generated
     */
    protected int $currentClassIndex = 0;
    
    /**
     * @inheritDoc
     */
    public function generate(): array
    {
        // Validate the diagram structure
        $this->validateDiagram();
        
        // Process each class in the diagram
        foreach ($this->diagram['classes'] as $index => $class) {
            $this->currentClassIndex = $index;
            $this->generateClass($class);
        }
        
        return $this->files;
    }
    
    /**
     * Validate the diagram structure
     *
     * @throws GeneratorException
     */
    protected function validateDiagram(): void
    {
        if (!isset($this->diagram['classes']) || !is_array($this->diagram['classes'])) {
            throw new GeneratorException('Invalid diagram structure: missing "classes" array');
        }
    }
    
    /**
     * Generate a Java class from the diagram class definition
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        $type = $classData['type'] ?? 'class';
        $name = $classData['name'];
        
        $fileName = "{$name}.java";
        $code = "package {$this->packageName};\n\n";
        
        // Add imports
        $imports = $this->generateImports($classData);
        if (!empty($imports)) {
            $code .= $imports . "\n";
        }
        
        // Class documentation
        $code .= "/**\n";
        $code .= " * " . ucfirst($type) . " {$name}\n";
        $code .= " * \n";
        $code .= " * Generated for Java 8+\n";
        $code .= " * Supports lambda expressions, streams, and functional interfaces\n";
        $code .= " */\n";
        
        // Class declaration
        switch ($type) {
            case 'interface':
                $code .= "public interface {$name}";
                
                // Add generic type parameters if present
                if (!empty($classData['typeParameters'])) {
                    $code .= "<" . implode(', ', $classData['typeParameters']) . ">";
                }
                
                if (!empty($classData['extends'])) {
                    // Java interfaces use "extends" for parent interfaces
                    $code .= " extends {$classData['extends']}";
                }
                $code .= " {\n";
                
                // Generate interface methods (no implementation)
                if (!empty($classData['methods'])) {
                    $code .= $this->generateInterfaceMethods($classData['methods']);
                }
                break;
                
            case 'enum':
                $code .= "public enum {$name} {\n";
                
                $hasValues = false;
                // Generate enum constants
                if (!empty($classData['enumValues'])) {
                    $code .= $this->generateEnumValues($classData['enumValues']);
                    // Check if any enum values have backing values
                    foreach ($classData['enumValues'] as $enumValue) {
                        if (is_array($enumValue) && isset($enumValue['value']) && $enumValue['value'] !== null) {
                            $hasValues = true;
                            break;
                        }
                    }
                } elseif (!empty($classData['attributes'])) {
                    // Fallback to legacy format
                    $code .= $this->generateEnumValuesFromAttributes($classData['attributes']);
                }
                
                // Add field and constructor for enums with values
                if ($hasValues) {
                    $code .= "\n";
                    $code .= "    private final String value;\n\n";
                    $code .= "    {$name}(String value) {\n";
                    $code .= "        this.value = value;\n";
                    $code .= "    }\n\n";
                    $code .= "    public String getValue() {\n";
                    $code .= "        return value;\n";
                    $code .= "    }\n";
                }
                
                // Generate methods for enum
                if (!empty($classData['methods'])) {
                    if ($hasValues) {
                        $code .= "\n";
                    }
                    $code .= $this->generateMethods($classData['methods']);
                }
                break;
                
            case 'abstract':
                $code .= "public abstract class {$name}";
                
                // Add generic type parameters if present
                if (!empty($classData['typeParameters'])) {
                    $code .= "<" . implode(', ', $classData['typeParameters']) . ">";
                }
                
                if (!empty($classData['extends'])) {
                    $code .= " extends {$classData['extends']}";
                }
                if (!empty($classData['implements'])) {
                    $code .= " implements " . implode(', ', $classData['implements']);
                }
                $code .= " {\n";
                
                // Generate properties
                if (!empty($classData['attributes'])) {
                    $code .= $this->generateProperties($classData['attributes']);
                }
                
                // Generate constructors and methods
                if (!empty($classData['methods'])) {
                    $code .= $this->generateMethods($classData['methods']);
                }
                break;
                
            default: // regular class
                $code .= "public class {$name}";
                
                // Add generic type parameters if present
                if (!empty($classData['typeParameters'])) {
                    $code .= "<" . implode(', ', $classData['typeParameters']) . ">";
                }
                
                if (!empty($classData['extends'])) {
                    $code .= " extends {$classData['extends']}";
                }
                if (!empty($classData['implements'])) {
                    $code .= " implements " . implode(', ', $classData['implements']);
                }
                $code .= " {\n";
                
                // Generate properties
                if (!empty($classData['attributes'])) {
                    $code .= $this->generateProperties($classData['attributes']);
                }
                
                // Generate constructors and methods
                if (!empty($classData['methods'])) {
                    $code .= $this->generateMethods($classData['methods']);
                }
                break;
        }
        
        $code .= "}\n";
        
        $path = $this->outputDirectory;
        $file = new CodeFile($fileName, $path, $code);
        $this->addFile($file);
        
        return $file;
    }
    

    
    /**
     * Generate Java properties from attributes
     *
     * @param array $attributes
     * @return string
     */
    protected function generateProperties(array $attributes): string
    {
        $code = "";
        
        foreach ($attributes as $attr) {
            $name = $attr['name'];
            $visibility = $this->mapVisibility($attr['visibility'] ?? 'private');
            $type = isset($attr['type']) ? $this->mapType($attr['type']) : 'Object';
            $static = $attr['static'] ?? false;
            $final = $attr['final'] ?? false;
            
            // Property documentation
            $code .= "    /**\n";
            $code .= "     * {$name} property\n";
            $code .= "     */\n";
            
            // Property declaration
            $code .= "    {$visibility}";
            
            if ($static) {
                $code .= " static";
            }
            
            if ($final) {
                $code .= " final";
            }
            
            $code .= " {$type} {$name}";
            
            // Add default value if provided
            if (isset($attr['defaultValue'])) {
                $code .= " = " . $this->formatDefaultValue($attr['defaultValue'], $type);
            }
            
            $code .= ";\n\n";
        }
        
        return $code;
    }
    
    /**
     * Generate methods with Java 8 lambda and stream support
     *
     * @param array $methods
     * @return string
     */
    protected function generateMethods(array $methods): string
    {
        $code = "";
        $constructorExists = false;
        
        foreach ($methods as $method) {
            $name = $method['name'];
            $visibility = $this->mapVisibility($method['visibility'] ?? 'public');
            $returnType = isset($method['returnType']) ? $this->mapType($method['returnType']) : 'void';
            $parameters = $method['parameters'] ?? [];
            $static = $method['static'] ?? false;
            $final = $method['final'] ?? false;
            $abstract = $method['abstract'] ?? false;
            $lambda = $method['lambda'] ?? false;
            $stream = $method['stream'] ?? false;
            
            // Check if this is a constructor
            if ($name === $this->diagram['classes'][$this->currentClassIndex]['name']) {
                $constructorExists = true;
            }
            
            // Method documentation
            $code .= "    /**\n";
            
            // Parameter documentation
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $code .= "     * @param {$paramName} The {$paramName} parameter\n";
            }
            
            // Return type documentation
            if ($returnType !== 'void') {
                $code .= "     * @return {$returnType}\n";
            }
            
            if ($lambda) {
                $code .= "     * @implNote Uses Java 8+ lambda expressions\n";
            }
            if ($stream) {
                $code .= "     * @implNote Uses Java 8+ Stream API\n";
            }
            
            $code .= "     */\n";
            
            // Method declaration
            $code .= "    {$visibility}";
            
            if ($static) {
                $code .= " static";
            }
            
            if ($final) {
                $code .= " final";
            }
            
            if ($abstract) {
                $code .= " abstract";
            }
            
            // Constructor doesn't have return type
            if ($name !== $this->diagram['classes'][$this->currentClassIndex]['name']) {
                $code .= " {$returnType}";
            }
            
            $code .= " {$name}(";
            
            // Method parameters
            $paramStrings = [];
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'Object';
                $paramStrings[] = "{$paramType} {$paramName}";
            }
            $code .= implode(", ", $paramStrings);
            
            $code .= ")";
            
            // Method body or abstract declaration
            if ($abstract) {
                $code .= ";\n\n";
            } else {
                $code .= " {\n";
                
                // Constructor body
                if ($name === $this->diagram['classes'][$this->currentClassIndex]['name']) {
                    $code .= "        // Initialize object\n";
                } else {
                    // Generate enhanced method body with Java 8 features
                    if ($stream && !empty($parameters)) {
                        $code .= $this->generateStreamExample($parameters[0] ?? null, $returnType);
                    } elseif ($lambda && !empty($parameters)) {
                        $code .= $this->generateLambdaExample($parameters[0] ?? null, $returnType);
                    } else {
                        // Regular method body
                        if ($returnType !== 'void') {
                            $code .= "        // TODO: Implement method\n";
                            $code .= "        return " . $this->getDefaultReturnValue($returnType) . ";\n";
                        } else {
                            $code .= "        // TODO: Implement method\n";
                        }
                    }
                }
                
                $code .= "    }\n\n";
            }
        }
        
        // Add default constructor if none exists and it's not an interface
        if (!$constructorExists && $this->diagram['classes'][$this->currentClassIndex]['type'] !== 'interface') {
            $code .= "    /**\n";
            $code .= "     * Default constructor\n";
            $code .= "     */\n";
            $code .= "    public " . $this->diagram['classes'][$this->currentClassIndex]['name'] . "() {\n";
            $code .= "        // Initialize object\n";
            $code .= "    }\n\n";
        }
        
        return $code;
    }
    
    /**
     * Generate interface methods with Java 8 default method support
     *
     * @param array $methods
     * @return string
     */
    protected function generateInterfaceMethods(array $methods): string
    {
        $code = "";
        
        foreach ($methods as $method) {
            $name = $method['name'];
            $returnType = isset($method['returnType']) ? $this->mapType($method['returnType']) : 'void';
            $parameters = $method['parameters'] ?? [];
            $default = $method['default'] ?? false; // Java 8 default methods
            $static = $method['static'] ?? false; // Java 8 static methods in interfaces
            
            // Method documentation
            $code .= "    /**\n";
            
            // Parameter documentation
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $code .= "     * @param {$paramName} The {$paramName} parameter\n";
            }
            
            // Return type documentation
            if ($returnType !== 'void') {
                $code .= "     * @return {$returnType}\n";
            }
            
            if ($default) {
                $code .= "     * @implNote Java 8+ default interface method\n";
            }
            if ($static) {
                $code .= "     * @implNote Java 8+ static interface method\n";
            }
            
            $code .= "     */\n";
            
            // Interface method declaration
            if ($static) {
                $code .= "    static {$returnType} {$name}(";
            } elseif ($default) {
                $code .= "    default {$returnType} {$name}(";
            } else {
                $code .= "    {$returnType} {$name}(";
            }
            
            // Method parameters
            $paramStrings = [];
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'Object';
                $paramStrings[] = "{$paramType} {$paramName}";
            }
            $code .= implode(", ", $paramStrings);
            
            if ($default || $static) {
                // Default and static methods have implementation
                $code .= ") {\n";
                if ($returnType !== 'void') {
                    $code .= "        // Default implementation\n";
                    $code .= "        return " . $this->getDefaultReturnValue($returnType) . ";\n";
                } else {
                    $code .= "        // Default implementation\n";
                }
                $code .= "    }\n\n";
            } else {
                // Interface methods don't have implementation
                $code .= ");\n\n";
            }
        }
        
        return $code;
    }
    
    /**
     * Generate enum values from modern enumValues format
     *
     * @param array $enumValues
     * @return string
     */
    protected function generateEnumValues(array $enumValues): string
    {
        $code = "";
        $values = [];
        
        foreach ($enumValues as $enumValue) {
            $name = is_array($enumValue) ? $enumValue['name'] : $enumValue;
            $value = is_array($enumValue) && isset($enumValue['value']) ? $enumValue['value'] : null;
            
            if ($value !== null) {
                // Java enums with values need constructor and field
                $values[] = "    {$name}(\"{$value}\")";
            } else {
                // Simple enum case
                $values[] = "    {$name}";
            }
        }
        
        $code .= implode(",\n", $values);
        
        // Add semicolon if we have values
        if (!empty($values)) {
            $code .= ";\n";
        }
        
        return $code;
    }

    /**
     * Generate enum values from legacy attributes format
     *
     * @param array $attributes
     * @return string
     */
    protected function generateEnumValuesFromAttributes(array $attributes): string
    {
        $code = "";
        $values = [];
        
        foreach ($attributes as $attr) {
            $name = $attr['name'];
            $values[] = "    {$name}";
        }
        
        $code .= implode(",\n", $values);
        
        // Add semicolon if we have values
        if (!empty($values)) {
            $code .= ";\n";
        }
        
        return $code;
    }

    /**
     * Generate necessary import statements
     *
     * @param array $classData
     * @return string
     */
    protected function generateImports(array $classData): string
    {
        $imports = [];
        
        // Check for imports in attributes
        if (!empty($classData['attributes'])) {
            foreach ($classData['attributes'] as $attr) {
                if (isset($attr['type']) && $this->needsImport($attr['type'])) {
                    $imports[] = $this->getImportForType($attr['type']);
                }
            }
        }
        
        // Check for imports in methods (return types and parameter types)
        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                // Return type
                if (isset($method['returnType']) && $this->needsImport($method['returnType'])) {
                    $imports[] = $this->getImportForType($method['returnType']);
                }
                
                // Parameter types
                if (!empty($method['parameters'])) {
                    foreach ($method['parameters'] as $param) {
                        if (isset($param['type']) && $this->needsImport($param['type'])) {
                            $imports[] = $this->getImportForType($param['type']);
                        }
                    }
                }
            }
        }
        
        // Remove duplicates and sort
        $imports = array_unique($imports);
        sort($imports);
        
        return implode("\n", $imports);
    }

    /**
     * Extract base type from generic types and arrays
     *
     * @param string $type
     * @return string
     */
    protected function extractBaseType(string $type): string
    {
        // Remove array brackets
        $baseType = rtrim($type, '[]');
        
        // Extract base type from generics (e.g., "List<String>" -> "List")
        if (preg_match('/^(\w+)(<.*>)?$/', $baseType, $matches)) {
            return $matches[1];
        }
        
        // Extract from fully qualified generics (e.g., "java.util.List<String>" -> "java.util.List")
        if (preg_match('/^([a-zA-Z_][\w.]*?)(<.*>)?$/', $baseType, $matches)) {
            return $matches[1];
        }
        
        return $baseType;
    }

    /**
     * Check if a type needs to be imported
     *
     * @param string $type
     * @return bool
     */
    protected function needsImport(string $type): bool
    {
        // Extract base type from generics and arrays
        $baseType = $this->extractBaseType($type);
        
        // Don't import primitive types, core java.lang.* classes, void, or template parameters
        $noImportTypes = ['boolean', 'byte', 'char', 'double', 'float', 'int', 'long', 'short', 
                          'String', 'Object', 'Class', 'Throwable', 'Exception', 'Error', 'void'];
        
        // Don't import single letter template parameters (K, V, T, etc.)
        if (strlen($baseType) === 1 && ctype_upper($baseType)) {
            return false;
        }
        
        // Don't import primitive or built-in types
        if (in_array($baseType, $noImportTypes)) {
            return false;
        }
        
        // Don't import wrapper types (they're in java.lang)
        $wrapperTypes = ['Boolean', 'Byte', 'Character', 'Double', 'Float', 'Integer', 'Long', 'Short'];
        if (in_array($baseType, $wrapperTypes)) {
            return false;
        }
        
        // Check if it's a fully qualified name that needs an import
        if (strpos($baseType, '.') !== false) {
            // Don't import if it's already java.lang.*
            if (strpos($baseType, 'java.lang.') === 0) {
                return false;
            }
            return true;
        }
        
        // Check if the base type maps to a fully qualified type that needs importing
        $mappedType = self::TYPE_MAPPING[strtolower($baseType)] ?? null;
        if ($mappedType && strpos($mappedType, '.') !== false) {
            // Don't import if it's java.lang.*
            if (strpos($mappedType, 'java.lang.') === 0) {
                return false;
            }
            return true;
        }
        
        return false;
    }

    /**
     * Get import statement for a type
     *
     * @param string $type
     * @return string
     */
    protected function getImportForType(string $type): string
    {
        // Extract base type from generics and arrays
        $baseType = $this->extractBaseType($type);
        
        // For types that already have packages defined
        if (strpos($baseType, '.') !== false) {
            return "import {$baseType};";
        }
        
        // For types that map to specific Java types
        $mappedType = self::TYPE_MAPPING[strtolower($baseType)] ?? null;
        if ($mappedType !== null && strpos($mappedType, '.') !== false) {
            // Extract base type from mapped type as well (remove generics)
            $mappedBaseType = $this->extractBaseType($mappedType);
            return "import {$mappedBaseType};";
        }
        
        // Handle special cases like DateTime which should use java.time
        if (strtolower($baseType) === 'datetime') {
            return "import java.time.LocalDateTime;";
        }
        
        // For other custom types in the same package
        if (!in_array($baseType, ['String', 'Object', 'Integer', 'Boolean', 'Character', 'Byte', 'Short', 'Long', 'Float', 'Double'])) {
            return "import {$this->packageName}.{$baseType};";
        }
        
        return '';
    }
    
    /**
     * Generate lambda expression example for Java 8
     *
     * @param array|null $param
     * @param string $returnType
     * @return string
     */
    protected function generateLambdaExample(?array $param, string $returnType): string
    {
        if ($param === null) {
            return "        // TODO: Implement method\n";
        }
        
        $paramName = $param['name'];
        
        $code = "        // Java 8 Lambda expression example\n";
        $code .= "        Function<String, String> processor = input -> input.toUpperCase();\n";
        $code .= "        Predicate<String> isEmpty = String::isEmpty;\n";
        $code .= "        \n";
        $code .= "        // Using lambda with the parameter\n";
        $code .= "        String processed = processor.apply({$paramName}.toString());\n";
        $code .= "        \n";
        
        if ($returnType !== 'void') {
            if ($returnType === 'String') {
                $code .= "        return processed;\n";
            } else {
                $code .= "        return " . $this->getDefaultReturnValue($returnType) . ";\n";
            }
        }
        
        return $code;
    }
    
    /**
     * Generate Stream API example for Java 8
     *
     * @param array|null $param
     * @param string $returnType
     * @return string
     */
    protected function generateStreamExample(?array $param, string $returnType): string
    {
        if ($param === null) {
            return "        // TODO: Implement method\n";
        }
        
        $paramName = $param['name'];
        
        $code = "        // Java 8 Stream API example\n";
        $code .= "        List<String> items = Arrays.asList(\"one\", \"two\", \"three\");\n";
        $code .= "        \n";
        $code .= "        // Stream processing with lambda expressions\n";
        $code .= "        List<String> result = items.stream()\n";
        $code .= "            .filter(item -> !item.isEmpty())\n";
        $code .= "            .map(String::toUpperCase)\n";
        $code .= "            .sorted()\n";
        $code .= "            .collect(Collectors.toList());\n";
        $code .= "        \n";
        $code .= "        // Optional processing\n";
        $code .= "        Optional<String> first = result.stream().findFirst();\n";
        $code .= "        \n";
        
        if ($returnType !== 'void') {
            if ($returnType === 'String') {
                $code .= "        return first.orElse(\"default\");\n";
            } elseif (strpos($returnType, 'List') === 0) {
                $code .= "        return result;\n";
            } elseif ($returnType === 'Optional') {
                $code .= "        return first;\n";
            } else {
                $code .= "        return " . $this->getDefaultReturnValue($returnType) . ";\n";
            }
        }
        
        return $code;
    }
    
    /**
     * Map UML visibility to Java visibility
     *
     * @param string $visibility
     * @return string
     */
    protected function mapVisibility(string $visibility): string
    {
        switch ($visibility) {
            case 'private':
                return 'private';
            case 'protected':
                return 'protected';
            case 'public':
            default:
                return 'public';
        }
    }
    
    /**
     * Get a default return value for a given Java type
     *
     * @param string $type
     * @return string
     */
    protected function getDefaultReturnValue(string $type): string
    {
        switch ($type) {
            case 'boolean':
                return "false";
            case 'byte':
            case 'short':
            case 'int':
            case 'long':
                return "0";
            case 'float':
                return "0.0f";
            case 'double':
                return "0.0";
            case 'char':
                return "''";
            case 'String':
                return "\"\"";
            case 'Boolean':
                return "Boolean.FALSE";
            case 'Byte':
            case 'Short':
            case 'Integer':
            case 'Long':
                return "0";
            case 'Float':
                return "0.0f";
            case 'Double':
                return "0.0";
            case 'Character':
                return "Character.MIN_VALUE";
            case 'Optional':
                return "Optional.empty()";
            default:
                return "null";
        }
    }
    
    /**
     * Format a default value for a Java type
     *
     * @param string $value
     * @param string $type
     * @return string
     */
    protected function formatDefaultValue(string $value, string $type): string
    {
        switch ($type) {
            case 'float':
                return "{$value}f";
            case 'long':
                return "{$value}L";
            case 'String':
                // Ensure proper quotation for strings
                if ($value[0] !== '"' || $value[strlen($value) - 1] !== '"') {
                    return "\"{$value}\"";
                }
                return $value;
            case 'char':
                // Ensure proper quotation for chars
                if ($value[0] !== "'" || $value[strlen($value) - 1] !== "'") {
                    return "'{$value}'";
                }
                return $value;
            default:
                return $value;
        }
    }
} 
