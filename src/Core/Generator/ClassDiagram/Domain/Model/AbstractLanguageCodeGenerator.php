<?php

namespace App\Core\Generator\ClassDiagram\Domain\Model;

/**
 * Abstract base implementation of LanguageCodeGenerator
 */
abstract class AbstractLanguageCodeGenerator extends AbstractCodeGenerator implements LanguageCodeGenerator
{
    /**
     * @var string The output directory for generated code
     */
    protected string $outputDirectory = '';
    
    /**
     * Set the output directory for generated code
     *
     * @param string $outputDirectory
     * @return self
     */
    public function setOutputDirectory(string $outputDirectory): self
    {
        $this->outputDirectory = rtrim($outputDirectory, '/');
        return $this;
    }
    
    /**
     * Get the supported language name
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }
    
    /**
     * Get the supported language version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }
} 
