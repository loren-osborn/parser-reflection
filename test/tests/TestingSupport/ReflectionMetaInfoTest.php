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

use Go\ParserReflection\Tests\TestingSupport\ParsedEquivilantComparitorTestBase;
use Go\ParserReflection\TestingSupport\ReflectionMetaInfo;
use InvalidArgumentException;

class ReflectionMetaInfoTest extends ParsedEquivilantComparitorTestBase
{
    /**
     * Tests ReflectionMetaInfo methods
     *
     * @dataProvider getValidMethodsWithSingleArgAndExpectedOutput
     *
     * @param string  $methodName      The method to call.
     * @param string  $argument        The argument to pass.
     * @param string  $expectedResult  The result value to expect.
     */
    public function testValidMethodsWithSingleArg(
        $methodName,
        $argument,
        $expectedResult)
    {
        $obj          = new ReflectionMetaInfo();
        $actualResult = $obj->$methodName($argument);
        $this->assertEquals($expectedResult, $actualResult, 'Correct class name transformation.');
    }

    /**
     * Tests ReflectionMetaInfo method exceptions
     *
     * @dataProvider getInvalidMethodsWithSingleArgAndExpectedExceptionMessage
     *
     * @param string  $methodName       The method to call.
     * @param string  $argument         The argument to pass.
     * @param string  $expectedMessage  The exception message to expect.
     */
    public function testInvalidMethodsWithSingleArg(
        $methodName,
        $argument,
        $expectedMessage)
    {
        $obj = new ReflectionMetaInfo();
        try {
            $obj->$methodName($argument);
            $this->fail(sprintf(
                'Method (obj %s)->%s(%s) should have thrown exception.',
                ReflectionMetaInfo::class,
                $methodName,
                var_export($argument, true)
            ));
        }
        catch (InvalidArgumentException $e) {
            $this->assertEquals(InvalidArgumentException::class, get_class($e), 'Expected exception class.');
            $this->assertContains($expectedMessage, $e->getMessage(), 'Correct exception message.');
        }
    }

    public function getReflectionClassPairsCases()
    {
        $nativeReflectionClasses = [
            'Reflection',
            'ReflectionClass',
            'ReflectionClassConstant',
            'ReflectionZendExtension',
            'ReflectionExtension',
            'ReflectionFunction',
            'ReflectionFunctionAbstract',
            'ReflectionMethod',
            'ReflectionNamedType',
            'ReflectionObject',
            'ReflectionParameter',
            'ReflectionProperty',
            'ReflectionType',
            'ReflectionGenerator',
            'ReflectionException',
            'Reflector',
        ];
        $result = [];
        foreach ($nativeReflectionClasses as $class) {
            $result["Bare $class"] = [
                '$nativeClass' => $class,
                '$parsedClass' => "Go\\ParserReflection\\$class",
            ];
            $result["Absolute $class"] = [
                '$nativeClass' => "\\$class",
                '$parsedClass' => "\\Go\\ParserReflection\\$class",
            ];
        }
        return $result;
    }

    public function getNonReflectionBuiltInClassCases()
    {
        $nonReflectionBuiltinClasses = [
            'Directory',
            'stdClass',
            '__PHP_Incomplete_Class',
            'Exception',
            'ErrorException',
            'php_user_filter',
            'Closure',
            'Generator',
            'ArithmeticError',
            'AssertionError',
            'DivisionByZeroError',
            'Error',
            'Throwable',
            'ParseError',
            'TypeError',
        ];
        $result = [];
        foreach ($nonReflectionBuiltinClasses as $class) {
            $result["Bare $class"] = [
                '$class' => $class,
            ];
            $result["Absolute $class"] = [
                '$class' => "\\$class",
            ];
        }
        return $result;
    }

    public function getMethodGroupInfos()
    {
        return [
            [
                'convertMethod'    => 'getParsedClass',
                'filterTextMethod' => 'replaceNativeClasses',
                'validityMethod'   => 'isNativeClass',
                'input'            => '$nativeClass',
                'output'           => '$parsedClass',
                'typeExpected'     => 'builtin',
            ],
            [
                'convertMethod'    => 'getNativeClass',
                'filterTextMethod' => 'replaceParsedClasses',
                'validityMethod'   => 'isParsedClass',
                'input'            => '$parsedClass',
                'output'           => '$nativeClass',
                'typeExpected'     => 'parsed',
            ],
        ];
    }

    public function getValidMethodsWithSingleArgAndExpectedOutput()
    {
        $result                      = [];
        $reflectionClassPairs        = $this->getReflectionClassPairsCases();
        $nonReflectionBuiltinClasses = $this->getNonReflectionBuiltInClassCases();
        $methodGroupInfos            = $this->getMethodGroupInfos();
        $testSamples                 = [
            'I do not like green eggs and ham.',
            'Four score and seven years ago...',
        ];
        foreach ($methodGroupInfos as $methodGroup) {
            foreach ($testSamples as $idx => $text) {
                $eachMethod = [
                    'filterTextMethod' => $text,
                    'validityMethod'   => false,
                ];
                foreach ($eachMethod as $methodName => $outputVal) {
                    $testCase          = sprintf(
                        '%s() with prose %u',
                        $methodName,
                        $idx + 1);
                    $result[$testCase] = [
                        '$methodName'     => $methodGroup[$methodName],
                        '$argument'       => $text,
                        '$expectedResult' => $outputVal,
                    ];
                }
            }
        }
        foreach ($methodGroupInfos as $methodGroup) {
            foreach ($reflectionClassPairs as $caseName => $classPairs) {
                $result[$methodGroup['convertMethod'] . '() with ' . lcfirst($caseName)] = [
                    '$methodName'     => $methodGroup['convertMethod'],
                    '$argument'       => $classPairs[$methodGroup['input']],
                    '$expectedResult' => $classPairs[$methodGroup['output']],
                ];
                $result[$methodGroup['filterTextMethod'] . '() with ' . lcfirst($caseName)] = [
                    '$methodName'     => $methodGroup['filterTextMethod'],
                    '$argument'       => $classPairs[$methodGroup['input']],
                    '$expectedResult' => $classPairs[$methodGroup['output']],
                ];
                $result[$methodGroup['filterTextMethod'] . '() with backwards ' . lcfirst($caseName)] = [
                    '$methodName'     => $methodGroup['filterTextMethod'],
                    '$argument'       => $classPairs[$methodGroup['output']],
                    '$expectedResult' => $classPairs[$methodGroup['output']],
                ];
                $result[$methodGroup['filterTextMethod'] . '() with ' . lcfirst($caseName) . ' in a sentence'] = [
                    '$methodName'     => $methodGroup['filterTextMethod'],
                    '$argument'       => sprintf('A sentence with %s in it.', $classPairs[$methodGroup['input']]),
                    '$expectedResult' => sprintf('A sentence with %s in it.', $classPairs[$methodGroup['output']]),
                ];
                $result[$methodGroup['validityMethod'] . '() with ' . lcfirst($caseName)] = [
                    '$methodName'     => $methodGroup['validityMethod'],
                    '$argument'       => $classPairs[$methodGroup['input']],
                    '$expectedResult' => true,
                ];
                $result[$methodGroup['validityMethod'] . '() with backwards ' . lcfirst($caseName)] = [
                    '$methodName'     => $methodGroup['validityMethod'],
                    '$argument'       => $classPairs[$methodGroup['output']],
                    '$expectedResult' => false,
                ];
                $result[$methodGroup['validityMethod'] . '() with ' . lcfirst($caseName) . ' in a sentence'] = [
                    '$methodName'     => $methodGroup['validityMethod'],
                    '$argument'       => sprintf('A sentence with %s in it.', $classPairs[$methodGroup['input']]),
                    '$expectedResult' => false,
                ];
            }
            foreach ($nonReflectionBuiltinClasses as $caseName => $classArr) {
                $result[$methodGroup['filterTextMethod'] . '() with non-reflection ' . lcfirst($caseName)] = [
                    '$methodName'     => $methodGroup['filterTextMethod'],
                    '$argument'       => $classArr['$class'],
                    '$expectedResult' => $classArr['$class'],
                ];
                $fakeParsedClass = preg_replace('/^(\\\\?)/', '\\1Go\\\\ParserReflection\\\\', $classArr['$class']);
                $fakeClassCaseName = $methodGroup['filterTextMethod'] . '() with fake parsed reflection ' . lcfirst(str_replace($classArr['$class'], $fakeParsedClass, $caseName));
                $result[$fakeClassCaseName] = [
                    '$methodName'     => $methodGroup['filterTextMethod'],
                    '$argument'       => $fakeParsedClass,
                    '$expectedResult' => $fakeParsedClass,
                ];
                $result[$methodGroup['validityMethod'] . '() with non-reflection ' . lcfirst($caseName)] = [
                    '$methodName'     => $methodGroup['validityMethod'],
                    '$argument'       => $classArr['$class'],
                    '$expectedResult' => false,
                ];
                $result[$methodGroup['validityMethod'] . '() with fake parsed reflection ' . lcfirst($caseName)] = [
                    '$methodName'     => $methodGroup['validityMethod'],
                    '$argument'       => $fakeParsedClass,
                    '$expectedResult' => false,
                ];
            }
        }
        return $result;
    }

    public function getInvalidMethodsWithSingleArgAndExpectedExceptionMessage()
    {
        $result                      = [];
        $reflectionClassPairs        = $this->getReflectionClassPairsCases();
        $nonReflectionBuiltinClasses = $this->getNonReflectionBuiltInClassCases();
        $methodGroupInfos            = $this->getMethodGroupInfos();
        foreach ($methodGroupInfos as $methodGroup) {
            foreach ($reflectionClassPairs as $caseName => $classPairs) {
                $result[$methodGroup['convertMethod'] . '() with backwards ' . lcfirst($caseName)] = [
                    '$methodName'      => $methodGroup['convertMethod'],
                    '$argument'        => $classPairs[$methodGroup['output']],
                    '$expectedMessage' => sprintf(
                        '%s not a %s Reflection class.',
                        $classPairs[$methodGroup['output']],
                        $methodGroup['typeExpected']),
                ];
                $result[$methodGroup['convertMethod'] . '() with ' . lcfirst($caseName) . ' in a sentence'] = [
                    '$methodName'     => $methodGroup['convertMethod'],
                    '$argument'       => sprintf(
                        'A sentence with %s in it.',
                        $classPairs[$methodGroup['input']]),
                    '$expectedResult' => sprintf(
                        'A sentence with %s in it. not a %s Reflection class.',
                        $classPairs[$methodGroup['input']],
                        $methodGroup['typeExpected']),
                ];
            }
            foreach ($nonReflectionBuiltinClasses as $caseName => $classArr) {
                $result[$methodGroup['convertMethod'] . '() with non-reflection ' . lcfirst($caseName)] = [
                    '$methodName'     => $methodGroup['convertMethod'],
                    '$argument'       => $classArr['$class'],
                    '$expectedResult' => sprintf(
                        '%s not a %s Reflection class.',
                        $classArr['$class'],
                        $methodGroup['typeExpected']),
                ];
                $fakeParsedClass = preg_replace('/^(\\\\?)/', '\\1Go\\\\ParserReflection\\\\', $classArr['$class']);
                $fakeClassCaseName = $methodGroup['convertMethod'] . '() with fake reflection ' . lcfirst(str_replace($classArr['$class'], $fakeParsedClass, $caseName));
                $result[$fakeClassCaseName] = [
                    '$methodName'     => $methodGroup['convertMethod'],
                    '$argument'       => $fakeParsedClass,
                    '$expectedResult' => sprintf(
                        '%s not a %s Reflection class.',
                        $fakeParsedClass,
                        $methodGroup['typeExpected']),
                ];
            }
        }
        return $result;
    }

    /**
     * Tests ReflectionMetaInfo getReflectionRepresentation() method
     *
     * @dataProvider getReflectionRepresentations
     *
     * @param string       $class                   The class name to test.
     * @param callable     $createFunc              Closure to create test object.
     * @param array        $expectedRepresentation  The argument list to pass.
     * @param null|string  $skipTestMessage         If non-null, why the test should be skipped?
     * @param boolean      $filterStrings           Should expected string args be filtered
     *                                                  through replaceNativeClasses()?
     */
    public function testGetReflectionRepresentation(
        $class,
        callable $createFunc,
        array $expectedRepresentation,
        $skipTestMessage,
        $filterStrings)
    {
        if (!is_null($skipTestMessage)) {
            $this->markTestSkipped($skipTestMessage);
        }
        $objectUnderTest = new ReflectionMetaInfo();
        $reflectionObj   = $createFunc();
        $representation  = $objectUnderTest->getReflectionRepresentation($reflectionObj);
        $this->assertSame($class, get_class($reflectionObj), 'Constructed object of correct class, (Derived class not allowed.)');
        $this->assertSame($expectedRepresentation, $representation, "Returned correct output");
        $this->assertArrayHasKey('class', $representation, "'class' is a required key.");
        $this->assertArrayHasKey('displayValues', $representation, "'displayValues' is a required key.");
        foreach ($representation as $key => $value) {
            $this->assertContains($key, ['class', 'constructorArgs', 'displayValues'], 'Allowed representation keys');
        }
        $this->assertSame($class, $representation['class'], 'Returned correct class');
    }
}
