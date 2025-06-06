<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * C# 10.0 code generator for class diagrams
 * 
 * New features in C# 10.0:
 * - Global using directives
 * - File-scoped namespaces
 * - Record structs
 * - Improvements to structure types
 * - Interpolated string handlers
 * - Global using directives
 * - Extended property patterns
 * - Lambda improvements
 * - Constant interpolated strings
 * - Record types can seal ToString()
 * - Assignment and declaration in same deconstruction
 */
class Csharp10CodeGenerator extends Csharp9CodeGenerator
{
    /**
     * Generate a C# class with C# 10.0 features
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        $type = $classData['type'] ?? 'class';
        $name = $classData['name'];
        
        // Check if this is a record struct
        if ($type === 'record struct') {
            return $this->generateRecordStruct($classData);
        }
        
        $fileName = "{$name}.cs";
        $namespace = $this->namespace;
        
        // Enable nullable reference types
        $code = "#nullable enable\n\n";
        
        // Global usings (C# 10 feature)
        if ($classData['useGlobalUsings'] ?? false) {
            $code .= "global using System;\n";
            $code .= "global using System.Collections.Generic;\n";
            $code .= "global using System.Linq;\n";
            $code .= "global using System.Threading.Tasks;\n\n";
        } else {
            $code .= "using System;\n";
            $code .= "using System.Collections.Generic;\n";
            $code .= "using System.Linq;\n";
        }
        
        // Add additional imports
        $imports = $this->generateImports($classData);
        if (!empty($imports)) {
            $code .= $imports;
        }
        
        // C# 10 file-scoped namespace
        if ($classData['fileScopedNamespace'] ?? true) {
            $code .= "\nnamespace {$namespace};\n\n";
            $indentation = "";
        } else {
            $code .= "\nnamespace {$namespace}\n{\n";
            $indentation = "    ";
        }
        
        // Class documentation
        $code .= "{$indentation}/// <summary>\n";
        $code .= "{$indentation}/// " . ucfirst($type) . " {$name}\n";
        $code .= "{$indentation}/// </summary>\n";
        
        // Generate attributes
        if (!empty($classData['annotations'])) {
            $code .= $this->generateAttributesWithIndent($classData['annotations'], $indentation);
        }
        
        // Class declaration
        $code .= "{$indentation}";
        
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
                
            case 'record':
                $code .= "public record {$name}";
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
                $code .= "\n{$indentation}    where " . $constraint;
            }
        }
        
        $code .= "\n{$indentation}{\n";
        
        // Generate content with proper indentation
        if ($type === 'enum') {
            $code .= $this->generateEnumValuesWithIndent($classData, $indentation . "    ");
        } else {
            // Generate fields/properties
            if (!empty($classData['attributes'])) {
                $code .= $this->generatePropertiesWithIndent($classData['attributes'], $indentation . "    ");
            }
            
            // Generate methods
            if (!empty($classData['methods'])) {
                $code .= $this->generateMethodsWithIndent($classData['methods'], $type, $indentation . "    ");
            }
        }
        
        $code .= "{$indentation}}\n";
        
        // Close namespace if not file-scoped
        if (!($classData['fileScopedNamespace'] ?? true)) {
            $code .= "}\n";
        }
        
        $path = $this->outputDirectory;
        $file = new CodeFile($fileName, $path, $code);
        $this->addFile($file);
        
        return $file;
    }
    
    /**
     * Generate a C# 10 record struct
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateRecordStruct(array $classData): CodeFile
    {
        $name = $classData['name'];
        $fileName = "{$name}.cs";
        $namespace = $this->namespace;
        
        // Enable nullable reference types
        $code = "#nullable enable\n\n";
        
        $code .= "using System;\n";
        $code .= "using System.Collections.Generic;\n";
        $code .= "using System.Linq;\n";
        
        // C# 10 file-scoped namespace
        $code .= "\nnamespace {$namespace};\n\n";
        
        // Record struct documentation
        $code .= "/// <summary>\n";
        $code .= "/// Record struct {$name}\n";
        $code .= "/// </summary>\n";
        
        // Generate attributes
        if (!empty($classData['annotations'])) {
            $code .= $this->generateAttributesWithIndent($classData['annotations'], "");
        }
        
        // Record struct declaration
        $code .= "public ";
        if ($classData['readonly'] ?? false) {
            $code .= "readonly ";
        }
        $code .= "record struct {$name}";
        
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
        
        $code .= "\n{\n";
        
        // Additional properties
        if (!empty($classData['attributes'])) {
            $code .= $this->generatePropertiesWithIndent($classData['attributes'], "    ");
        }
        
        // Methods
        if (!empty($classData['methods'])) {
            $code .= $this->generateMethodsWithIndent($classData['methods'], 'record struct', "    ");
        }
        
        $code .= "}\n";
        
        $path = $this->outputDirectory;
        $file = new CodeFile($fileName, $path, $code);
        $this->addFile($file);
        
        return $file;
    }
    
    /**
     * Generate attributes with indentation
     *
     * @param array $annotations
     * @param string $indent
     * @return string
     */
    protected function generateAttributesWithIndent(array $annotations, string $indent): string
    {
        $code = "";
        
        foreach ($annotations as $annotation) {
            $code .= "{$indent}[{$annotation}]\n";
        }
        
        return $code;
    }
    
    /**
     * Generate enum values with indentation
     *
     * @param array $classData
     * @param string $indent
     * @return string
     */
    protected function generateEnumValuesWithIndent(array $classData, string $indent): string
    {
        $code = "";
        
        if (!empty($classData['enumValues'])) {
            $values = [];
            foreach ($classData['enumValues'] as $enumValue) {
                $name = is_array($enumValue) ? $enumValue['name'] : $enumValue;
                $value = is_array($enumValue) && isset($enumValue['value']) ? $enumValue['value'] : null;
                
                $valueStr = "{$indent}{$name}";
                if ($value !== null) {
                    $valueStr .= " = {$value}";
                }
                $values[] = $valueStr;
            }
            $code .= implode(",\n", $values) . "\n";
        }
        
        return $code;
    }
    
    /**
     * Generate properties with indentation
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
            $defaultValue = $attr['defaultValue'] ?? null;
            
            // Property documentation
            if (!empty($attr['description'])) {
                $code .= "{$indent}/// <summary>\n";
                $code .= "{$indent}/// {$attr['description']}\n";
                $code .= "{$indent}/// </summary>\n";
            }
            
            // Property attributes
            if (!empty($attr['annotations'])) {
                foreach ($attr['annotations'] as $annotation) {
                    $code .= "{$indent}[{$annotation}]\n";
                }
            }
            
            $code .= "{$indent}{$visibility} ";
            
            if ($isStatic) {
                $code .= "static ";
            }
            
            // Handle nullable reference types
            if ($isNullable && $this->isReferenceType($type)) {
                $type .= "?";
            }
            
            $code .= "{$type} {$name} { get; ";
            
            if ($isInitOnly) {
                $code .= "init; ";
            } elseif (!$isReadonly) {
                $code .= "set; ";
            }
            
            $code .= "}";
            
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
     * Generate methods with indentation
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
            
            // Method attributes
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
                $code .= "\n{$indent}{\n";
                
                // C# 10 constant interpolated strings
                if ($method['useConstantInterpolation'] ?? false) {
                    $code .= "{$indent}    // C# 10 constant interpolated strings\n";
                    $code .= "{$indent}    const string message = \$\"Hello from {{{$name}}}\";\n\n";
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
} 
