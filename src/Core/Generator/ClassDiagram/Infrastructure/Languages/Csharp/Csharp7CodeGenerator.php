<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Csharp;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * C# 7.0 code generator for class diagrams
 * 
 * New features in C# 7.0:
 * - Tuples and tuple deconstruction
 * - Pattern matching (is and switch)
 * - Local functions
 * - Out variables
 * - Throw expressions
 * - Discards (_)
 * - Ref locals and returns
 * - Expression-bodied constructors and finalizers
 * - Expression-bodied getters and setters
 * - Literal improvements (binary literals, digit separators)
 */
class Csharp7CodeGenerator extends Csharp6CodeGenerator
{
    /**
     * Generate properties with C# 7 enhancements
     * Supports expression-bodied getters and setters
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
            $hasCustomGetter = $attr['customGetter'] ?? false;
            $hasCustomSetter = $attr['customSetter'] ?? false;
            
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
                
                // C# 7.0 supports expression-bodied getters and setters
                if ($hasCustomGetter || $hasCustomSetter) {
                    // Backing field
                    $backingFieldName = "_{$name}";
                    $code .= "{$type} {$name}\n        {\n";
                    
                    // Expression-bodied getter
                    $code .= "            get => {$backingFieldName};\n";
                    
                    if (!$isReadonly) {
                        // Expression-bodied setter
                        $code .= "            set => {$backingFieldName} = value;\n";
                    }
                    
                    $code .= "        }\n";
                    
                    // Add backing field
                    $code .= "        private {$type} {$backingFieldName}";
                    if ($defaultValue !== null) {
                        $code .= " = {$defaultValue}";
                    }
                    $code .= ";\n";
                } else {
                    // Regular auto-property
                    if ($isReadonly) {
                        $code .= "{$type} {$name} { get; }";
                    } else {
                        $code .= "{$type} {$name} { get; set; }";
                    }
                    
                    if ($defaultValue !== null) {
                        $code .= " = {$defaultValue};";
                    }
                }
            }
            
            $code .= "\n\n";
        }
        
        return $code;
    }
    
    /**
     * Generate methods with C# 7 enhancements
     * Supports tuples, pattern matching, and local functions
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
            $isExpressionBodied = $method['expressionBodied'] ?? false;
            $returnsRef = $method['returnsRef'] ?? false;
            $hasLocalFunctions = $method['hasLocalFunctions'] ?? false;
            $returnsTuple = $method['returnsTuple'] ?? false;
            
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
            
            // C# 7.0 ref returns
            if ($returnsRef) {
                $code .= "ref ";
            }
            
            // Handle tuple return types
            if ($returnsTuple && isset($method['tupleElements'])) {
                $tupleElements = [];
                foreach ($method['tupleElements'] as $element) {
                    $elementType = $this->mapType($element['type']);
                    $elementName = $element['name'] ?? null;
                    if ($elementName) {
                        $tupleElements[] = "{$elementType} {$elementName}";
                    } else {
                        $tupleElements[] = $elementType;
                    }
                }
                $returnType = "(" . implode(", ", $tupleElements) . ")";
            }
            
            $code .= "{$returnType} {$name}(";
            
            // Method parameters with C# 7.0 enhancements
            $paramStrings = [];
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'object';
                $paramModifier = $param['modifier'] ?? '';
                
                $paramStr = "";
                
                // Parameter modifiers (ref, out, in, params)
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
                // Expression-bodied members
                if ($isExpressionBodied && $returnType !== 'void') {
                    if ($returnsTuple && isset($method['tupleElements'])) {
                        $defaultValues = [];
                        foreach ($method['tupleElements'] as $element) {
                            $defaultValues[] = $this->getDefaultReturnValue($this->mapType($element['type']));
                        }
                        $code .= " => (" . implode(", ", $defaultValues) . ");\n\n";
                    } else {
                        $code .= " => " . $this->getDefaultReturnValue($returnType) . ";\n\n";
                    }
                } else {
                    $code .= "\n        {\n";
                    
                    // C# 7.0 local functions example
                    if ($hasLocalFunctions) {
                        $code .= "            // C# 7.0 Local function example\n";
                        $code .= "            bool IsValid(string input)\n";
                        $code .= "            {\n";
                        $code .= "                return !string.IsNullOrEmpty(input);\n";
                        $code .= "            }\n\n";
                    }
                    
                    // Pattern matching example
                    if (!empty($parameters)) {
                        $firstParam = $parameters[0];
                        if (isset($firstParam['name'])) {
                            $code .= "            // C# 7.0 Pattern matching example\n";
                            $code .= "            if ({$firstParam['name']} is null)\n";
                            $code .= "            {\n";
                            $code .= "                throw new ArgumentNullException(nameof({$firstParam['name']}));\n";
                            $code .= "            }\n\n";
                        }
                    }
                    
                    if ($returnType !== 'void') {
                        $code .= "            // TODO: Implement method\n";
                        if ($returnsTuple && isset($method['tupleElements'])) {
                            $defaultValues = [];
                            foreach ($method['tupleElements'] as $element) {
                                $defaultValues[] = $this->getDefaultReturnValue($this->mapType($element['type']));
                            }
                            $code .= "            return (" . implode(", ", $defaultValues) . ");\n";
                        } elseif ($isAsync && strpos($returnType, 'Task') !== false) {
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
     * Generate a class with C# 7.0 features
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        // Check if class uses tuples
        if ($this->usesTuples($classData)) {
            // Add System.ValueTuple for C# 7.0 tuples
            $classData['additionalUsings'] = array_merge(
                $classData['additionalUsings'] ?? [],
                ['System.ValueTuple']
            );
        }
        
        return parent::generateClass($classData);
    }
    
    /**
     * Check if the class uses tuple types
     *
     * @param array $classData
     * @return bool
     */
    protected function usesTuples(array $classData): bool
    {
        // Check methods for tuple returns
        if (!empty($classData['methods'])) {
            foreach ($classData['methods'] as $method) {
                if ($method['returnsTuple'] ?? false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Generate pattern matching switch expression example
     *
     * @param string $variableName
     * @param string $variableType
     * @return string
     */
    protected function generatePatternMatchingSwitch(string $variableName, string $variableType): string
    {
        $code = "            // C# 7.0 Pattern matching with switch\n";
        $code .= "            switch ({$variableName})\n";
        $code .= "            {\n";
        $code .= "                case null:\n";
        $code .= "                    throw new ArgumentNullException(nameof({$variableName}));\n";
        $code .= "                case string s when s.Length > 0:\n";
        $code .= "                    // Handle non-empty string\n";
        $code .= "                    break;\n";
        $code .= "                case int n when n > 0:\n";
        $code .= "                    // Handle positive integer\n";
        $code .= "                    break;\n";
        $code .= "                default:\n";
        $code .= "                    // Default case\n";
        $code .= "                    break;\n";
        $code .= "            }\n";
        
        return $code;
    }
}
