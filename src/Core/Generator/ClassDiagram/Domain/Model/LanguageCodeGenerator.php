<?php

namespace App\Core\Generator\ClassDiagram\Domain\Model;

/**
 * Interface for language-specific code generators with additional configuration
 */
interface LanguageCodeGenerator extends CodeGenerator
{
    /**
     * Set the output directory for generated code
     *
     * @param string $outputDirectory
     * @return self
     */
    public function setOutputDirectory(string $outputDirectory): self;
    
    /**
     * Get the supported language name
     *
     * @return string
     */
    public function getLanguage(): string;
    
    /**
     * Get the supported language version
     *
     * @return string
     */
    public function getVersion(): string;
} 
