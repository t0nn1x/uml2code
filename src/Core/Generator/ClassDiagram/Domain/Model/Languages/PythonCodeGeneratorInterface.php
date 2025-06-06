<?php

namespace App\Core\Generator\ClassDiagram\Domain\Model\Languages;

use App\Core\Generator\ClassDiagram\Domain\Model\LanguageCodeGenerator;

/**
 * Interface for Python code generators
 */
interface PythonCodeGeneratorInterface extends LanguageCodeGenerator
{
    /**
     * Set the module prefix for generated Python code
     *
     * @param string $modulePrefix
     * @return self
     */
    public function setModulePrefix(string $modulePrefix): self;
    
    /**
     * Get the module prefix for generated Python code
     *
     * @return string
     */
    public function getModulePrefix(): string;
} 
