<?php
/**
 * Parser Reflection API
 *
 * @copyright Copyright 2016, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Go\ParserReflection\TestingSupport;

use InvalidArgumentException;

/**
 * Source of some meta information about the classes we're testing.
 */
class ReflectionMetaInfo
{

    /**
     * Check if the input is a native reflection class.
     *
     * @param string $nativeClass The name of the native reflection class.
     *
     * @return boolean Is a native class?
     */
    public function isNativeClass($nativeClass)
    {
        return preg_match('/^\\\\?Reflect(ion([A-Z]\\w*)?|or)$/', $nativeClass);
    }

    /**
     * Returns the parsed reflection class equivilant to the given native class.
     *
     * @param string $nativeClass The name of the native reflection class.
     *
     * @return string The equivilant parsed class.
     */
    public function getParsedClass($nativeClass)
    {
        if (!$this->isNativeClass($nativeClass)) {
            throw new InvalidArgumentException("$nativeClass not a builtin Reflection class.");
        }
        return preg_replace('/^(\\\\?)/', '\\1Go\\\\ParserReflection\\\\', $nativeClass);
    }

    /**
     * Returns the string input with parsed reflection classes replaced with native equivilants.
     *
     * @param string $input String that may contain parsed reflection class names.
     *
     * @return string Tranformed output.
     */
    public function replaceNativeClasses($input)
    {
        return preg_replace(
                    '/((?<![a-zA-Z0-9_\\x7f-\\xff])\\\\+|[^\\\\]|^)\\b(Reflect(ion([A-Z]\\w*)?|or))\\b/',
                    '\\1Go\\\\ParserReflection\\\\\\2',
                    $input);
    }

    /**
     * Check if the input is a parsed reflection class.
     *
     * @param string $nativeClass The name of the parsed reflection class.
     *
     * @return boolean Is a parsed class?
     */
    public function isParsedClass($parsedClass)
    {
        return preg_match('/^\\\\?Go\\\\ParserReflection\\\\Reflect(ion([A-Z]\\w*)?|or)$/', $parsedClass);
    }

    /**
     * Returns the native reflection class equivilant to the given parsed class.
     *
     * @param string $parsedClass The name of the native reflection class.
     *
     * @return string The equivilant native class.
     */
    public function getNativeClass($parsedClass)
    {
        if (!$this->isParsedClass($parsedClass)) {
            throw new InvalidArgumentException("$parsedClass not a parsed Reflection class.");
        }
        return preg_replace('/^(\\\\?)Go\\\\ParserReflection\\\\/', '\\1', $parsedClass);
    }

    /**
     * Returns the string input with native reflection classes replaced with parsed equivilants.
     *
     * @param string $input String that may contain native reflection class names.
     *
     * @return string Tranformed output.
     */
    public function replaceParsedClasses($input)
    {
        return preg_replace(
                    '/((?<![a-zA-Z0-9_\\x7f-\\xff])\\\\+|[^\\\\]|^)\\bGo\\\\+ParserReflection\\\\+(Reflect(ion([A-Z]\\w*)?|or))\\b/',
                    '\\1\\2',
                    $input);
    }
}
