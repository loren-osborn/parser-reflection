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
        try {
            // Make sure file, line and backtrace are populated.
            throw new \ReflectionException(
                sprintf($origExceptionMessageTemplate, $origExceptionMessageClass),
                42,
                new \Exception('Testing...', 7));
        } catch (\ReflectionException $e) {
            $reflectionException = $e;
        }
        $reflectionClass = new \ReflectionClass('DateTime');
        $exporter = new Exporter();
        return [
            [
                '$value'                   => [1, 2, 3],
                '$transformer'             => null,
                '$expectedStringification' => $exporter->export([1,2,3]),
                '$expectedComparisonType'  => 'identical',
            ],
            [
                '$value'                   => 'fee figh foo fum',
                '$transformer'             => new TextTransformer([['/foo/', 'bar']]),
                '$expectedStringification' => $exporter->export('fee figh bar fum'),
                '$expectedComparisonType'  => 'identical',
            ],
            [
                '$value'                   => $reflectionClass,
                '$transformer'             => null,
                '$expectedStringification' => preg_replace(
                    '/^Array \\&0/',
                    'Go\\\\ParserReflection\\\\ReflectionClass Object',
                    $exporter->export(['name' => 'DateTime'])),
                '$expectedComparisonType'  => 'equivilant',
            ],
            [
                '$value'                   => ['foo' => $reflectionException, 'bar' => 'abcde'],
                '$transformer'             => null,
                '$expectedStringification' => preg_replace(
                    '/(?:^|(?<==>))(\\s+)Array \\&1/',
                    '\\1Go\\\\ParserReflection\\\\ReflectionException Object',
                    $exporter->export([
                        'foo' => [
                            'message'  => sprintf(
                                $origExceptionMessageTemplate,
                                'Go\\ParserReflection\\' . $origExceptionMessageClass),
                            'code'     => 42,
                            'file'     => $reflectionException->getFile(),
                            'line'     => $reflectionException->getLine(),
                        ],
                        'bar' => 'abcde'])),
                '$expectedComparisonType'  => 'equivilant',
            ],
        ];
        return $result;
    }
}
