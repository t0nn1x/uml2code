<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Java;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * Java 17 code generator for class diagrams
 * 
 * New features in Java 17 (LTS):
 * - Records
 * - Sealed classes and interfaces
 * - Pattern matching for instanceof
 * - Text blocks (from Java 13-15)
 * - Switch expressions (from Java 12-14)
 * - var keyword improvements
 */
class Java17CodeGenerator extends Java11CodeGenerator
{
    /**
     * Enhanced type mapping for Java 17 with pattern matching support
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
        'var' => 'var', // Java 10+ local variable type inference
    ];

    /**
     * Generate a Java class from the diagram class definition
     * Enhanced with Java 17 features like records and sealed classes
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        $type = $classData['type'] ?? 'class';
        $name = $classData['name'];
        $record = $classData['record'] ?? false;
        $sealed = $classData['sealed'] ?? false;
        $permits = $classData['permits'] ?? [];
        
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
        $code .= " * Generated for Java 17+\n";
        if ($record) {
            $code .= " * Record class with immutable data\n";
        }
        if ($sealed) {
            $code .= " * Sealed class - restricts inheritance\n";
        }
        $code .= " */\n";
        
        // Class declaration
        switch ($type) {
            case 'interface':
                $code .= "public";
                if ($sealed) {
                    $code .= " sealed";
                }
                $code .= " interface {$name}";
                
                // Add generic type parameters if present
                if (!empty($classData['typeParameters'])) {
                    $code .= "<" . implode(', ', $classData['typeParameters']) . ">";
                }
                
                if (!empty($classData['extends'])) {
                    $code .= " extends {$classData['extends']}";
                }
                if ($sealed && !empty($permits)) {
                    $code .= " permits " . implode(', ', $permits);
                }
                $code .= " {\n";
                
                // Generate interface methods
                if (!empty($classData['methods'])) {
                    $code .= $this->generateInterfaceMethods($classData['methods']);
                }
                break;
                
            case 'enum':
                return parent::generateClass($classData); // Use parent implementation
                
            case 'record':
                // Java 17 records
                $code .= "public record {$name}(";
                
                // Record components (parameters)
                if (!empty($classData['attributes'])) {
                    $components = [];
                    foreach ($classData['attributes'] as $attr) {
                        $attrName = $attr['name'];
                        $attrType = isset($attr['type']) ? $this->mapType($attr['type']) : 'Object';
                        $components[] = "{$attrType} {$attrName}";
                    }
                    $code .= implode(', ', $components);
                }
                
                $code .= ")";
                
                if (!empty($classData['implements'])) {
                    $code .= " implements " . implode(', ', $classData['implements']);
                }
                
                $code .= " {\n";
                
                // Records can have additional methods
                if (!empty($classData['methods'])) {
                    $code .= $this->generateRecordMethods($classData['methods']);
                }
                break;
                
            case 'abstract':
                $code .= "public";
                if ($sealed) {
                    $code .= " sealed";
                }
                $code .= " abstract class {$name}";
                
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
                if ($sealed && !empty($permits)) {
                    $code .= " permits " . implode(', ', $permits);
                }
                $code .= " {\n";
                
                // Generate properties
                if (!empty($classData['attributes'])) {
                    $code .= $this->generateProperties($classData['attributes']);
                }
                
                // Generate methods
                if (!empty($classData['methods'])) {
                    $code .= $this->generateMethods($classData['methods']);
                }
                break;
                
            default: // regular class
                $code .= "public";
                if ($sealed) {
                    $code .= " sealed";
                }
                $code .= " class {$name}";
                
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
                if ($sealed && !empty($permits)) {
                    $code .= " permits " . implode(', ', $permits);
                }
                $code .= " {\n";
                
                // Generate properties
                if (!empty($classData['attributes'])) {
                    $code .= $this->generateProperties($classData['attributes']);
                }
                
                // Generate methods
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
     * Generate methods with Java 17 enhancements
     * Supports pattern matching and switch expressions
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
            $patternMatching = $method['patternMatching'] ?? false;
            
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
            
            if ($patternMatching) {
                $code .= "     * @implNote Uses Java 17+ pattern matching\n";
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
            
            // Method parameters with enhanced parameter handling
            $paramStrings = [];
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'Object';
                
                // Support for var in method parameters (limited cases)
                if ($paramType === 'var' && isset($param['inferredType'])) {
                    $paramType = $this->mapType($param['inferredType']);
                }
                
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
                    // Generate enhanced method body with pattern matching if specified
                    if ($patternMatching && !empty($parameters)) {
                        $code .= $this->generatePatternMatchingExample($parameters[0] ?? null, $returnType);
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
     * Generate methods for records (Java 17)
     *
     * @param array $methods
     * @return string
     */
    protected function generateRecordMethods(array $methods): string
    {
        $code = "";
        
        foreach ($methods as $method) {
            $name = $method['name'];
            $visibility = $this->mapVisibility($method['visibility'] ?? 'public');
            $returnType = isset($method['returnType']) ? $this->mapType($method['returnType']) : 'void';
            $parameters = $method['parameters'] ?? [];
            $static = $method['static'] ?? false;
            
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
            
            $code .= "     */\n";
            
            // Method declaration
            $code .= "    {$visibility}";
            
            if ($static) {
                $code .= " static";
            }
            
            $code .= " {$returnType} {$name}(";
            
            // Method parameters
            $paramStrings = [];
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'Object';
                $paramStrings[] = "{$paramType} {$paramName}";
            }
            $code .= implode(", ", $paramStrings);
            
            $code .= ") {\n";
            
            // Record method body
            if ($returnType !== 'void') {
                $code .= "        // TODO: Implement record method\n";
                $code .= "        return " . $this->getDefaultReturnValue($returnType) . ";\n";
            } else {
                $code .= "        // TODO: Implement record method\n";
            }
            
            $code .= "    }\n\n";
        }
        
        return $code;
    }

    /**
     * Generate pattern matching example for Java 17
     *
     * @param array|null $param
     * @param string $returnType
     * @return string
     */
    protected function generatePatternMatchingExample(?array $param, string $returnType): string
    {
        if ($param === null) {
            return "        // TODO: Implement method\n";
        }
        
        $paramName = $param['name'];
        $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'Object';
        
        $code = "        // Example of Java 17 pattern matching\n";
        $code .= "        if ({$paramName} instanceof String s) {\n";
        $code .= "            // Pattern variable 's' is available here\n";
        if ($returnType !== 'void') {
            $code .= "            return " . $this->getDefaultReturnValue($returnType) . ";\n";
        }
        $code .= "        }\n";
        $code .= "        \n";
        $code .= "        // Switch expression example\n";
        $code .= "        var result = switch ({$paramName}) {\n";
        $code .= "            case String s -> \"String: \" + s;\n";
        $code .= "            case Integer i -> \"Integer: \" + i;\n";
        $code .= "            case null -> \"null value\";\n";
        $code .= "            default -> \"Unknown type\";\n";
        $code .= "        };\n";
        $code .= "        \n";
        
        if ($returnType !== 'void') {
            $code .= "        return " . $this->getDefaultReturnValue($returnType) . ";\n";
        }
        
        return $code;
    }

    /**
     * Enhanced interface methods generation with Java 17 features
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
            $default = $method['default'] ?? false;
            $static = $method['static'] ?? false;
            $private = $method['private'] ?? false; // Java 9+ private interface methods
            
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
            
            $code .= "     */\n";
            
            // Method declaration
            if ($private) {
                $code .= "    private {$returnType} {$name}(";
            } elseif ($static) {
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
            
            if ($default || $static || $private) {
                // Methods with implementation
                $code .= ") {\n";
                if ($returnType !== 'void') {
                    $code .= "        // Implementation\n";
                    $code .= "        return " . $this->getDefaultReturnValue($returnType) . ";\n";
                } else {
                    $code .= "        // Implementation\n";
                }
                $code .= "    }\n\n";
            } else {
                // Abstract interface methods
                $code .= ");\n\n";
            }
        }
        
        return $code;
    }
} 
