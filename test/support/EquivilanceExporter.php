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
use InvalidArgumentException;

class EquivilanceExporter extends Exporter
{
    /**
     * @var TextTransformer  Whatever text transformations should be performed
     *                       on string value components prior to comparison.
     */
    protected $stringTransformer = null;
    /**
     * @var ReflectionMetaInfo  Object to query about classes under test.
     */
    protected $metaInfo = null;

    /**
     * Construct the exporter for value equivilance.
     *
     * @param TextTransformer  $transformer  The name of the native reflection class.
     */
    public function __construct(
        TextTransformer $transformer = null,
        ReflectionMetaInfo $metaInfo = null)
    {
        $this->transformer = $transformer;
        $this->metaInfo = $metaInfo ?: new ReflectionMetaInfo();
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
            $valueIsReflectorObject =
                ($value instanceof \Reflector) ||
                ($value instanceof \ReflectionException);
            $origClass = get_class($value);
            $class     = $valueIsReflectorObject ? $this->metaInfo->getParsedClass($origClass) : $origClass;
            $origHash  = $processed->contains($value);
            if ($origHash) {
                $hash = $processed->equivilantKeyLookup[$origHash];
                return sprintf('%s Object &%s', $class, $hash);
            }
            $reflectionInfo = $this->metaInfo->getReflectionRepresentation($value);
            $displayValues  = $reflectionInfo['displayValues'];
            if ($valueIsReflectorObject) {
                foreach ($displayValues as $argName => $argValInfo) {
                    if (($argValInfo['type'] == 'value') && is_string($argValInfo['value'])) {
                        $displayValues[$argName]['value'] = $this->metaInfo->replaceNativeClasses($argValInfo['value']);
                    }
                }
            }
            $properties = [];
            foreach ($displayValues as $argName => $argValInfo) {
                if (($argValInfo['type'] != 'value')) {
                    throw new \Exception('Not implemented yet.');
                }
                $properties[$argName] = $argValInfo['value'];
            }
            $nested                         = isset($processed->shortenNestedOutput);
            $processed->shortenNestedOutput = true;
            $rawOut                         = parent::recursiveExport($properties, $indentation, $processed);
            $origHash                       = $processed->add($value);
            $hash                           = $processed->contains($properties);
            if (!$nested) {
                unset($processed->shortenNestedOutput);
            }
            $processed->equivilantKeyLookup[$origHash] = $hash;
            if (($hash === false) || is_null($hash)) {
                throw new \Exception('INTERNAL ERROR: Array should have already been added to $processed.');
            }
            return preg_replace(
                '/^Array (\\&[0-9a-fA-F.]+)/',
                str_replace('\\', '\\\\', $class) . ' Object &' . $hash,
                $rawOut);
        }
        return parent::recursiveExport($value, $indentation, $processed);
    }
}
