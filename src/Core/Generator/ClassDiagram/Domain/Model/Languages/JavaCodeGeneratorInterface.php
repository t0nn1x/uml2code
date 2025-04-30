<?php

namespace App\Core\Generator\ClassDiagram\Domain\Model\Languages;

use App\Core\Generator\ClassDiagram\Domain\Model\LanguageCodeGenerator;

/**
 * Interface for Java code generators
 */
interface JavaCodeGeneratorInterface extends LanguageCodeGenerator
{
    /**
     * Set the package name for generated Java code
     *
     * @param string $packageName
     * @return self
     */
    public function setPackageName(string $packageName): self;
    
    /**
     * Get the package name for generated Java code
     *
     * @return string
     */
    public function getPackageName(): string;
} 
