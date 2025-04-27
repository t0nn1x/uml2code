<?php

namespace App\Core\Parser;

use App\Core\Parser\Exception\ParserException;

/**
 * Interface for UML diagram parsers
 */
interface ParserInterface
{
    /**
     * Parse UML text into a structured diagram model
     *
     * @param string $umlText The UML text to parse
     * @return mixed Returns a diagram model object
     * @throws ParserException If parsing fails
     */
    public function parse(string $umlText);
}
