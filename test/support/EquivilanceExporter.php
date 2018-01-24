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
        if (!$processed) {
            $processed = new Context;
        }
        if (!isset($processed->equivilantKeyLookup)) {
            $processed->equivilantKeyLookup = [];
        }
        if (is_object($value) && isset($processed->shortenNestedOutput)) {
            return $this->shortenedExport($value);
        }
        if (($value instanceof \Reflector) || ($value instanceof \ReflectionException)) {
            $origClass                    = get_class($value);
            $equivilantClass              = EquivilanceConstraint::getParsedClass($origClass);
            if ($hash = $processed->contains($value)) {
                $equivilantHash = $processed->equivilantKeyLookup[$hash];
                return sprintf('%s Object &%s', $equivilantClass, $equivilantHash);
            }
            $constructorParamsByClassName = [
                'ReflectionClass'         => ['name'],
                'ReflectionClassConstant' => ['class:declaringClass->name', 'name'],
                'ReflectionZendExtension' => ['name'],
                // ...
                'ReflectionException'     => ['message', 'code(0)', 'previous(null)'],
            ];
            if (!array_key_exists($origClass, $constructorParamsByClassName)) {
                throw new \Exception(sprintf('INTERNAL ERROR: EquivilanceExport params for class %s not implemented.', $origClass));
            }
            $transformedValue = [];
            foreach ($constructorParamsByClassName[$origClass] as $paramNameSpec) {
                $matches      = [];
                $defaultValue = null;
                if (preg_match('/^([^(]*)\\((.*)\\)$/', $paramNameSpec, $matches)) {
                    $paramNameSpec = $matches[1];
                    $defaultValue  = $matches[2];
                }
                if (strpos($paramNameSpec, ':') === false) {
                    $parameterName = $paramNameSpec;
                    $propertyPath  = $paramNameSpec;
                }
                else {
                    list($parameterName, $propertyPath) = explode(':', $paramNameSpec);
                }
                $paramVal = $value;
                foreach (explode('->', $propertyPath) as $propertyName) {
                    $methodName = 'get' . ucfirst($propertyName);
                    $paramVal   = $paramVal->$methodName();
                }
                if (($value instanceof \Exception) && is_string($paramVal)) {
                    $paramVal = EquivilanceConstraint::replaceNativeClasses($paramVal);
                }
                if (!strlen($defaultValue) || ($paramVal !== eval("return $defaultValue;"))) {
                    $transformedValue[$parameterName] = $paramVal;
                }
            }
            $processed->shortenNestedOutput = true;
            $rawOut                         = parent::recursiveExport($transformedValue, $indentation, $processed);
            $hash                           = $processed->add($value);
            $equivilantHash                 = $processed->contains($transformedValue);
            unset($processed->shortenNestedOutput);
            if ($equivilantHash === false) {
                throw new \Exception('INTERNAL ERROR: Array should have already been added to $processed.');
            }
            $processed->equivilantKeyLookup[$hash] = $equivilantHash;
            return preg_replace(
                '/^Array (\\&[0-9a-fA-F.]+)/',
                str_replace('\\', '\\\\', $equivilantClass) . ' Object &' . $equivilantHash,
                $rawOut);
        }
        return parent::recursiveExport($value, $indentation, $processed);
    }
}
