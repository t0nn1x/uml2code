<?php

namespace App\Core\Parser;

use App\Core\Parser\Models\ClassDiagram;

/**
 * Interface for class diagram parsers
 */
interface ClassDiagramParserInterface extends DiagramParserInterface
{
    /**
     * Parse class diagram text into a structured model
     *
     * @param string $diagramText The diagram text to parse
     * @return ClassDiagram Returns a class diagram model
     * @throws ParserException If parsing fails
     */
    public function parse(string $diagramText): ClassDiagram;
} 
