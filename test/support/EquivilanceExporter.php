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

use SebastianBergmann\Exporter\Exporter;
use SebastianBergmann\RecursionContext\Context;
use Go\ParserReflection\TestingSupport\PHPUnit\Constraint\IsParsedEquivilantToReflectionValue as EquivilanceConstraint;
use InvalidArgumentException;

class EquivilanceExporter extends Exporter
{
    /**
     * @var TextTransformer  Whatever text transformations should be performed
     *                       on string value components prior to comparison.
     */
    protected $stringTransformer = null;

    /**
     * Construct the exporter for value equivilance.
     *
     * @param TextTransformer  $transformer  The name of the native reflection class.
     */
    public function __construct(TextTransformer $transformer = null)
    {
        $this->transformer = $transformer;
    }

    /**
     * Recursive implementation of export
     *
     * @param  mixed    $value        The value to export
     * @param  int      $indentation  The indentation level of the 2nd+ line
     * @param  Context  $processed    Previously processed objects
     * @return string
     * @see    SebastianBergmann\Exporter\Exporter::export
     */
    protected function recursiveExport(&$value, $indentation, $processed = null)
    {
        if (is_string($value) && $this->transformer) {
            $transformedValue = $this->transformer->filter($value);
            if ($this->transformer->filter($transformedValue) !== $transformedValue) {
                throw new InvalidArgumentException('Provided TextTransformer is not indempotent.');
            }
            return parent::recursiveExport($transformedValue, $indentation, $processed);
        }
        if (($value instanceof \Reflector) || ($value instanceof \ReflectionException)) {
            $origClass                    = get_class($value);
            $expectedClass                = EquivilanceConstraint::getParsedClass($origClass);
            $constructorParamsByClassName = [
                'ReflectionClass'     => ['name'],
                'ReflectionException' => ['message', 'code', 'file', 'line'],
            ];
            if (!array_key_exists($origClass, $constructorParamsByClassName)) {
                throw new \Exception(sprintf('INTERNAL ERROR: EquivilanceExport params for class %s not implemented.', $origClass));
            }
            $transformedValue = [];
            foreach ($constructorParamsByClassName[$origClass] as $paramName) {
                $methodName = 'get' . ucfirst($paramName);
                $paramVal   = $value->$methodName();
                if (is_string($paramVal)) {
                    $paramVal = EquivilanceConstraint::replaceNativeClasses($paramVal);
                }
                $transformedValue[$paramName] = $paramVal;
            }
            $rawOut = parent::recursiveExport($transformedValue, $indentation, $processed);
            return preg_replace(
                '/^Array \\&[0-9a-f.]+/',
                str_replace('\\', '\\\\', $expectedClass) . ' Object',
                $rawOut);
        }
        return parent::recursiveExport($value, $indentation, $processed);
    }
}
