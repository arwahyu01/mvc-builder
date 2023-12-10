<?php

namespace Arwp\Mvc\utilities;

use Illuminate\Support\Str;

trait StringUtil
{
    /**
     * Clean the specified string.
     *
     * @param string $field
     * @param string $replaced
     * @return string
     */
    public static function cleanString($field, $replaced): string
    {
        return Str::replace(
            ['<', '>', '/', ' ', '-', '_', '(', ')', '[', ']', '{', '}', ':', ';', '"', "'", ',', '.', '?', '!', '@', '#', '$', '%', '^', '&', '*', '+', '=', '|', '\\', '`', '~'],
            $replaced,
            $field
        );
    }

    /**
     * Replace the end of line with indentation.
     *
     * @param string $input
     * @param int $indentation (default: 2)
     * @return string
     */
    public static function replaceEOLWithIndentation($input, $indentation = 2)
    {
        $indent = str_repeat("\t", $indentation);
        return Str::replace(PHP_EOL, PHP_EOL . $indent, $input);
    }
}
