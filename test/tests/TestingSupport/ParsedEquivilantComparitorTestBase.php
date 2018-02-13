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
    public function getTestableConstructorArgLists()
    {
        $origExceptionMessageClass    = 'ReflectionClassConstant';
        $origExceptionMessageTemplate = 'Error calling %s::methodName() do re mi';
        $previousException            = new \Exception('Testing...', 7);
        $generator                    = (function () {
            foreach ([1, 2, 3, 4] as $number) {
                yield $number;
            }
        })();
        return [
            'ReflectionClass' =>
            [
                '$class'         => 'ReflectionClass',
                '$argList'       => ['name' => 'DateTime'],
                '$filterStrings' => false,
            ],
            'ReflectionException' =>
            [
                '$class'         => 'ReflectionException',
                '$argList'       => [
                    'message'  => sprintf($origExceptionMessageTemplate, $origExceptionMessageClass),
                    'code'     => 42,
                    'previous' => $previousException
                ],
                '$filterStrings' => true,
            ],
            'ReflectionException without optional arguments' =>
            [
                '$class'         => 'ReflectionException',
                '$argList'       => ['message' => 'Testing abc123 bar'],
                '$filterStrings' => true,
            ],
            'ReflectionException with single non-default optional argument' =>
            [
                '$class'         => 'ReflectionException',
                '$argList'       => [
                    'message'  => 'Testing abc123 bar',
                    'code'     => 0,
                    'previous' => $previousException
                ],
                '$filterStrings' => true,
            ],
            'ReflectionClassConstant' =>
            [
                '$class'         => 'ReflectionClassConstant',
                '$argList'       => ['class' => 'DateTime', 'name' => 'ATOM'],
                '$filterStrings' => false,
            ],
            'ReflectionExtension' =>
            [
                '$class'         => 'ReflectionExtension',
                '$argList'       => ['name' => 'calendar'],
                '$filterStrings' => false,
            ],
            'ReflectionFunction' =>
            [
                '$class'         => 'ReflectionFunction',
                '$argList'       => ['name' => 'preg_match'],
                '$filterStrings' => false,
            ],
            'ReflectionFunction with closure' =>
            [
                '$class'         => 'ReflectionFunction',
                '$argList'       => ['name' => (function () {})],
                '$filterStrings' => false,
            ],
            'ReflectionMethod' =>
            [
                '$class'         => 'ReflectionMethod',
                '$argList'       => ['class' => 'DateTime', 'name' => 'setTime'],
                '$filterStrings' => false,
            ],
            'ReflectionParameter' =>
            [
                '$class'         => 'ReflectionParameter',
                '$argList'       => ['function' => 'preg_match', 'parameter' => 'subject'],
                '$filterStrings' => false,
            ],
            'ReflectionParameter for method' =>
            [
                '$class'         => 'ReflectionParameter',
                '$argList'       => ['function' => ['DateTime', 'setTime'], 'parameter' => 'minute'],
                '$filterStrings' => false,
            ],
            'ReflectionParameter for unbound closure' =>
            [
                '$class'         => 'ReflectionParameter',
                '$argList'       => [
                    'function' => \Closure::bind(function ($fee, $figh, $foe) {}, null, null),
                    'parameter' => 'foe'
                ],
                '$filterStrings' => false,
            ],
            // 'BROKEN ReflectionParameter for bound closure' =>
            // [
            //     '$class'         => 'ReflectionParameter',
            //     '$argList'       => [
            //         'function' => function ($fee, $figh, $foe) {},
            //         'parameter' => 'foe'
            //     ],
            //     '$filterStrings' => false,
            // ],
            'ReflectionProperty' =>
            [
                '$class'         => 'ReflectionProperty',
                '$argList'       => [
                    'class' => 'Exception',
                    'name'  => 'message'
                ],
                '$filterStrings' => false,
            ],
            'ReflectionGenerator' =>
            [
                '$class'         => 'ReflectionGenerator',
                '$argList'       => [
                    'generator' => $generator
                ],
                '$filterStrings' => false,
            ],
        ];
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
