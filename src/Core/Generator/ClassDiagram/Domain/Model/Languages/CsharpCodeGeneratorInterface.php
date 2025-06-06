<?php

namespace App\Core\Generator\ClassDiagram\Domain\Model\Languages;

use App\Core\Generator\ClassDiagram\Domain\Model\LanguageCodeGenerator;

/**
 * Interface for C# code generators
 */
interface CsharpCodeGeneratorInterface extends LanguageCodeGenerator
{
    /**
     * Set the namespace for generated C# code
     *
     * @param string $namespace
     * @return self
     */
    public function setNamespace(string $namespace): self;
    
    /**
     * Get the namespace for generated C# code
     *
     * @return string
     */
    public function getNamespace(): string;
} 
