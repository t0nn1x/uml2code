<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;
use App\Core\Generator\ClassDiagram\Domain\Exception\GeneratorException;

/**
 * C# 6.0 code generator for class diagrams
 * 
 * Features in C# 6.0:
 * - Auto-property initializers
 * - Expression-bodied members (methods and properties)
 * - using static
 * - Null-conditional operators (?.)
 * - String interpolation ($"")
 * - nameof operator
 * - Index initializers
 * - Exception filters
 * - Await in catch/finally blocks
 */
class Csharp6CodeGenerator extends AbstractCsharpCodeGenerator
{
    /**
     * @var int Current class index being processed
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
     * Generate a C# class from the diagram class definition
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
        
        $code = "using System;\n";
        $code .= "using System.Collections.Generic;\n";
        $code .= "using System.Linq;\n";
        
        // Add additional imports
        $imports = $this->generateImports($classData);
        if (!empty($imports)) {
            $code .= $imports;
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
                $code .= "public struct {$name}";
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
            
            // Generate methods
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
     * Generate additional using statements
     *
     * @param array $classData
     * @return string
     */
    protected function generateImports(array $classData): string
    {
        $imports = [];
        
        // Add imports for Task types
        if ($this->usesTaskTypes($classData)) {
            $imports[] = "using System.Threading.Tasks;";
        }
        
        // Add imports for LINQ if needed
        if ($this->usesLinq($classData)) {
            $imports[] = "using System.Linq;";
        }
        
        if (empty($imports)) {
            return "";
        }
        
        return implode("\n", $imports) . "\n";
    }
    
    /**
     * Check if the class uses Task types
     *
     * @param array $classData
     * @return bool
     */
    protected function usesTaskTypes(array $classData): bool
    {
        // Check attributes
        if (!empty($classData['attributes'])) {
            foreach ($classData['attributes'] as $attr) {
                if (isset($attr['type']) && stripos($attr['type'], 'task') !== false) {
                    return true;
                }
            }
        }
        
        // Check methods
        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                if (isset($method['returnType']) && stripos($method['returnType'], 'task') !== false) {
                    return true;
                }
                if (!empty($method['parameters'])) {
                    foreach ($method['parameters'] as $param) {
                        if (isset($param['type']) && stripos($param['type'], 'task') !== false) {
                            return true;
                        }
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if the class uses LINQ
     *
     * @param array $classData
     * @return bool
     */
    protected function usesLinq(array $classData): bool
    {
        // This is a simplified check - could be expanded
        return !empty($classData['usesLinq']);
    }
    
    /**
     * Generate C# attributes (annotations)
     *
     * @param array $annotations
     * @return string
     */
    protected function generateAttributes(array $annotations): string
    {
        $code = "";
        
        foreach ($annotations as $annotation) {
            $code .= "    [{$annotation}]\n";
        }
        
        return $code;
    }
    
    /**
     * Generate enum values
     *
     * @param array $classData
     * @return string
     */
    protected function generateEnumValues(array $classData): string
    {
        $code = "";
        
        if (!empty($classData['enumValues'])) {
            $values = [];
            foreach ($classData['enumValues'] as $enumValue) {
                $name = is_array($enumValue) ? $enumValue['name'] : $enumValue;
                $value = is_array($enumValue) && isset($enumValue['value']) ? $enumValue['value'] : null;
                
                $valueStr = "        {$name}";
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
     * Generate properties from attributes
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
            
            if ($classType === 'interface') {
                // Interface properties
                $code .= "        {$type} {$name}";
                if ($isReadonly) {
                    $code .= " { get; }";
                } else {
                    $code .= " { get; set; }";
                }
                
                if ($defaultValue !== null) {
                    $code .= " => {$defaultValue};";
                }
            } else {
                // Class properties
                $code .= "        {$visibility} ";
                
                if ($isStatic) {
                    $code .= "static ";
                }
                
                // Generate property declaration
                $code .= "{$type} {$name}";
                
                // C# 6.0 auto-property with initializer
                if ($isReadonly) {
                    // Read-only property (only getter)
                    $code .= " { get; }";
                } else {
                    // Regular auto-property
                    $code .= " { get; set; }";
                }
                
                // C# 6.0 allows auto-property initializers
                if ($defaultValue !== null) {
                    $code .= " = {$defaultValue};";
                }
            }
            
            $code .= "\n\n";
        }
        
        return $code;
    }
    
    /**
     * Generate methods
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
            
            // Check if method has expression body (C# 6.0 feature: method => expression)
            $expressionBody = $method['expressionBody'] ?? null;
            $isExpressionBodied = !empty($expressionBody) || ($method['expressionBodied'] ?? false);
            
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
                
                $paramStr = "";
                
                // Parameter modifiers (ref, out, params)
                if (!empty($paramModifier)) {
                    $paramStr .= "{$paramModifier} ";
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
            if ($classType === 'interface' || $isAbstract) {
                $code .= ";\n\n";
            } else {
                // C# 6.0 expression-bodied members
                if ($isExpressionBodied) {
                    if (!empty($expressionBody)) {
                        // Use provided expression body
                        $code .= " => {$expressionBody};\n\n";
                    } else {
                        // Generate default expression body
                        $code .= " => " . $this->getDefaultReturnValue($returnType) . ";\n\n";
                    }
                } else {
                    $code .= "\n        {\n";
                    
                    if ($returnType !== 'void') {
                        $code .= "            // TODO: Implement method\n";
                        if ($isAsync && strpos($returnType, 'Task') !== false) {
                            $code .= "            return Task.FromResult(" . $this->getDefaultReturnValue($this->getTaskReturnType($returnType)) . ");\n";
                        } else {
                            $code .= "            return " . $this->getDefaultReturnValue($returnType) . ";\n";
                        }
                    } else {
                        $code .= "            // TODO: Implement method\n";
                    }
                    
                    $code .= "        }\n\n";
                }
            }
        }
        
        return $code;
    }
    
    /**
     * Extract return type from Task<T>
     *
     * @param string $taskType
     * @return string
     */
    protected function getTaskReturnType(string $taskType): string
    {
        if (preg_match('/Task<(.+)>/', $taskType, $matches)) {
            return $this->mapType($matches[1]);
        }
        return 'object';
    }
    
    /**
     * Check if a type is a C# built-in type
     *
     * @param string $type
     * @return bool
     */
    protected function isBuiltinType(string $type): bool
    {
        $builtinTypes = [
            'bool', 'byte', 'sbyte', 'char', 'decimal', 'double', 'float', 'int', 'uint', 
            'long', 'ulong', 'object', 'short', 'ushort', 'string', 'void', 'dynamic',
            'DateTime', 'TimeSpan', 'Guid', 'DateTimeOffset'
        ];
        
        // Remove array notation
        $typeWithoutArray = rtrim($type, '[]');
        
        // Check if it's a basic built-in type
        if (in_array($typeWithoutArray, $builtinTypes)) {
            return true;
        }
        
        // Check if it's in the type mapping
        if (array_key_exists(strtolower($typeWithoutArray), static::TYPE_MAPPING)) {
            return true;
        }
        
        // Handle generic types
        if (preg_match('/^(\w+)<.+>$/', $typeWithoutArray, $matches)) {
            $baseType = $matches[1];
            // Common generic collections
            if (in_array($baseType, ['List', 'Dictionary', 'HashSet', 'IList', 'ICollection', 'IEnumerable', 'Task'])) {
                return true;
            }
        }
        
        return false;
    }
}
