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
    public function getReflectionRepresentations()
    {
        $origExceptionMessageClass    = 'ReflectionClassConstant';
        $origExceptionMessageTemplate = 'Error calling %s::methodName() do re mi';
        $previousException            = new \Exception('Testing...', 7);
        $generator                    = (function () {
            foreach ([1, 2, 3, 4] as $number) {
                yield $number;
            }
        })();
        $createArrayReflectionType = (function () {
            $refParam = new \ReflectionParameter(function (array $foo) {}, 'foo');
            return $refParam->getType();
        });
        $namedReflectionTypeClass = 'ReflectionType';
        if (class_exists('ReflectionNamedType')) {
            $refRefNamedType = new \ReflectionClass('ReflectionNamedType');
            if (!$refRefNamedType->isUserDefined()) {
                $namedReflectionTypeClass = get_class($createArrayReflectionType());
            }

        }
        $constructorArgs = [
            'ReflectionClass' =>
            [
                'class'         => 'ReflectionClass',
                'argList'       => ['name' => 'DateTime'],
                'filterStrings' => false,
            ],
            'ReflectionException' =>
            [
                'class'         => 'ReflectionException',
                'argList'       => [
                    'message'  => sprintf($origExceptionMessageTemplate, $origExceptionMessageClass),
                    'code'     => 42,
                    'previous' => $previousException,
                ],
                'filterStrings' => true,
            ],
            'ReflectionException without optional arguments' =>
            [
                'class'         => 'ReflectionException',
                'argList'       => ['message' => 'Testing abc123 bar'],
                'filterStrings' => true,
            ],
            'ReflectionException with single non-default optional argument' =>
            [
                'class'         => 'ReflectionException',
                'argList'       => [
                    'message'  => 'Testing abc123 bar',
                    'code'     => 0,
                    'previous' => $previousException,
                ],
                'filterStrings' => true,
            ],
            'ReflectionClassConstant' =>
            [
                'class'         => 'ReflectionClassConstant',
                'argList'       => ['class' => 'DateTime', 'name' => 'ATOM'],
                'filterStrings' => false,
            ],
            'ReflectionExtension' =>
            [
                'class'         => 'ReflectionExtension',
                'argList'       => ['name' => 'calendar'],
                'filterStrings' => false,
            ],
            'ReflectionFunction' =>
            [
                'class'         => 'ReflectionFunction',
                'argList'       => ['name' => 'preg_match'],
                'filterStrings' => false,
            ],
            'ReflectionFunction with closure' =>
            [
                'class'         => 'ReflectionFunction',
                'argList'       => ['name' => (function () {})],
                'closureLines'  => (__LINE__ - 1),
                'filterStrings' => false,
            ],
            'ReflectionMethod' =>
            [
                'class'         => 'ReflectionMethod',
                'argList'       => ['class' => 'DateTime', 'name' => 'setTime'],
                'filterStrings' => false,
            ],
            'ReflectionParameter' =>
            [
                'class'         => 'ReflectionParameter',
                'argList'       => ['function' => 'preg_match', 'parameter' => 'subject'],
                'filterStrings' => false,
            ],
            'ReflectionParameter for method' =>
            [
                'class'         => 'ReflectionParameter',
                'argList'       => ['function' => ['DateTime', 'setTime'], 'parameter' => 'minute'],
                'filterStrings' => false,
            ],
            'ReflectionParameter for unbound closure' =>
            [
                'class'         => 'ReflectionParameter',
                'argList'       => [
                    'function' => \Closure::bind(
                        function ($fee, $figh, $foe) {
                            // Make clousure 3 lines long.
                        }, null, null),
                    'parameter' => 'foe'
                ],
                'closureLines'  => (__LINE__ - 5) . '-' . (__LINE__ - 3),
                'filterStrings' => false,
            ],
            'ReflectionParameter for bound closure' =>
            [
                'class'         => 'ReflectionParameter',
                'argList'       => [
                    'function'      => function ($fee, $figh, $foe) {},
                    'parameter'     => 'foe'
                ],
                'closureLines'  => (__LINE__ - 3),
                'skipArgList'   => true,
                'displayValues' => [
                    'function'      => ['type'  => 'Closure'],
                    'parameter'     => ['value' => 'foe'],
                ],
                'filterStrings' => false,
            ],
            'ReflectionProperty' =>
            [
                'class'         => 'ReflectionProperty',
                'argList'       => [
                    'class'         => 'Exception',
                    'name'          => 'message',
                ],
                'filterStrings' => false,
            ],
            'ReflectionGenerator' =>
            [
                'class'         => 'ReflectionGenerator',
                'argList'       => [
                    'generator'     => $generator,
                ],
                'filterStrings' => false,
            ],
            "$namedReflectionTypeClass for array" =>
            [
                'class'         => $namedReflectionTypeClass,
                'createFunc'    => $createArrayReflectionType,
                'skipArgList'   => true,
                'displayValues' => [
                    'name'          => ['value' => 'array'],
                    'isNullable'    => ['value' => false],
                    'isBuiltin'     => ['value' => true],
                    'asString'      => ['value' => 'array'],
                ],
                'filterStrings' => false,
            ],
            "$namedReflectionTypeClass for nullable callable" =>
            [
                'class'         => $namedReflectionTypeClass,
                'createFunc'    => (function () {
                    $refParam = new \ReflectionParameter(function (callable $foo = null) {}, 'foo');
                    return $refParam->getType();
                }),
                'skipArgList'   => true,
                'displayValues' => [
                    'name'          => ['value' => 'callable'],
                    'isNullable'    => ['value' => true],
                    'isBuiltin'     => ['value' => true],
                    'asString'      => ['value' => 'callable'],
                ],
                'filterStrings' => false,
            ],
            "$namedReflectionTypeClass for nullable user defined class" =>
            [
                'class'         => $namedReflectionTypeClass,
                'createFunc'    => (function () {
                    $refParam = new \ReflectionParameter(function (ParsedEquivilantComparitorTestBase $foo = null) {}, 'foo');
                    return $refParam->getType();
                }),
                'skipArgList'   => true,
                'displayValues' => [
                    'name'          => ['value' => ParsedEquivilantComparitorTestBase::class],
                    'isNullable'    => ['value' => true],
                    'isBuiltin'     => ['value' => false],
                    'asString'      => ['value' => ParsedEquivilantComparitorTestBase::class],
                ],
                'filterStrings' => false,
            ],
        ];
        $result = [];
        foreach ($constructorArgs as $testCase => $constructorInfo) {
            $newCase = [
                '$class'                  => $constructorInfo['class'],
                '$createFunc'             => 'strval', // Placeholder.
                '$expectedRepresentation' => ['class' => $constructorInfo['class']],
                '$skipTestMessage'        => null,
                '$filterStrings'          => $constructorInfo['filterStrings'],
            ];
            $classReflection = null;
            if (class_exists($constructorInfo['class'])) {
                $classReflection = new \ReflectionClass($constructorInfo['class']);
            }
            if (!$classReflection || $classReflection->isUserDefined()) {
                $newCase['$skipTestMessage'] = "Class {$constructorInfo['class']} not available in this PHP version.";
            }
            else {
                if (!array_key_exists('createFunc', $constructorInfo)) {
                    $constructorInfo['createFunc'] = (function () use ($classReflection, $constructorInfo) {
                        return $classReflection->newInstanceArgs(array_values($constructorInfo['argList']));
                    });
                }
                $newCase['$createFunc'] = $constructorInfo['createFunc'];
                if (
                    !array_key_exists('skipArgList', $constructorInfo) ||
                    !$constructorInfo['skipArgList']
                ) {
                    $newCase['$expectedRepresentation']['constructorArgs'] = $constructorInfo['argList'];
                }
                if (!array_key_exists('displayValues', $constructorInfo)) {
                    $displayValues = [];
                    foreach ($constructorInfo['argList'] as $argName => $argValue) {
                        $displayValues[$argName] = ['value' => $argValue];
                    }
                    $constructorInfo['displayValues'] = $displayValues;
                }
                foreach ($constructorInfo['displayValues'] as $propName => $propInfo) {
                    if (
                        array_key_exists('value', $propInfo) &&
                        ($propInfo['value'] instanceof \Closure) &&
                        !array_key_exists('type', $propInfo)
                    ) {
                        // Prepend key 'type'.
                        $propInfo = array_merge(['type'  => 'Closure'], $propInfo);
                    }
                    if (array_key_exists('type', $propInfo) && ($propInfo['type'] == 'Closure')) {
                       $propInfo['file']  = __FILE__;
                       $propInfo['lines'] = $constructorInfo['closureLines'];
                    }
                    if (
                        !array_key_exists('type', $propInfo) &&
                        array_key_exists('value', $propInfo)
                    ) {
                        // Prepend key 'type'.
                        $propInfo = array_merge(['type'  => 'value'], $propInfo);
                    }
                    $constructorInfo['displayValues'][$propName] = $propInfo;
                }
                $newCase['$expectedRepresentation']['displayValues'] = $constructorInfo['displayValues'];
            }
            $result[$testCase] = $newCase;
        }
        return $result;
    }

    public function getExpectedSerializerOutput()
    {
        $origExceptionMessageClass    = 'ReflectionClassConstant';
        $origExceptionMessageTemplate = 'Error calling %s::methodName() do re mi';
        $previousException            = new \Exception('Testing...', 7);
        $reflectionException = new \ReflectionException(
            sprintf($origExceptionMessageTemplate, $origExceptionMessageClass),
            42,
            $previousException);
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
                        '/(?:^|(?<==>))(\\s+)Array \\&2/',
                    ],
                    [
                        '\\1Go\\\\ParserReflection\\\\ReflectionException Object &1',
                        '\\1Exception Object &2',
                    ],
                    $exporter->export([
                        'foo' => [
                            'message'  => sprintf(
                                $origExceptionMessageTemplate,
                                'Go\\ParserReflection\\' . $origExceptionMessageClass),
                            'code'     => 42,
                            'previous' => [
                                'message' => 'Testing...',
                                'code'    => 7,
                            ],
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
            'ReflectionException with single optional argument' =>
            [
                '$value'                   => new \ReflectionException(
                    'Testing abc123 bar',
                    0,
                    $previousException),
                '$transformer'             => new TextTransformer([['/abc123/', 'foo']]),
                '$expectedStringification' => preg_replace(
                    [
                        '/^Array \\&0/',
                        '/(?:^|(?<==>))(\\s+)Array \\&1/',
                    ],
                    [
                        '\\1Go\\\\ParserReflection\\\\ReflectionException Object &0',
                        '\\1Exception Object &1',
                    ],
                    $exporter->export([
                        'message'  => 'Testing foo bar',
                        'code'     => 0,
                        'previous' => [
                            'message' => 'Testing...',
                            'code'    => 7,
                        ],
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
