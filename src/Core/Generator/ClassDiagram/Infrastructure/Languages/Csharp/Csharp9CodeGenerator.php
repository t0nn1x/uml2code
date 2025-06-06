<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * C# 9.0 code generator for class diagrams
 * 
 * New features in C# 9.0:
 * - Records
 * - Init-only properties
 * - Top-level programs
 * - Pattern matching enhancements (relational patterns, logical patterns)
 * - Target-typed new expressions
 * - Covariant returns
 * - Native sized integers
 * - Function pointers
 * - Static anonymous functions
 * - Module initializers
 */
class Csharp9CodeGenerator extends Csharp8CodeGenerator
{
    /**
     * Generate a C# class with C# 9.0 features
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        $type = $classData['type'] ?? 'class';
        $name = $classData['name'];
        
        // Check if this is a record type
        if ($type === 'record') {
            return $this->generateRecord($classData);
        }
        
        return parent::generateClass($classData);
    }
    
    /**
     * Generate a C# 9.0 record
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateRecord(array $classData): CodeFile
    {
        $name = $classData['name'];
        $fileName = "{$name}.cs";
        $namespace = $this->namespace;
        
        // Enable nullable reference types
        $code = "#nullable enable\n\n";
        
        $code .= "using System;\n";
        $code .= "using System.Collections.Generic;\n";
        $code .= "using System.Linq;\n";
        
        // Add additional imports
        $imports = $this->generateImports($classData);
        if (!empty($imports)) {
            $code .= $imports;
        }
        
        $code .= "\nnamespace {$namespace}\n{\n";
        
        // Record documentation
        $code .= "    /// <summary>\n";
        $code .= "    /// Record {$name}\n";
        $code .= "    /// </summary>\n";
        
        // Generate attributes
        if (!empty($classData['annotations'])) {
            $code .= $this->generateAttributes($classData['annotations']);
        }
        
        // Record declaration
        $code .= "    public record {$name}";
        
        // Handle generics
        if (!empty($classData['typeParameters'])) {
            $code .= "<" . implode(", ", $classData['typeParameters']) . ">";
        }
        
        // Positional record with primary constructor
        if (!empty($classData['primaryConstructor'])) {
            $params = [];
            foreach ($classData['primaryConstructor'] as $param) {
                $paramType = $this->mapType($param['type']);
                $paramName = $param['name'];
                $params[] = "{$paramType} {$paramName}";
            }
            $code .= "(" . implode(", ", $params) . ")";
        }
        
        // Handle inheritance
        if (!empty($classData['extends'])) {
            $code .= " : {$classData['extends']}";
        }
        
        // Generic constraints
        if (!empty($classData['typeConstraints'])) {
            foreach ($classData['typeConstraints'] as $constraint) {
                $code .= "\n        where " . $constraint;
            }
        }
        
        $code .= "\n    {\n";
        
        // Additional properties (beyond primary constructor)
        if (!empty($classData['attributes'])) {
            $code .= $this->generateRecordProperties($classData['attributes']);
        }
        
        // Methods
        if (!empty($classData['methods'])) {
            $code .= $this->generateMethods($classData['methods'], 'record');
        }
        
        $code .= "    }\n}\n";
        
        $path = $this->outputDirectory;
        $file = new CodeFile($fileName, $path, $code);
        $this->addFile($file);
        
        return $file;
    }
    
    /**
     * Generate properties with C# 9.0 init-only setters
     *
     * @param array $attributes
     * @param string $classType
     * @return string
     */
    protected function generateProperties(array $attributes, string $classType = 'class'): string
    {
        $code = "";
        
        foreach ($attributes as $attr) {
            $name = $attr['name'];
            $visibility = $this->mapVisibility($attr['visibility'] ?? 'public');
            $type = isset($attr['type']) ? $this->mapType($attr['type']) : 'object';
            $isStatic = $attr['static'] ?? false;
            $isReadonly = $attr['readonly'] ?? false;
            $isInitOnly = $attr['initOnly'] ?? false;
            $isNullable = $attr['nullable'] ?? false;
            $defaultValue = $attr['defaultValue'] ?? null;
            
            // Property documentation
            if (!empty($attr['description'])) {
                $code .= "        /// <summary>\n";
                $code .= "        /// {$attr['description']}\n";
                $code .= "        /// </summary>\n";
            }
            
            // Property attributes
            if (!empty($attr['annotations'])) {
                foreach ($attr['annotations'] as $annotation) {
                    $code .= "        [{$annotation}]\n";
                }
            }
            
            // Handle nullable reference types
            if ($isNullable && $this->isReferenceType($type)) {
                $type .= "?";
            }

            if ($classType === 'interface') {
                // Interface properties
                $code .= "        {$type} {$name}";
                if ($isReadonly) {
                    $code .= " { get; }";
                } else {
                    $code .= " { get; set; }";
                }
            } else {
                // Class properties
                $code .= "        {$visibility} ";
                
                if ($isStatic) {
                    $code .= "static ";
                }
                
                $code .= "{$type} {$name}";
                
                // C# 9.0 init-only properties
                if ($isInitOnly) {
                    $code .= " { get; init; }";
                } elseif ($isReadonly) {
                    $code .= " { get; }";
                } else {
                    $code .= " { get; set; }";
                }
            }
            
            if ($defaultValue !== null) {
                $code .= " = {$defaultValue};";
            } elseif (!$isNullable && $this->isReferenceType($type)) {
                // Initialize non-nullable reference types
                $code .= " = default!;";
            }
            
            $code .= "\n\n";
        }
        
        return $code;
    }
    
    /**
     * Generate record properties
     *
     * @param array $attributes
     * @return string
     */
    protected function generateRecordProperties(array $attributes): string
    {
        $code = "";
        
        foreach ($attributes as $attr) {
            $name = $attr['name'];
            $visibility = $this->mapVisibility($attr['visibility'] ?? 'public');
            $type = isset($attr['type']) ? $this->mapType($attr['type']) : 'object';
            $isNullable = $attr['nullable'] ?? false;
            $defaultValue = $attr['defaultValue'] ?? null;
            
            // Property documentation
            if (!empty($attr['description'])) {
                $code .= "        /// <summary>\n";
                $code .= "        /// {$attr['description']}\n";
                $code .= "        /// </summary>\n";
            }
            
            // Handle nullable reference types
            if ($isNullable && $this->isReferenceType($type)) {
                $type .= "?";
            }
            
            // Records use init-only properties by default
            $code .= "        {$visibility} {$type} {$name} { get; init; }";
            
            if ($defaultValue !== null) {
                $code .= " = {$defaultValue};";
            } elseif (!$isNullable && $this->isReferenceType($type)) {
                $code .= " = default!;";
            }
            
            $code .= "\n\n";
        }
        
        return $code;
    }
    
    /**
     * Generate methods with C# 9.0 enhancements
     *
     * @param array $methods
     * @param string $classType
     * @return string
     */
    protected function generateMethods(array $methods, string $classType): string
    {
        $code = "";
        
        foreach ($methods as $method) {
            $name = $method['name'];
            $visibility = $this->mapVisibility($method['visibility'] ?? 'public');
            $returnType = isset($method['returnType']) ? $this->mapType($method['returnType']) : 'void';
            $parameters = $method['parameters'] ?? [];
            $isStatic = $method['static'] ?? false;
            $isVirtual = $method['virtual'] ?? false;
            $isOverride = $method['override'] ?? false;
            $isAbstract = $method['abstract'] ?? false;
            $isAsync = $method['async'] ?? false;
            
            // Method documentation
            if (!empty($method['description']) || !empty($parameters) || $returnType !== 'void') {
                $code .= "        /// <summary>\n";
                if (!empty($method['description'])) {
                    $code .= "        /// {$method['description']}\n";
                }
                $code .= "        /// </summary>\n";
                
                // Parameter documentation
                foreach ($parameters as $param) {
                    $paramDescription = isset($param['description']) ? $param['description'] : 'The ' . $param['name'] . ' parameter';
                    $code .= "        /// <param name=\"{$param['name']}\">{$paramDescription}</param>\n";
                }
                
                // Return documentation
                if ($returnType !== 'void') {
                    $returnDescription = isset($method['returnDescription']) ? $method['returnDescription'] : 'The result';
                    $code .= "        /// <returns>{$returnDescription}</returns>\n";
                }
            }
            
            // Method attributes
            if (!empty($method['annotations'])) {
                foreach ($method['annotations'] as $annotation) {
                    $code .= "        [{$annotation}]\n";
                }
            }
            
            // Method declaration
            $code .= "        {$visibility} ";
            
            if ($isStatic) {
                $code .= "static ";
            }
            
            if ($isVirtual && !$isOverride) {
                $code .= "virtual ";
            }
            
            if ($isOverride) {
                $code .= "override ";
            }
            
            if ($isAbstract) {
                $code .= "abstract ";
            }
            
            if ($isAsync) {
                $code .= "async ";
            }
            
            $code .= "{$returnType} {$name}(";
            
            // Method parameters
            $paramStrings = [];
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'object';
                $paramModifier = $param['modifier'] ?? '';
                $paramNullable = $param['nullable'] ?? false;
                
                $paramStr = "";
                
                // Parameter modifiers
                if (!empty($paramModifier)) {
                    $paramStr .= "{$paramModifier} ";
                }
                
                // Handle nullable parameters
                if ($paramNullable && $this->isReferenceType($paramType)) {
                    $paramType .= "?";
                }
                
                $paramStr .= "{$paramType} {$paramName}";
                
                // Default value
                if (isset($param['defaultValue'])) {
                    $paramStr .= " = {$param['defaultValue']}";
                }
                
                $paramStrings[] = $paramStr;
            }
            
            $code .= implode(", ", $paramStrings);
            $code .= ")";
            
            // Method body
            if ($isAbstract) {
                $code .= ";\n\n";
            } else {
                $code .= "\n        {\n";
                
                // C# 9.0 pattern matching example
                if (!empty($parameters) && ($method['usePatternMatching'] ?? false)) {
                    $firstParam = $parameters[0];
                    $code .= "            // C# 9.0 pattern matching with relational patterns\n";
                    $code .= "            var category = {$firstParam['name']} switch\n";
                    $code .= "            {\n";
                    $code .= "                < 0 => \"negative\",\n";
                    $code .= "                0 => \"zero\",\n";
                    $code .= "                > 0 and <= 10 => \"small positive\",\n";
                    $code .= "                > 10 and <= 100 => \"medium positive\",\n";
                    $code .= "                > 100 => \"large positive\"\n";
                    $code .= "            };\n\n";
                }
                
                // C# 9.0 target-typed new expression
                if ($returnType !== 'void' && $this->isInstantiableType($returnType)) {
                    $code .= "            // C# 9.0 target-typed new expression\n";
                    $code .= "            return new();\n";
                } elseif ($returnType !== 'void') {
                    $code .= "            // TODO: Implement method\n";
                    if ($isAsync && strpos($returnType, 'Task') !== false) {
                        $code .= "            return await Task.FromResult(" . $this->getDefaultReturnValue($this->getTaskReturnType($returnType)) . ");\n";
                    } else {
                        $code .= "            return " . $this->getDefaultReturnValue($returnType) . ";\n";
                    }
                } else {
                    $code .= "            // TODO: Implement method\n";
                }
                
                $code .= "        }\n\n";
            }
        }
        
        return $code;
    }
    
    /**
     * Check if a type can be instantiated with new()
     *
     * @param string $type
     * @return bool
     */
    protected function isInstantiableType(string $type): bool
    {
        $nonInstantiableTypes = [
            'void', 'int', 'uint', 'long', 'ulong', 'short', 'ushort',
            'byte', 'sbyte', 'bool', 'char', 'float', 'double', 'decimal',
            'string', 'object', 'dynamic'
        ];
        
        // Remove generics and array notation
        $baseType = preg_replace('/[\[\]<>].*/', '', $type);
        
        return !in_array($baseType, $nonInstantiableTypes);
    }
} 
