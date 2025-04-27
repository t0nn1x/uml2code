<?php

namespace App\Core\Parser;

use App\Core\Parser\Exception\ParserException;

/**
 * Detects the type of PlantUML diagram from its content
 */
class DiagramTypeDetector
{
    // Diagram type constants
    public const TYPE_CLASS = 'class';
    public const TYPE_SEQUENCE = 'sequence';
    public const TYPE_ACTIVITY = 'activity';
    public const TYPE_USECASE = 'usecase';
    public const TYPE_COMPONENT = 'component';
    public const TYPE_STATE = 'state';
    public const TYPE_OBJECT = 'object';
    public const TYPE_UNKNOWN = 'unknown';

    // Keywords that indicate diagram types
    private const TYPE_INDICATORS = [
        self::TYPE_CLASS => [
            'class',
            'interface',
            'enum',
            'abstract class',
            'extends',
            'implements',
            '<|--',
            '*--',
            'o--'
        ],
        self::TYPE_SEQUENCE => [
            'participant',
            'actor',
            'boundary',
            'control',
            'entity',
            'database',
            '->>',
            '->x',
            '->o',
            '<--',
            '-->',
            '<<--',
            ': ',
            'activate',
            'deactivate',
            'alt',
            'loop',
            'opt'
        ],
        self::TYPE_ACTIVITY => [
            'start',
            'stop',
            'end',
            'if',
            'then',
            'else',
            'endif',
            'fork',
            'join',
            'repeat',
            'while',
            'endwhile',
            'swim',
            'partition'
        ],
        self::TYPE_USECASE => [
            'actor',
            'usecase',
            '<<extend>>',
            '<<include>>'
        ],
        self::TYPE_COMPONENT => [
            'component',
            '[',
            ']',
            'interface',
            'port'
        ],
        self::TYPE_STATE => [
            'state',
            '[*]'
        ],
        self::TYPE_OBJECT => [
            'object',
            'map'
        ]
    ];

    /**
     * Detect the type of diagram from PlantUML content
     *
     * @param string $plantUmlText
     * @return string One of the TYPE_* constants
     * @throws ParserException If the diagram type cannot be detected
     */
    public function detectType(string $plantUmlText): string
    {
        // Check for explicit diagram type
        if (preg_match('/@startuml\s*\((\w+)\)/', $plantUmlText, $matches)) {
            $explicitType = strtolower($matches[1]);
            if (defined('self::TYPE_' . strtoupper($explicitType))) {
                return $explicitType;
            }
        }

        // Count indicators for each diagram type
        $typeCounts = [];
        foreach (self::TYPE_INDICATORS as $type => $indicators) {
            $typeCounts[$type] = 0;

            foreach ($indicators as $indicator) {
                // Calculate the number of occurrences and adjust counters
                $count = substr_count($plantUmlText, $indicator);
                $typeCounts[$type] += $count;
            }
        }

        // Get diagram type with the highest indicator count
        arsort($typeCounts);
        $detectedType = key($typeCounts);

        // Return unknown if no indicators found
        if ($typeCounts[$detectedType] === 0) {
            return self::TYPE_UNKNOWN;
        }

        return $detectedType;
    }
}
