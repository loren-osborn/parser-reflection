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
     * Get constructor arguments that would yeild reflection class object.
     *
     * @param  Reflector|ReflectionException $obj  The object to inspect
     * @return array  Constructor arguments that would create equivilant object.
     *
     */
    private function getConstructorArgs($obj)
    {
        $constructorParamsByClassName = [
            'ReflectionClass'         => ['name'],
            'ReflectionClassConstant' => [
                [
                    'name'      => 'class',
                    'callChain' => ['getDeclaringClass', 'getName']
                ],
                'name'
            ],
            'ReflectionZendExtension' => ['name'],
            // ...
            'ReflectionException'     => [
                'message',
                [
                    'name'         => 'code',
                    'defaultValue' => 0,
                ],
                [
                    'name'         => 'previous',
                    'defaultValue' => null,
                ],
            ],
        ];
        $class = get_class($obj);
        if ($obj instanceof \Exception) {
            $class = 'ReflectionException';
        }
        if (!array_key_exists($class, $constructorParamsByClassName)) {
            throw new \Exception(sprintf('INTERNAL ERROR: EquivilanceExport params for class %s not implemented.', $class));
        }
        $result = [];
        foreach ($constructorParamsByClassName[$class] as $paramNameSpec) {
           $normalizedSpec = $paramNameSpec;
            if (is_string($paramNameSpec)) {
                $normalizedSpec = ['name' => $paramNameSpec];
            }
            if (!array_key_exists('getValueFrom', $normalizedSpec)) {
                if (!array_key_exists('callChain', $normalizedSpec)) {
                    $normalizedSpec['callChain'] = ['get' . ucfirst($normalizedSpec['name'])];
                }
                $normalizedSpec['getValueFrom'] = (function ($inObj) use ($normalizedSpec) {
                    $outVal = $inObj;
                    foreach ($normalizedSpec['callChain'] as $methodName) {
                        $outVal = $outVal->$methodName();
                    }
                    return $outVal;
                });
            }
            $getValueFrom = $normalizedSpec['getValueFrom'];
            $paramVal     = $getValueFrom($obj);
            if (
                !array_key_exists('defaultValue', $normalizedSpec) ||
                ($paramVal !== $normalizedSpec['defaultValue'])
            ) {
                $result[$normalizedSpec['name']] = $paramVal;
            }
        }
        return $result;
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
        if (
            ($value instanceof \Reflector) ||
            ($value instanceof \ReflectionException) ||
            (isset($processed->shortenNestedOutput) && ($value instanceof \Exception))
        ) {
            $equivilantClass = ($origClass = get_class($value));
            if (
                ($value instanceof \Reflector) ||
                ($value instanceof \ReflectionException)
            ) {
                $equivilantClass = EquivilanceConstraint::getParsedClass($origClass);
            }
            if ($hash = $processed->contains($value)) {
                $equivilantHash = $processed->equivilantKeyLookup[$hash];
                return sprintf('%s Object &%s', $equivilantClass, $equivilantHash);
            }
            $constructorArgs = $this->getConstructorArgs($value);
            if ($value instanceof \Exception) {
                foreach ($constructorArgs as $argName => $argVal) {
                    if (is_string($argVal)) {
                        $constructorArgs[$argName] = EquivilanceConstraint::replaceNativeClasses($argVal);
                    }
                }
            }
            $processed->shortenNestedOutput = true;
            $rawOut                         = parent::recursiveExport($constructorArgs, $indentation, $processed);
            $hash                           = $processed->add($value);
            $equivilantHash                 = $processed->contains($constructorArgs);
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
