<?php
/**
 * Parser Reflection API
 *
 * @copyright Copyright 2016, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Go\ParserReflection\Tests\TestingSupport;

use Go\ParserReflection\Tests\TestCaseBase;
use Go\ParserReflection\TestingSupport\TextTransformer;
use SebastianBergmann\Exporter\Exporter;

class ParsedEquivilantComparitorTestBase extends TestCaseBase
{
    public function getConstructorValuesWithExpectedState()
    {
        $origExceptionMessageClass    = 'ReflectionClassConstant';
        $origExceptionMessageTemplate = 'Error calling %s::methodName() do re mi';
        $previousException            = new \Exception('Testing...', 7);
        try {
            // Make sure file, line and backtrace are populated.
            throw new \ReflectionException(
                sprintf($origExceptionMessageTemplate, $origExceptionMessageClass),
                42,
                $previousException);
        } catch (\ReflectionException $e) {
            $reflectionException = $e;
        }
        $reflectionClass         = new \ReflectionClass('DateTime');
        $reflectionClassConstant = class_exists('ReflectionClassConstant') ? new \ReflectionClassConstant('DateTime', 'ATOM') : null;
        $classConstantEquivilant = ['class'  => 'DateTime', 'name'   => 'ATOM'];
        $exporter                = new Exporter();
        return [
            'Array serialization' =>
            [
                '$value'                   => [1, 2, 3],
                '$transformer'             => null,
                '$expectedStringification' => $exporter->export([1,2,3]),
                '$expectedComparisonType'  => 'identical',
                '$testSkipReason'          => null,
            ],
            'TextTransformer on string' =>
            [
                '$value'                   => 'fee figh foo fum',
                '$transformer'             => new TextTransformer([['/foo/', 'bar']]),
                '$expectedStringification' => $exporter->export('fee figh bar fum'),
                '$expectedComparisonType'  => 'identical',
                '$testSkipReason'          => null,
            ],
            'ReflectionClass' =>
            [
                '$value'                   => $reflectionClass,
                '$transformer'             => null,
                '$expectedStringification' => preg_replace(
                    '/^Array \\&0/',
                    'Go\\\\ParserReflection\\\\ReflectionClass Object &0',
                    $exporter->export(['name' => 'DateTime'])),
                '$expectedComparisonType'  => 'equivilant',
                '$testSkipReason'          => null,
            ],
            'ReflectionException in an array' =>
            [
                '$value'                   => ['foo' => $reflectionException, 'bar' => 'abcde'],
                '$transformer'             => null,
                '$expectedStringification' => preg_replace(
                    [
                        '/(?:^|(?<==>))(\\s+)Array \\&1/',
                        '/\\b1234598765\\b/',
                    ],
                    [
                        '\\1Go\\\\ParserReflection\\\\ReflectionException Object &1',
                        $exporter->shortenedExport($previousException),
                    ],
                    $exporter->export([
                        'foo' => [
                            'message'  => sprintf(
                                $origExceptionMessageTemplate,
                                'Go\\ParserReflection\\' . $origExceptionMessageClass),
                            'code'     => 42,
                            'previous' => 1234598765,
                        ],
                        'bar' => 'abcde'])),
                '$expectedComparisonType'  => 'equivilant',
                '$testSkipReason'          => null,
            ],
            'ReflectionException without optional arguments' =>
            [
                '$value'                   => new \ReflectionException('Testing abc123 bar'),
                '$transformer'             => new TextTransformer([['/abc123/', 'foo']]),
                '$expectedStringification' => preg_replace(
                    '/^Array \\&0/',
                    '\\1Go\\\\ParserReflection\\\\ReflectionException Object &0',
                    $exporter->export([
                        'message'  => 'Testing foo bar',
                    ])),
                '$expectedComparisonType'  => 'equivilant',
                '$testSkipReason'          => null,
            ],
            'ReflectionClassConstant in data structure more than once' =>
            [
                '$value'                   => [$reflectionClassConstant, $reflectionClassConstant],
                '$transformer'             => null,
                '$expectedStringification' => preg_replace(
                    '/(?:^|(?<==>))(\\s+)Array \\&1/',
                    '\\1Go\\\\ParserReflection\\\\ReflectionClassConstant Object &1',
                    $exporter->export([
                        &$classConstantEquivilant,
                        &$classConstantEquivilant])),
                '$expectedComparisonType'  => 'equivilant',
                '$testSkipReason'          => $reflectionClassConstant ? null : 'Class ReflectionClassConstant not available in this PHP version.',
            ],
        ];
        return $result;
    }
}
