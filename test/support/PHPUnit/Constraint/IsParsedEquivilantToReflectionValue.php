<?php
/**
 * Parser Reflection API
 *
 * @copyright Copyright 2016, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Go\ParserReflection\TestingSupport\PHPUnit\Constraint;

use PHPUnit_Framework_Constraint;
use Go\ParserReflection\TestingSupport\TextTransformer;
use Go\ParserReflection\TestingSupport\EquivilanceExporter;
use InvalidArgumentException;

class IsParsedEquivilantToReflectionValue extends PHPUnit_Framework_Constraint
{
    /**
     * @var mixed  The value being compared to.
     */
    protected $value;

    /**
     * @var TextTransformer  Whatever text transformations should be performed
     *                       on string value components prior to comparison.
     */
    protected $stringTransformer;

    /**
     * Construct the comparitor reflection class equivilant to the given value.
     *
     * @param mixed            $value        The name of the native reflection class.
     * @param TextTransformer  $transformer  The name of the native reflection class.
     */
    public function __construct($value, TextTransformer $transformer = null)
    {
        $this->value       = $value;
        $this->transformer = $transformer;
        $this->exporter    = new EquivilanceExporter($this->transformer);
        // Confirm we can export without an exception:
        $this->exporter->export($this->value);
    }

    /**
     * Returns the parsed reflection class equivilant to the given native class.
     *
     * @param string $nativeClass The name of the native reflection class.
     *
     * @return string The equivilant parsed class.
     */
    public static function getParsedClass($nativeClass)
    {
        if (!preg_match('/^\\\\?Reflect(ion([A-Z]\\w*)?|or)$/', $nativeClass)) {
            throw new InvalidArgumentException("$nativeClass not a builtin Reflection class.");
        }
        return preg_replace('/^(\\\\?)/', '\\1Go\\\\ParserReflection\\\\', $nativeClass);
    }

    /**
     * Returns the native reflection class equivilant to the given parsed class.
     *
     * @param string $parsedClass The name of the native reflection class.
     *
     * @return string The equivilant native class.
     */
    public static function getNativeClass($parsedClass)
    {
        if (!preg_match('/^\\\\?Go\\\\ParserReflection\\\\Reflect(ion([A-Z]\\w*)?|or)$/', $parsedClass)) {
            throw new InvalidArgumentException("$parsedClass not a parsed Reflection class.");
        }
        return preg_replace('/^(\\\\?)Go\\\\ParserReflection\\\\/', '\\1', $parsedClass);
    }

    /**
     * Returns the comparison type.
     *
     * @return string Either 'identical' or 'equivilant'.
     */
    public function getComparisonType()
    {
        return $this->getInternalComparisonType($this->value);
    }

    /**
     * Returns the comparison type.
     *
     * @return string Either 'identical' or 'equivilant'.
     */
    private function getInternalComparisonType($value)
    {
        if (($value instanceof \Reflector) || ($value instanceof \ReflectionException)) {
            return 'equivilant';
        }
        if (is_array($value)) {
            foreach ($value as $element) {
                $compType = $this->getInternalComparisonType($element);
                if ($compType != 'identical') {
                    return $compType;
                }
            };
        }
        return 'identical';
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString()
    {
        return sprintf(
            'is %s to %s',
            $this->getComparisonType(),
            $this->exporter->export($this->value)
        );
        // ------

        // if (is_object($this->value)) {
        //     if (($this->value instanceof \Reflector) || ($this->value instanceof \ReflectionException)) {
        //         return 'is identical to ' .
        //                $this->exporter->export($this->value);
        //     } else {
        //         return 'is identical to an object of class "' .
        //                get_class($this->value) . '"';
        //     }
        // } else {
        //     return 'is identical to ' .
        //            $this->exporter->export($this->value);
        //     if (is_string($this->value)) {
        //         if (strpos($this->value, "\n") !== false) {
        //             return 'is equal to <text>';
        //         } else {
        //             return sprintf(
        //                 'is equal to <string:%s>',
        //                 $this->value
        //             );
        //         }
        //     } else {
        //         return sprintf(
        //             'is equal to %s',
        //             $this->exporter->export($this->value)
        //         );
        //     }
        // }
    }
}
