<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * C# 8.0 code generator for class diagrams
 * 
 * New features in C# 8.0:
 * - Nullable reference types
 * - Async streams (IAsyncEnumerable)
 * - Default interface methods
 * - Pattern matching enhancements (switch expressions)
 * - Using declarations
 * - Static local functions
 * - Disposable ref structs
 * - Null-coalescing assignment (??=)
 * - Readonly members
 * - Indices and ranges
 */
class Csharp8CodeGenerator extends Csharp7CodeGenerator
{
    /**
     * Generate a C# class with C# 8.0 features
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        $type = $classData['type'] ?? 'class';
        $name = $classData['name'];
        
        $fileName = "{$name}.cs";
        $namespace = $this->namespace;
        
        // Enable nullable reference types for C# 8.0
        $code = "#nullable enable\n\n";
        
        $code .= "using System;\n";
        $code .= "using System.Collections.Generic;\n";
        $code .= "using System.Linq;\n";
        
        // Add additional imports
        $imports = $this->generateImports($classData);
        if (!empty($imports)) {
            $code .= $imports;
        }
        
        // Add async enumerable support if needed
        if ($this->usesAsyncEnumerable($classData)) {
            $code .= "using System.Collections.Generic;\n";
            $code .= "using System.Threading;\n";
        }
        
        $code .= "\nnamespace {$namespace}\n{\n";
        
        // Class documentation
        $code .= "    /// <summary>\n";
        $code .= "    /// " . ucfirst($type) . " {$name}\n";
        $code .= "    /// </summary>\n";
        
        // Generate attributes (C# attributes/annotations)
        if (!empty($classData['annotations'])) {
            $code .= $this->generateAttributes($classData['annotations']);
        }
        
        // Class declaration
        $code .= "    ";
        
        // Add modifiers
        if ($classData['sealed'] ?? false) {
            $code .= "sealed ";
        }
        if ($classData['abstract'] ?? false) {
            $code .= "abstract ";
        }
        if ($classData['static'] ?? false) {
            $code .= "static ";
        }
        
        switch ($type) {
            case 'interface':
                $code .= "public interface {$name}";
                break;
                
            case 'enum':
                $code .= "public enum {$name}";
                if (!empty($classData['enumType'])) {
                    $code .= " : " . $classData['enumType'];
                }
                break;
                
            case 'struct':
                $code .= "public ";
                if ($classData['readonly'] ?? false) {
                    $code .= "readonly ";
                }
                $code .= "struct {$name}";
                break;
                
            default: // class
                $code .= "public ";
                if ($classData['partial'] ?? false) {
                    $code .= "partial ";
                }
                $code .= "class {$name}";
                break;
        }
        
        // Handle generics
        if (!empty($classData['typeParameters'])) {
            $code .= "<" . implode(", ", $classData['typeParameters']) . ">";
        }
        
        // Handle inheritance
        $inheritance = [];
        if (!empty($classData['extends'])) {
            $inheritance[] = $classData['extends'];
        }
        if (!empty($classData['implements'])) {
            $inheritance = array_merge($inheritance, $classData['implements']);
        }
        if (!empty($inheritance)) {
            $code .= " : " . implode(", ", $inheritance);
        }
        
        // Generic constraints
        if (!empty($classData['typeConstraints'])) {
            foreach ($classData['typeConstraints'] as $constraint) {
                $code .= "\n        where " . $constraint;
            }
        }
        
        $code .= "\n    {\n";
        
        // Generate enum values
        if ($type === 'enum') {
            $code .= $this->generateEnumValues($classData);
        } else {
            // Generate fields/properties
            if (!empty($classData['attributes'])) {
                $code .= $this->generateProperties($classData['attributes'], $type);
            }
            
            // Generate methods (including default interface methods for C# 8.0)
            if (!empty($classData['methods'])) {
                $code .= $this->generateMethods($classData['methods'], $type);
            }
        }
        
        $code .= "    }\n}\n";
        
        $path = $this->outputDirectory;
        $file = new CodeFile($fileName, $path, $code);
        $this->addFile($file);
        
        return $file;
    }
    
    /**
     * Generate properties with C# 8.0 nullable reference types
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
                // Interface properties (C# 8.0 style)
                $code .= "        {$type} {$name}";
                if ($isReadonly) {
                    $code .= " { get; }";
                } else {
                    $code .= " { get; set; }";
                }
                
                // C# 8.0 default interface members
                if ($defaultValue !== null) {
                    $code .= " => {$defaultValue};";
                }
            } else {
                // Class properties
                $code .= "        {$visibility} ";
                
                if ($isStatic) {
                    $code .= "static ";
                }
                
                // C# 8.0 readonly members
                if ($isReadonly) {
                    $code .= "readonly ";
                }
                
                $code .= "{$type} {$name}";
                
                if ($isReadonly) {
                    $code .= " { get; }";
                } else {
                    $code .= " { get; set; }";
                }
                
                if ($defaultValue !== null) {
                    $code .= " = {$defaultValue};";
                } elseif (!$isNullable && $this->isReferenceType($type)) {
                    // Initialize non-nullable reference types
                    $code .= " = default!;";
                }
            }
            
            $code .= "\n\n";
        }
        
        return $code;
    }
    
    /**
     * Generate methods with C# 8.0 enhancements
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
            $isAsyncEnumerable = $method['asyncEnumerable'] ?? false;
            $isDefaultInterfaceMethod = $method['defaultInterfaceMethod'] ?? false;
            $isReadonly = $method['readonly'] ?? false;
            
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
            
            if ($isAbstract && $classType !== 'interface') {
                $code .= "abstract ";
            }
            
            if ($isAsync) {
                $code .= "async ";
            }
            
            // C# 8.0 readonly members
            if ($isReadonly) {
                $code .= "readonly ";
            }
            
            // Handle async enumerable return type
            if ($isAsyncEnumerable) {
                $returnType = "IAsyncEnumerable<{$returnType}>";
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
            if ($classType === 'interface' && !$isDefaultInterfaceMethod) {
                $code .= ";\n\n";
            } elseif ($isAbstract && $classType !== 'interface') {
                $code .= ";\n\n";
            } else {
                $code .= "\n        {\n";
                
                // C# 8.0 using declaration example
                if ($method['hasUsing'] ?? false) {
                    $code .= "            // C# 8.0 using declaration\n";
                    $code .= "            using var resource = new System.IO.MemoryStream();\n\n";
                }
                
                // C# 8.0 switch expression example
                if (!empty($parameters) && ($method['useSwitchExpression'] ?? false)) {
                    $firstParam = $parameters[0];
                    $code .= "            // C# 8.0 switch expression\n";
                    $code .= "            var result = {$firstParam['name']} switch\n";
                    $code .= "            {\n";
                    $code .= "                null => \"null value\",\n";
                    $code .= "                \"\" => \"empty string\",\n";
                    $code .= "                _ => \"other value\"\n";
                    $code .= "            };\n\n";
                }
                
                if ($isAsyncEnumerable) {
                    $code .= "            // C# 8.0 async enumerable\n";
                    $code .= "            await foreach (var item in GetItemsAsync())\n";
                    $code .= "            {\n";
                    $code .= "                yield return item;\n";
                    $code .= "            }\n";
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
                
                // Helper method for async enumerable
                if ($isAsyncEnumerable) {
                    $code .= "        private async IAsyncEnumerable<{$returnType}> GetItemsAsync()\n";
                    $code .= "        {\n";
                    $code .= "            await Task.Delay(100);\n";
                    $code .= "            yield return " . $this->getDefaultReturnValue($returnType) . ";\n";
                    $code .= "        }\n\n";
                }
            }
        }
        
        return $code;
    }
    
    /**
     * Check if the class uses async enumerable
     *
     * @param array $classData
     * @return bool
     */
    protected function usesAsyncEnumerable(array $classData): bool
    {
        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                if ($method['asyncEnumerable'] ?? false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if a type is a reference type
     *
     * @param string $type
     * @return bool
     */
    protected function isReferenceType(string $type): bool
    {
        $valueTypes = [
            'bool', 'byte', 'sbyte', 'char', 'decimal', 'double', 'float', 
            'int', 'uint', 'long', 'ulong', 'short', 'ushort', 'void',
            'DateTime', 'TimeSpan', 'DateTimeOffset', 'Guid'
        ];
        
        // Remove array notation and generics for checking
        $baseType = preg_replace('/[\[\]<>].*/', '', $type);
        
        return !in_array($baseType, $valueTypes);
    }
} 
