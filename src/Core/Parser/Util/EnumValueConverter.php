<?php

namespace App\Core\Parser\Util;

/**
 * Helper class to convert enum values to appropriate types
 */
class EnumValueConverter
{
    /**
     * Convert an enum value to the appropriate type (number if numeric)
     *
     * @param mixed $value The enum value
     * @return mixed The converted value
     */
    public static function convert($value): mixed
    {
        // If not a string, return as is
        if (!is_string($value)) {
            return $value;
        }

        // If it's a numeric string, convert to number
        if (is_numeric($value)) {
            // Force integer conversion for values without decimals
            if ((string)(int)$value === $value) {
                return (int)$value;
            }
            // Otherwise convert to float
            return (float)$value;
        }

        // Return original value for non-numeric strings
        return $value;
    }
}
