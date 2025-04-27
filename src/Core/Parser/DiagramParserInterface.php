<?php

namespace App\Core\Parser;

/**
 * Base interface for all diagram parsers
 */
interface DiagramParserInterface
{
    /**
     * Parse diagram text into a structured model
     *
     * @param string $diagramText The diagram text to parse
     * @return mixed Returns a diagram model
     * @throws ParserException If parsing fails
     */
    public function parse(string $diagramText);
} 
