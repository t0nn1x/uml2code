<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Java;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * Java 21 code generator for class diagrams
 * 
 * New features in Java 21 (LTS):
 * - Pattern matching for switch (JEP 441)
 * - Record patterns (JEP 440)
 * - String templates (Preview - JEP 430)
 * - Virtual threads (JEP 444)
 * - Sequenced collections (JEP 431)
 * - Key encapsulation mechanism API (JEP 452)
 */
class Java21CodeGenerator extends Java17CodeGenerator
{
    /**
     * Enhanced type mapping for Java 21 with sequenced collections and virtual threads
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
        'SequencedList' => 'java.util.SequencedList',
        'SequencedSet' => 'java.util.SequencedSet',
        'SequencedMap' => 'java.util.SequencedMap',
        'SequencedCollection' => 'java.util.SequencedCollection',
        'VirtualThread' => 'java.lang.Thread',
        'StringTemplate' => 'java.lang.StringTemplate', // Preview feature
        'Thread' => 'java.lang.Thread',
    ];

    /**
     * Generate a Java class from the diagram class definition
     * Enhanced with Java 21 features like advanced pattern matching and virtual threads
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
        $virtualThreadsEnabled = $classData['virtualThreads'] ?? false;
        
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
        $code .= " * Generated for Java 21+\n";
        if ($record) {
            $code .= " * Record class with immutable data\n";
        }
        if ($sealed) {
            $code .= " * Sealed class - restricts inheritance\n";
        }
        if ($virtualThreadsEnabled) {
            $code .= " * Virtual threads enabled for concurrent processing\n";
        }
        $code .= " */\n";
        
        // For most types, use parent implementation, but add virtual thread support
        if ($virtualThreadsEnabled && $type === 'class') {
            return $this->generateVirtualThreadClass($classData);
        }
        
        // Use parent implementation for other types
        return parent::generateClass($classData);
    }

    /**
     * Generate methods with Java 21 enhancements
     * Supports advanced pattern matching, string templates, and virtual threads
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
            $stringTemplates = $method['stringTemplates'] ?? false;
            $virtualThread = $method['virtualThread'] ?? false;
            
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
                $code .= "     * @implNote Uses Java 21+ advanced pattern matching\n";
            }
            if ($stringTemplates) {
                $code .= "     * @implNote Uses Java 21+ string templates (preview)\n";
            }
            if ($virtualThread) {
                $code .= "     * @implNote Uses Java 21+ virtual threads\n";
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
                    // Generate enhanced method body
                    if ($virtualThread) {
                        $code .= $this->generateVirtualThreadExample($parameters, $returnType);
                    } elseif ($stringTemplates && !empty($parameters)) {
                        $code .= $this->generateStringTemplateExample($parameters[0] ?? null, $returnType);
                    } elseif ($patternMatching && !empty($parameters)) {
                        $code .= $this->generateAdvancedPatternMatchingExample($parameters[0] ?? null, $returnType);
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
     * Generate class with virtual thread support
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateVirtualThreadClass(array $classData): CodeFile
    {
        $name = $classData['name'];
        $fileName = "{$name}.java";
        $code = "package {$this->packageName};\n\n";
        
        // Add imports for virtual threads
        $code .= "import java.util.concurrent.Executors;\n";
        $code .= "import java.util.concurrent.ExecutorService;\n";
        $code .= "import java.util.concurrent.Future;\n";
        $code .= "import java.util.concurrent.CompletableFuture;\n\n";
        
        // Add other imports
        $imports = $this->generateImports($classData);
        if (!empty($imports)) {
            $code .= $imports . "\n";
        }
        
        // Class documentation
        $code .= "/**\n";
        $code .= " * Class {$name} with virtual thread support\n";
        $code .= " * \n";
        $code .= " * Generated for Java 21+ with virtual threads\n";
        $code .= " */\n";
        
        $code .= "public class {$name}";
        if (!empty($classData['extends'])) {
            $code .= " extends {$classData['extends']}";
        }
        if (!empty($classData['implements'])) {
            $code .= " implements " . implode(', ', $classData['implements']);
        }
        $code .= " {\n\n";
        
        // Add virtual thread executor
        $code .= "    /**\n";
        $code .= "     * Virtual thread executor for concurrent operations\n";
        $code .= "     */\n";
        $code .= "    private static final ExecutorService virtualExecutor = \n";
        $code .= "        Executors.newVirtualThreadPerTaskExecutor();\n\n";
        
        // Generate properties
        if (!empty($classData['attributes'])) {
            $code .= $this->generateProperties($classData['attributes']);
        }
        
        // Generate methods
        if (!empty($classData['methods'])) {
            $code .= $this->generateMethods($classData['methods']);
        }
        
        $code .= "}\n";
        
        $path = $this->outputDirectory;
        $file = new CodeFile($fileName, $path, $code);
        $this->addFile($file);
        
        return $file;
    }

    /**
     * Generate virtual thread example for Java 21
     *
     * @param array $parameters
     * @param string $returnType
     * @return string
     */
    protected function generateVirtualThreadExample(array $parameters, string $returnType): string
    {
        $code = "        // Java 21 Virtual Thread example\n";
        $code .= "        try (var executor = Executors.newVirtualThreadPerTaskExecutor()) {\n";
        $code .= "            var future = executor.submit(() -> {\n";
        $code .= "                // Virtual thread task\n";
        $code .= "                Thread.sleep(100); // Simulate work\n";
        if ($returnType !== 'void') {
            $code .= "                return " . $this->getDefaultReturnValue($returnType) . ";\n";
        }
        $code .= "            });\n";
        $code .= "            \n";
        if ($returnType !== 'void') {
            $code .= "            return future.get();\n";
        } else {
            $code .= "            future.get(); // Wait for completion\n";
        }
        $code .= "        } catch (Exception e) {\n";
        $code .= "            throw new RuntimeException(\"Virtual thread execution failed\", e);\n";
        $code .= "        }\n";
        
        return $code;
    }

    /**
     * Generate string template example for Java 21 (preview feature)
     *
     * @param array|null $param
     * @param string $returnType
     * @return string
     */
    protected function generateStringTemplateExample(?array $param, string $returnType): string
    {
        if ($param === null) {
            return "        // TODO: Implement method\n";
        }
        
        $paramName = $param['name'];
        
        $code = "        // Java 21 String Template example (Preview feature)\n";
        $code .= "        // Note: String templates are a preview feature in Java 21\n";
        $code .= "        var name = \"{$paramName}\";\n";
        $code .= "        var message = STR.\"Hello, \\{name}! Welcome to Java 21.\";\n";
        $code .= "        \n";
        $code .= "        // Multi-line string template\n";
        $code .= "        var multiLineMessage = STR.\"\"\"\n";
        $code .= "            Dear \\{name},\n";
        $code .= "            This is a multi-line string template\n";
        $code .= "            generated with Java 21 features.\n";
        $code .= "            \"\"\";\n";
        $code .= "        \n";
        
        if ($returnType !== 'void') {
            if ($returnType === 'String') {
                $code .= "        return message;\n";
            } else {
                $code .= "        return " . $this->getDefaultReturnValue($returnType) . ";\n";
            }
        }
        
        return $code;
    }

    /**
     * Generate advanced pattern matching example for Java 21
     *
     * @param array|null $param
     * @param string $returnType
     * @return string
     */
    protected function generateAdvancedPatternMatchingExample(?array $param, string $returnType): string
    {
        if ($param === null) {
            return "        // TODO: Implement method\n";
        }
        
        $paramName = $param['name'];
        
        $code = "        // Java 21 Advanced Pattern Matching\n";
        $code .= "        return switch ({$paramName}) {\n";
        $code .= "            // Pattern matching with guard conditions\n";
        $code .= "            case String s when s.length() > 10 -> {\n";
        $code .= "                yield \"Long string: \" + s.substring(0, 10) + \"...\";\n";
        $code .= "            }\n";
        $code .= "            case String s when s.isEmpty() -> \"Empty string\";\n";
        $code .= "            case String s -> \"String: \" + s;\n";
        $code .= "            \n";
        $code .= "            // Record pattern matching (if records are available)\n";
        $code .= "            case null -> \"null value\";\n";
        $code .= "            \n";
        $code .= "            // Number patterns\n";
        $code .= "            case Integer i when i < 0 -> \"Negative: \" + i;\n";
        $code .= "            case Integer i when i == 0 -> \"Zero\";\n";
        $code .= "            case Integer i -> \"Positive: \" + i;\n";
        $code .= "            \n";
        $code .= "            // Default case\n";
        $code .= "            default -> {\n";
        $code .= "                var type = {$paramName}.getClass().getSimpleName();\n";
        if ($returnType === 'String') {
            $code .= "                yield \"Unknown type: \" + type;\n";
        } else {
            $code .= "                yield " . $this->getDefaultReturnValue($returnType) . ";\n";
        }
        $code .= "            }\n";
        $code .= "        };\n";
        
        return $code;
    }

    /**
     * Enhanced record methods generation for Java 21
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
            $patternMatching = $method['patternMatching'] ?? false;
            
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
                $code .= "     * @implNote Uses Java 21+ pattern matching\n";
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
            
            // Record method body with Java 21 features
            if ($patternMatching && !empty($parameters)) {
                $code .= $this->generateAdvancedPatternMatchingExample($parameters[0] ?? null, $returnType);
            } else {
                if ($returnType !== 'void') {
                    $code .= "        // TODO: Implement record method\n";
                    $code .= "        return " . $this->getDefaultReturnValue($returnType) . ";\n";
                } else {
                    $code .= "        // TODO: Implement record method\n";
                }
            }
            
            $code .= "    }\n\n";
        }
        
        return $code;
    }
} 
