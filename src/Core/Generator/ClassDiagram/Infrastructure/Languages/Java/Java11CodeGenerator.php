<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Java;

use App\Core\Generator\ClassDiagram\Domain\Exception\GeneratorException;
use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * Java 11 code generator for class diagrams
 *
 * New features in Java 11:
 * - Local-Variable Type Inference
 * - JShell (REPL)
 * - HTTP Client
 * - ZGC and Shenandoah GC
 * - Text Blocks
 * - Pattern Matching for instanceof
 */
class Java11CodeGenerator extends Java8CodeGenerator
{
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
        $code .= " */\n";
        
        // Class declaration
        switch ($type) {
            case 'interface':
                $code .= "public interface {$name}";
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
            $originalType = $attr['type'] ?? 'Object';
            $mappedType = $this->mapType($originalType);
            
            // Property documentation
            $code .= "    /**\n";
            $code .= "     * The {$name} property\n";
            $code .= "     */\n";
            
            // Property declaration
            $code .= "    {$visibility} {$mappedType} {$name}";
            
            // Add default value if provided
            if (isset($attr['defaultValue'])) {
                $defaultValue = $this->formatDefaultValue($attr['defaultValue'], $mappedType);
                $code .= " = {$defaultValue}";
            }
            
            $code .= ";\n\n";
            
            // Generate getter
            $getterPrefix = ($mappedType === 'boolean' || $mappedType === 'Boolean') ? 'is' : 'get';
            $capitalizedName = ucfirst($name);
            
            $code .= "    /**\n";
            $code .= "     * Get the {$name} value\n";
            $code .= "     * @return {$mappedType}\n";
            $code .= "     */\n";
            $code .= "    public {$mappedType} {$getterPrefix}{$capitalizedName}() {\n";
            $code .= "        return this.{$name};\n";
            $code .= "    }\n\n";
            
            // Generate setter (except for final or constant fields)
            if (!isset($attr['isFinal']) || !$attr['isFinal']) {
                $code .= "    /**\n";
                $code .= "     * Set the {$name} value\n";
                $code .= "     * @param {$name} The {$name} value\n";
                $code .= "     */\n";
                $code .= "    public void set{$capitalizedName}({$mappedType} {$name}) {\n";
                $code .= "        this.{$name} = {$name};\n";
                $code .= "    }\n\n";
            }
        }
        
        return $code;
    }
    
    /**
     * Generate Java methods
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
            $isConstructor = ($name === $this->diagram['classes'][$this->currentClassIndex]['name']);
            
            if ($isConstructor) {
                $constructorExists = true;
            }
            
            // Method documentation
            $code .= "    /**\n";
            
            // Parameter documentation
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'Object';
                $code .= "     * @param {$paramName} The {$paramName} parameter\n";
            }
            
            // Return type documentation (only for non-constructors with non-void return type)
            if (!$isConstructor && $returnType !== 'void') {
                $code .= "     * @return {$returnType}\n";
            }
            
            $code .= "     */\n";
            
            // Method declaration
            $code .= "    {$visibility} ";
            
            // For constructors, no return type and use class name as method name
            if ($isConstructor) {
                $code .= $this->diagram['classes'][$this->currentClassIndex]['name'];
            } else {
                $code .= "{$returnType} {$name}";
            }
            
            // Method parameters
            $code .= "(";
            $paramStrings = [];
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'Object';
                $paramStrings[] = "{$paramType} {$paramName}";
            }
            $code .= implode(", ", $paramStrings);
            $code .= ") {\n";
            
            // Method body
            if ($isConstructor) {
                $code .= "        // Initialize object\n";
            } else if ($returnType !== 'void') {
                $code .= "        // TODO: Implement method\n";
                $code .= "        return " . $this->getDefaultReturnValue($returnType) . ";\n";
            } else {
                $code .= "        // TODO: Implement method\n";
            }
            
            $code .= "    }\n\n";
        }
        
        // Create a default constructor if no constructor was defined
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
     * Generate interface methods (without implementation)
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
            
            // Method documentation
            $code .= "    /**\n";
            
            // Parameter documentation
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'Object';
                $code .= "     * @param {$paramName} The {$paramName} parameter\n";
            }
            
            // Return type documentation
            if ($returnType !== 'void') {
                $code .= "     * @return {$returnType}\n";
            }
            
            $code .= "     */\n";
            
            // Interface methods don't have visibility (implicitly public)
            $code .= "    {$returnType} {$name}(";
            
            // Method parameters
            $paramStrings = [];
            foreach ($parameters as $param) {
                $paramName = $param['name'];
                $paramType = isset($param['type']) ? $this->mapType($param['type']) : 'Object';
                $paramStrings[] = "{$paramType} {$paramName}";
            }
            $code .= implode(", ", $paramStrings);
            
            // Interface methods don't have implementation
            $code .= ");\n\n";
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
     * Map PHP visibility to Java visibility
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
