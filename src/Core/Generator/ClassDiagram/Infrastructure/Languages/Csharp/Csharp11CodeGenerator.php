<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * C# 11.0 code generator for class diagrams
 * 
 * New features in C# 11.0:
 * - Raw string literals
 * - Generic math support
 * - Generic attributes
 * - UTF-8 string literals
 * - Newlines in string interpolation expressions
 * - List patterns
 * - File-local types
 * - Required members
 * - Auto-default structs
 * - Pattern match Span<char> on a constant string
 * - Extended nameof scope
 * - Numeric IntPtr
 * - ref fields and scoped ref
 * - Improved method group conversion to delegate
 */
class Csharp11CodeGenerator extends Csharp10CodeGenerator
{
    /**
     * Generate properties with C# 11.0 required members
     *
     * @param array $attributes
     * @param string $indent
     * @return string
     */
    protected function generatePropertiesWithIndent(array $attributes, string $indent): string
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
            $isRequired = $attr['required'] ?? false;
            $defaultValue = $attr['defaultValue'] ?? null;
            
            // Check for required annotation in UML annotations
            if (!$isRequired && !empty($attr['annotations'])) {
                foreach ($attr['annotations'] as $annotation) {
                    if (strtolower(trim($annotation, '<>')) === 'required') {
                        $isRequired = true;
                        break;
                    }
                }
            }
            
            // Property documentation
            if (!empty($attr['description'])) {
                $code .= "{$indent}/// <summary>\n";
                $code .= "{$indent}/// {$attr['description']}\n";
                $code .= "{$indent}/// </summary>\n";
            }
            
            // Property attributes (skip 'required' annotation since it becomes a keyword)
            if (!empty($attr['annotations'])) {
                foreach ($attr['annotations'] as $annotation) {
                    if (strtolower(trim($annotation, '<>')) !== 'required') {
                        $code .= "{$indent}[{$annotation}]\n";
                    }
                }
            }
            
            $code .= "{$indent}{$visibility} ";
            
            if ($isStatic) {
                $code .= "static ";
            }
            
            // C# 11.0 required members
            if ($isRequired) {
                $code .= "required ";
            }
            
            // Handle nullable reference types
            if ($isNullable && $this->isReferenceType($type)) {
                $type .= "?";
            }
            
            $code .= "{$type} {$name}";
            
            if ($isInitOnly) {
                $code .= " { get; init; }";
            } elseif ($isReadonly) {
                $code .= " { get; }";
            } else {
                $code .= " { get; set; }";
            }
            
            if ($defaultValue !== null) {
                $code .= " = {$defaultValue};";
            } elseif (!$isNullable && $this->isReferenceType($type) && !$isRequired) {
                $code .= " = default!;";
            }
            
            $code .= "\n\n";
        }
        
        return $code;
    }
    
    /**
     * Generate methods with C# 11.0 enhancements
     *
     * @param array $methods
     * @param string $classType
     * @param string $indent
     * @return string
     */
    protected function generateMethodsWithIndent(array $methods, string $classType, string $indent): string
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
                $code .= "{$indent}/// <summary>\n";
                if (!empty($method['description'])) {
                    $code .= "{$indent}/// {$method['description']}\n";
                }
                $code .= "{$indent}/// </summary>\n";
                
                // Parameter documentation
                foreach ($parameters as $param) {
                    $paramDescription = isset($param['description']) ? $param['description'] : 'The ' . $param['name'] . ' parameter';
                    $code .= "{$indent}/// <param name=\"{$param['name']}\">{$paramDescription}</param>\n";
                }
                
                // Return documentation
                if ($returnType !== 'void') {
                    $returnDescription = isset($method['returnDescription']) ? $method['returnDescription'] : 'The result';
                    $code .= "{$indent}/// <returns>{$returnDescription}</returns>\n";
                }
            }
            
            // Method attributes (C# 11 supports generic attributes)
            if (!empty($method['annotations'])) {
                foreach ($method['annotations'] as $annotation) {
                    $code .= "{$indent}[{$annotation}]\n";
                }
            }
            
            // Method declaration
            $code .= "{$indent}{$visibility} ";
            
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
                
                // Parameter modifiers (including C# 11 scoped)
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
                $code .= "\n{$indent}{\n";
                
                // C# 11 raw string literals example
                if ($method['useRawStringLiterals'] ?? false) {
                    $code .= "{$indent}    // C# 11 raw string literals\n";
                    $code .= "{$indent}    var json = \"\"\"\n";
                    $code .= "{$indent}        {\n";
                    $code .= "{$indent}            \"name\": \"{$name}\",\n";
                    $code .= "{$indent}            \"type\": \"method\"\n";
                    $code .= "{$indent}        }\n";
                    $code .= "{$indent}        \"\"\";\n\n";
                }
                
                // C# 11 UTF-8 string literals
                if ($method['useUtf8Literals'] ?? false) {
                    $code .= "{$indent}    // C# 11 UTF-8 string literals\n";
                    $code .= "{$indent}    ReadOnlySpan<byte> utf8 = \"Hello UTF-8\"u8;\n\n";
                }
                
                // C# 11 list patterns example
                if (!empty($parameters) && ($method['useListPatterns'] ?? false)) {
                    $code .= "{$indent}    // C# 11 list patterns\n";
                    $code .= "{$indent}    int[] numbers = { 1, 2, 3, 4, 5 };\n";
                    $code .= "{$indent}    var result = numbers switch\n";
                    $code .= "{$indent}    {\n";
                    $code .= "{$indent}        [] => \"Empty\",\n";
                    $code .= "{$indent}        [var first] => \$\"One element: {first}\",\n";
                    $code .= "{$indent}        [var first, var second] => \$\"Two elements: {first}, {second}\",\n";
                    $code .= "{$indent}        [var first, .., var last] => \$\"First: {first}, Last: {last}\",\n";
                    $code .= "{$indent}        _ => \"Multiple elements\"\n";
                    $code .= "{$indent}    };\n\n";
                }
                
                if ($returnType !== 'void') {
                    $code .= "{$indent}    // TODO: Implement method\n";
                    if ($isAsync && strpos($returnType, 'Task') !== false) {
                        $code .= "{$indent}    return await Task.FromResult(" . $this->getDefaultReturnValue($this->getTaskReturnType($returnType)) . ");\n";
                    } else {
                        $code .= "{$indent}    return " . $this->getDefaultReturnValue($returnType) . ";\n";
                    }
                } else {
                    $code .= "{$indent}    // TODO: Implement method\n";
                }
                
                $code .= "{$indent}}\n\n";
            }
        }
        
        return $code;
    }
    
    /**
     * Generate a C# class with C# 11.0 features
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        // Check if this is a file-local type
        if ($classData['fileLocal'] ?? false) {
            return $this->generateFileLocalType($classData);
        }
        
        return parent::generateClass($classData);
    }
    
    /**
     * Generate a file-local type (C# 11 feature)
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateFileLocalType(array $classData): CodeFile
    {
        $type = $classData['type'] ?? 'class';
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
        
        // File-scoped namespace
        $code .= "\nnamespace {$namespace};\n\n";
        
        // File-local type documentation
        $code .= "/// <summary>\n";
        $code .= "/// File-local " . $type . " {$name}\n";
        $code .= "/// </summary>\n";
        
        // Generate attributes
        if (!empty($classData['annotations'])) {
            $code .= $this->generateAttributesWithIndent($classData['annotations'], "");
        }
        
        // File-local type declaration (C# 11)
        $code .= "file ";
        
        // Add other modifiers
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
                $code .= "interface {$name}";
                break;
                
            case 'struct':
                $code .= "struct {$name}";
                break;
                
            case 'record':
                $code .= "record {$name}";
                break;
                
            case 'record struct':
                $code .= "record struct {$name}";
                break;
                
            default: // class
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
        
        $code .= "\n{\n";
        
        // Generate content
        if (!empty($classData['attributes'])) {
            $code .= $this->generatePropertiesWithIndent($classData['attributes'], "    ");
        }
        
        if (!empty($classData['methods'])) {
            $code .= $this->generateMethodsWithIndent($classData['methods'], $type, "    ");
        }
        
        $code .= "}\n";
        
        $path = $this->outputDirectory;
        $file = new CodeFile($fileName, $path, $code);
        $this->addFile($file);
        
        return $file;
    }
} 
