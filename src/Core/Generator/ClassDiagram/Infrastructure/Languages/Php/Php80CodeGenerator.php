<?php

namespace App\Core\Generator\ClassDiagram\Infrastructure\Languages\Php;

use App\Core\Generator\ClassDiagram\Domain\Model\CodeFile;

/**
 * PHP 8.0 code generator for class diagrams
 */
class Php80CodeGenerator extends Php74CodeGenerator
{
    /**
     * Generate properties from attributes
     * Overrides the PHP 7.4 implementation to use PHP 8.0 features
     *
     * @param array $attributes
     * @return string
     */
    protected function generateProperties(array $attributes): string
    {
        $code = "";
        
        foreach ($attributes as $attr) {
            $name = $attr['name'];
            $visibility = $this->mapVisibility($attr['visibility'] ?? 'public');
            $type = isset($attr['type']) ? $this->mapType($attr['type']) : null;
            
            // Property docblock
            $code .= "    /**\n";
            
            if ($type !== null) {
                // Handle generic types in docblock
                if (isset($attr['typeArguments']) && !empty($attr['typeArguments'])) {
                    $docType = $this->generateDocblockType($attr['type'], $attr['typeArguments']);
                    $code .= "     * @var {$docType}\n";
                } else {
                    $code .= "     * @var {$type}\n";
                }
            }
            
            $code .= "     */\n";
            
            // Property declaration
            $code .= "    {$visibility} ";
            
            // Add type hint - PHP 8.0 supports more types including mixed
            if ($type !== null) {
                $code .= "{$type} ";
            }
            
            $code .= "\${$name}";
            
            // Add default value if it's a constant or simple value
            if (isset($attr['defaultValue'])) {
                $code .= " = " . $attr['defaultValue'];
            }
            
            $code .= ";\n\n";
        }
        
        return $code;
    }
    
    /**
     * Generate a PHP class from the diagram class definition
     *
     * @param array $classData
     * @return CodeFile
     */
    protected function generateClass(array $classData): CodeFile
    {
        $type = $classData['type'] ?? 'class';
        $name = $classData['name'];
        
        $fileName = "{$name}.php";
        $namespace = $this->getNamespacePrefix();
        
        $code = "<?php\n\n";
        $code .= "namespace {$namespace};\n\n";
        
        // Add imports
        $imports = $this->generateImports($classData);
        if (!empty($imports)) {
            $code .= $imports . "\n";
        }
        
        // Class, interface, or enum docblock
        $code .= "/**\n";
        $code .= " * " . ucfirst($type) . " {$name}\n";
        $code .= " */\n";
        
        // Class declaration
        switch ($type) {
            case 'interface':
                $code .= "interface {$name}\n";
                break;
                
            case 'enum':
                // PHP 8.0 doesn't support enums yet (they were added in 8.1)
                // so implement as class with constants like in PHP 7.4
                $code .= "class {$name}\n";
                break;
                
            case 'abstract':
                $code .= "abstract class {$name}";
                if (!empty($classData['extends'])) {
                    $code .= " extends {$classData['extends']}";
                }
                if (!empty($classData['implements'])) {
                    $code .= " implements " . implode(', ', $classData['implements']);
                }
                $code .= "\n";
                break;
                
            default: // regular class
                $code .= "class {$name}";
                if (!empty($classData['extends'])) {
                    $code .= " extends {$classData['extends']}";
                }
                if (!empty($classData['implements'])) {
                    $code .= " implements " . implode(', ', $classData['implements']);
                }
                
                // Handle generic type parameters
                if (!empty($classData['typeParameters'])) {
                    // In PHP 8.0 we still need to use docblocks for generics
                    $code = str_replace("/**\n * " . ucfirst($type) . " {$name}\n */", 
                        "/**\n * " . ucfirst($type) . " {$name}\n * \n * @template " . 
                        implode("\n * @template ", $classData['typeParameters']) . "\n */", $code);
                }
                
                $code .= "\n";
                break;
        }
        
        $code .= "{\n";
        
        // Constants for enums in PHP 8.0
        if ($type === 'enum') {
            $code .= $this->generateEnumConstants($classData);
        }
        
        // Properties
        if (!empty($classData['attributes'])) {
            $code .= $this->generateProperties($classData['attributes']);
        }
        
        // Methods
        if (!empty($classData['methods'])) {
            $code .= $this->generateMethods($classData['methods'], $type);
        }
        
        $code .= "}\n";
        
        $path = $this->outputDirectory;
        $file = new CodeFile($fileName, $path, $code);
        $this->addFile($file);
        
        return $file;
    }

    /**
     * Generate enum constants (for PHP 8.0 which doesn't support enums)
     * Override parent method to use modern enum format
     *
     * @param array $classData
     * @return string
     */
    protected function generateEnumConstants(array $classData): string
    {
        $code = "";
        
        // Check for enum values first (modern parser format)
        if (!empty($classData['enumValues'])) {
            foreach ($classData['enumValues'] as $enumValue) {
                $name = is_array($enumValue) ? $enumValue['name'] : $enumValue;
                $value = is_array($enumValue) && isset($enumValue['value']) ? $enumValue['value'] : null;
                
                // Default value handling
                if ($value === null) {
                    $defaultValue = "'{$name}'"; // String default
                } else {
                    // If it's numeric, don't quote it
                    $defaultValue = is_numeric($value) ? $value : "'{$value}'";
                }
                
                $code .= "    public const {$name} = {$defaultValue};\n";
            }
            $code .= "\n";
            return $code;
        }
        
        // Fallback to parent implementation for legacy format
        return parent::generateEnumConstants($classData);
    }
} 
