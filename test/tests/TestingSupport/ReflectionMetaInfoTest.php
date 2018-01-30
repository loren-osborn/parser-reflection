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
use Go\ParserReflection\TestingSupport\ReflectionMetaInfo;
use InvalidArgumentException;

class ReflectionMetaInfoTest extends TestCaseBase
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

    public function getValidMethodsWithSingleArgAndExpectedOutput()
    {
        $reflectionClassPairs        = $this->getReflectionClassPairsCases();
        $nonReflectionBuiltinClasses = $this->getNonReflectionBuiltInClassCases();
        $result = [
            'replaceNativeClasses() with prose' => [
                '$methodName' => 'replaceNativeClasses',
                '$argument'         => 'Four score and seven years ago...',
                '$expectedResult'   => 'Four score and seven years ago...',
            ],
            'replaceParsedClasses() with prose' => [
                '$methodName' => 'replaceParsedClasses',
                '$argument'         => 'Four score and seven years ago...',
                '$expectedResult'   => 'Four score and seven years ago...',
            ],
        ];
        foreach ($reflectionClassPairs as $caseName => $classPairs) {
            $result['getParsedClass() with ' . lcfirst($caseName)] = [
                '$methodName' => 'getParsedClass',
                '$argument'         => $classPairs['$nativeClass'],
                '$expectedResult'   => $classPairs['$parsedClass'],
            ];
            $result['getNativeClass() with ' . lcfirst($caseName)] = [
                '$methodName' => 'getNativeClass',
                '$argument'         => $classPairs['$parsedClass'],
                '$expectedResult'   => $classPairs['$nativeClass'],
            ];
            $result['replaceNativeClasses() with ' . lcfirst($caseName)] = [
                '$methodName' => 'replaceNativeClasses',
                '$argument'         => $classPairs['$nativeClass'],
                '$expectedResult'   => $classPairs['$parsedClass'],
            ];
            $result['replaceParsedClasses() with ' . lcfirst($caseName)] = [
                '$methodName' => 'replaceParsedClasses',
                '$argument'         => $classPairs['$parsedClass'],
                '$expectedResult'   => $classPairs['$nativeClass'],
            ];
            $result['replaceNativeClasses() with backwards ' . lcfirst($caseName)] = [
                '$methodName' => 'replaceNativeClasses',
                '$argument'         => $classPairs['$parsedClass'],
                '$expectedResult'   => $classPairs['$parsedClass'],
            ];
            $result['replaceParsedClasses() with backwards ' . lcfirst($caseName)] = [
                '$methodName' => 'replaceParsedClasses',
                '$argument'         => $classPairs['$nativeClass'],
                '$expectedResult'   => $classPairs['$nativeClass'],
            ];
            $result['replaceNativeClasses() with ' . lcfirst($caseName) . ' in a sentence'] = [
                '$methodName' => 'replaceNativeClasses',
                '$argument'         => sprintf('A sentence with %s in it.', $classPairs['$nativeClass']),
                '$expectedResult'   => sprintf('A sentence with %s in it.', $classPairs['$parsedClass']),
            ];
            $result['replaceParsedClasses() with ' . lcfirst($caseName) . ' in a sentence'] = [
                '$methodName' => 'replaceParsedClasses',
                '$argument'         => sprintf('A sentence with %s in it.', $classPairs['$parsedClass']),
                '$expectedResult'   => sprintf('A sentence with %s in it.', $classPairs['$nativeClass']),
            ];
        }
        foreach ($nonReflectionBuiltinClasses as $caseName => $classArr) {
            $result['replaceNativeClasses() with non-reflection ' . lcfirst($caseName)] = [
                '$methodName' => 'replaceNativeClasses',
                '$argument'         => $classArr['$class'],
                '$expectedResult'   => $classArr['$class'],
            ];
            $result['replaceParsedClasses() with non-reflection ' . lcfirst($caseName)] = [
                '$methodName' => 'replaceParsedClasses',
                '$argument'         => $classArr['$class'],
                '$expectedResult'   => $classArr['$class'],
            ];
            $fakeParsedClass = preg_replace('/^(\\\\?)/', '\\1Go\\\\ParserReflection\\\\', $classArr['$class']);
            $fakeClassCaseName = 'replaceParsedClasses() with fake reflection ' . lcfirst(str_replace($classArr['$class'], $fakeParsedClass, $caseName));
            $result[$fakeClassCaseName] = [
                '$methodName' => 'replaceParsedClasses',
                '$argument'         => $fakeParsedClass,
                '$expectedResult'   => $fakeParsedClass,
            ];
        }
        return $result;
    }

    public function getInvalidMethodsWithSingleArgAndExpectedExceptionMessage()
    {
        $reflectionClassPairs        = $this->getReflectionClassPairsCases();
        $nonReflectionBuiltinClasses = $this->getNonReflectionBuiltInClassCases();
        $result = [];
        foreach ($reflectionClassPairs as $caseName => $classPairs) {
            $result['getParsedClass() with backwards ' . lcfirst($caseName)] = [
                '$methodName' => 'getParsedClass',
                '$argument'         => $classPairs['$parsedClass'],
                '$expectedMessage'  => $classPairs['$parsedClass'] . ' not a builtin Reflection class.',
            ];
            $result['getNativeClass() with backwards ' . lcfirst($caseName)] = [
                '$methodName' => 'getNativeClass',
                '$argument'         => $classPairs['$nativeClass'],
                '$expectedMessage'  => $classPairs['$nativeClass'] . ' not a parsed Reflection class.',
            ];
            $result['getParsedClass() with ' . lcfirst($caseName) . ' in a sentence'] = [
                '$methodName' => 'getParsedClass',
                '$argument'         => sprintf('A sentence with %s in it.', $classPairs['$nativeClass']),
                '$expectedResult'   => sprintf('A sentence with %s in it. not a builtin Reflection class.', $classPairs['$nativeClass']),
            ];
            $result['getNativeClass() with ' . lcfirst($caseName) . ' in a sentence'] = [
                '$methodName' => 'getNativeClass',
                '$argument'         => sprintf('A sentence with %s in it.', $classPairs['$parsedClass']),
                '$expectedResult'   => sprintf('A sentence with %s in it. not a parsed Reflection class.', $classPairs['$parsedClass']),
            ];
        }
        foreach ($nonReflectionBuiltinClasses as $caseName => $classArr) {
            $result['getParsedClass() with non-reflection ' . lcfirst($caseName)] = [
                '$methodName' => 'getParsedClass',
                '$argument'         => $classArr['$class'],
                '$expectedMessage'  => $classArr['$class'] . ' not a builtin Reflection class.',
            ];
            $result['getNativeClass() with non-reflection ' . lcfirst($caseName)] = [
                '$methodName' => 'getNativeClass',
                '$argument'         => $classArr['$class'],
                '$expectedMessage'  => $classArr['$class'] . ' not a parsed Reflection class.',
            ];
            $fakeParsedClass = preg_replace('/^(\\\\?)/', '\\1Go\\\\ParserReflection\\\\', $classArr['$class']);
            $fakeClassCaseName = 'getNativeClass() with fake reflection ' . lcfirst(str_replace($classArr['$class'], $fakeParsedClass, $caseName));
            $result[$fakeClassCaseName] = [
                '$methodName' => 'getNativeClass',
                '$argument'         => $fakeParsedClass,
                '$expectedMessage'  => $fakeParsedClass . ' not a parsed Reflection class.',
            ];
        }
        return $result;
    } 
}
