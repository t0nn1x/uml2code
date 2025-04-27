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
            'class' => 10,           // Highest weight: Definitive keyword that explicitly declares a class diagram
            'interface' => 10,       // Highest weight: Definitive keyword for interface declaration, unique to class diagrams
            'enum' => 10,           // Highest weight: Definitive keyword for enum declaration, specific to class diagrams
            'abstract class' => 10,  // Highest weight: Definitive keyword combination for abstract class declaration
            'extends' => 5,         // Medium weight: Common inheritance keyword but could appear in comments or names
            'implements' => 5,      // Medium weight: Common interface implementation keyword but could appear in comments
            '<|--' => 3,           // Low weight: Inheritance arrow notation but could be used in other contexts
            '*--' => 3,            // Low weight: Composition arrow notation but similar to other relationship types
            'o--' => 3,            // Low weight: Aggregation arrow notation but could be confused with other symbols
            '{' => 1,              // Minimal weight: Basic syntax character used in many contexts
            '}' => 1,              // Minimal weight: Basic syntax character used in many contexts
            '--' => 2,             // Low weight: Basic association line, could be used in many contexts
            '->' => 2,             // Low weight: Basic directed association, common in many diagrams
            '<-' => 2,             // Low weight: Basic directed association (reverse), common in many diagrams
            '<--' => 2,            // Low weight: Basic directed association line, used in various diagrams
            '-->' => 2,            // Low weight: Basic directed association line, used in various diagrams
            '<|-' => 2,            // Low weight: Inheritance line start, could be used in other contexts
            '-|>' => 2,            // Low weight: Inheritance line end, could be used in other contexts
            '*-' => 2,             // Low weight: Composition line start, similar to other relationships
            '-*' => 2,             // Low weight: Composition line end, similar to other relationships
            'o-' => 2,             // Low weight: Aggregation line start, could be confused with other notations
            '-o' => 2,             // Low weight: Aggregation line end, could be confused with other notations
            '..' => 1,             // Minimal weight: Dotted line, very generic notation
            '..>' => 1,            // Minimal weight: Dotted arrow, very generic notation
            '<..' => 1,            // Minimal weight: Dotted arrow (reverse), very generic notation
        ],
        self::TYPE_SEQUENCE => [
            'participant' => 10,    // Highest weight: Definitive keyword unique to sequence diagrams
            'actor' => 5,          // Medium weight: Common in sequence diagrams but also used in use cases
            'boundary' => 8,       // High weight: Very specific to sequence diagrams but not definitive
            'control' => 8,        // High weight: Very specific to sequence diagrams but not definitive
            'entity' => 3,         // Low weight: Could appear in class diagrams or other contexts
            'database' => 5,       // Medium weight: Common in sequence diagrams but could be used elsewhere
            '->>' => 8,           // High weight: Typical sequence message notation
            '->x' => 8,           // High weight: Specific destruction message notation
            '->o' => 8,           // High weight: Specific creation message notation
            '-->' => 3,           // Low weight: Generic arrow that appears in many diagram types
            '<<--' => 8,          // High weight: Specific return message notation
            'activate' => 10,      // Highest weight: Definitive sequence diagram lifecycle keyword
            'deactivate' => 10,    // Highest weight: Definitive sequence diagram lifecycle keyword
            'alt' => 5,           // Medium weight: Alternative fragment but could be variable name
            'loop' => 5,          // Medium weight: Loop fragment but could be variable name
            'opt' => 5            // Medium weight: Optional fragment but could be variable name
        ],
        self::TYPE_ACTIVITY => [
            'start' => 10,         // Highest weight: Definitive activity diagram start node
            'stop' => 10,          // Highest weight: Definitive activity diagram stop node
            'end' => 5,           // Medium weight: Could be part of other words or contexts
            'if' => 3,            // Low weight: Common programming keyword, could be in comments
            'then' => 3,          // Low weight: Common programming keyword, could be in comments
            'else' => 3,          // Low weight: Common programming keyword, could be in comments
            'endif' => 8,         // High weight: More specific to activity flow control
            'fork' => 8,          // High weight: Specific parallel processing notation
            'join' => 5,          // Medium weight: Could appear in other contexts
            'repeat' => 5,        // Medium weight: Could be part of other constructs
            'while' => 3,         // Low weight: Common programming keyword, could be in comments
            'endwhile' => 8,      // High weight: More specific to activity flow control
            'swim' => 10,         // Highest weight: Definitive swimlane keyword
            'partition' => 8      // High weight: Very specific to activity partitioning
        ],
        self::TYPE_USECASE => [
            'actor' => 10,         // Highest weight: Definitive use case diagram element
            'usecase' => 10,       // Highest weight: Definitive use case keyword
            '<<extend>>' => 8,     // High weight: Specific use case relationship stereotype
            '<<include>>' => 8     // High weight: Specific use case relationship stereotype
        ],
        self::TYPE_COMPONENT => [
            'component' => 10,      // Highest weight: Definitive component diagram keyword
            '[' => 2,              // Very low weight: Could be array notation or other syntax
            ']' => 2,              // Very low weight: Could be array notation or other syntax
            'interface' => 5,       // Medium weight: Could also appear in class diagrams
            'port' => 8            // High weight: Specific to component diagrams but could be variable name
        ],
        self::TYPE_STATE => [
            'state' => 10,         // Highest weight: Definitive state diagram keyword
            '[*]' => 8             // High weight: Specific state diagram notation for start/end states
        ],
        self::TYPE_OBJECT => [
            'object' => 10,        // Highest weight: Definitive object diagram keyword
            'map' => 8             // High weight: Common in object diagrams but could be used elsewhere
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

        // Remove common elements that shouldn't affect type detection
        $cleanText = preg_replace([
            '/@startuml(\s|\n)/',   // Remove start tag
            '/@enduml(\s|\n)?/',    // Remove end tag
            '/title\s+"[^"]*"/',    // Remove title
            '/note\s+[^{]*{[^}]*}/', // Remove notes
            '/\'[^\n]*/',           // Remove single-line comments
            '/\s+/'                 // Normalize whitespace
        ], ' ', $plantUmlText);

        $cleanText = trim($cleanText);

        // If after cleaning there's almost nothing left, it's unknown
        if (strlen($cleanText) < 5) {
            return self::TYPE_UNKNOWN;
        }

        // Count indicators for each diagram type with weights
        $typeCounts = [];
        foreach (self::TYPE_INDICATORS as $type => $indicators) {
            $typeCounts[$type] = 0;

            foreach ($indicators as $indicator => $weight) {
                // Calculate the number of occurrences and adjust counters
                $count = substr_count($cleanText, $indicator);
                $typeCounts[$type] += $count * $weight;
            }
        }

        // Get diagram type with the highest weighted count
        arsort($typeCounts);
        $detectedType = key($typeCounts);
        $maxCount = current($typeCounts);

        // Return unknown if the score is too low
        if ($maxCount < 5) {  // Increased threshold further
            return self::TYPE_UNKNOWN;
        }

        // Get the second highest score
        next($typeCounts);
        $secondScore = current($typeCounts);
        
        // If the difference between top scores is too small, consider it ambiguous
        if ($secondScore && ($maxCount - $secondScore) < 8) {
            return self::TYPE_UNKNOWN;
        }

        return $detectedType;
    }
}
