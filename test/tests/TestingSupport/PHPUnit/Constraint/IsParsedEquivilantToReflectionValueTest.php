<?php
/**
 * Parser Reflection API
 *
 * @copyright Copyright 2016, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Go\ParserReflection\Tests\TestingSupport\PHPUnit\Constraint;

use PHPUnit_Framework_Constraint;
use Go\ParserReflection\Tests\TestingSupport\ParsedEquivilantComparitorTestBase;
use Go\ParserReflection\TestingSupport\TextTransformer;
use Go\ParserReflection\TestingSupport\PHPUnit\Constraint\IsParsedEquivilantToReflectionValue;
use SebastianBergmann\Exporter\Exporter;
use InvalidArgumentException;

class IsParsedEquivilantToReflectionValueTest extends ParsedEquivilantComparitorTestBase
{
    public function testCanCreate()
    {
        $obj = new IsParsedEquivilantToReflectionValue('', new TextTransformer());
        $this->assertInstanceOf(PHPUnit_Framework_Constraint::class, $obj);
        // Test that second argument is optional
        new IsParsedEquivilantToReflectionValue('');
    }

    /**
     * Test that creation prevents non-indempotent TextTransformers.
     *
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Provided TextTransformer is not indempotent.
     */
    public function testRequireIndempotence()
    {
        new IsParsedEquivilantToReflectionValue(
            'el foo goo',
            new TextTransformer([['/foo/','foo foo']]));
    }

    /**
     * Tests IsParsedEquivilantToReflectionValue static methods
     *
     * @dataProvider getValidStaticMethodsWithSingleArgAndExpectedOutput
     *
     * @param string  $staticMethodName  The method to call.
     * @param string  $argument          The argument to pass.
     * @param string  $expectedResult    The result value to expect.
     */
    public function testValidStaticMethodsWithSingleArg(
        $staticMethodName,
        $argument,
        $expectedResult)
    {
        $actualResult = IsParsedEquivilantToReflectionValue::$staticMethodName($argument);
        $this->assertEquals($expectedResult, $actualResult, 'Correct class name transformation.');
    }

    /**
     * Tests IsParsedEquivilantToReflectionValue static method exceptions
     *
     * @dataProvider getInvalidStaticMethodsWithSingleArgAndExpectedExceptionMessage
     *
     * @param string  $staticMethodName  The method to call.
     * @param string  $argument          The argument to pass.
     * @param string  $expectedMessage   The exception message to expect.
     */
    public function testInvalidStaticMethodsWithSingleArg(
        $staticMethodName,
        $argument,
        $expectedMessage)
    {
        try {
            IsParsedEquivilantToReflectionValue::$staticMethodName($argument);
            $this->fail(sprintf(
                'Method %s::%s(%s) should have thrown exception.',
                IsParsedEquivilantToReflectionValue::class,
                $staticMethodName,
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

    public function getValidStaticMethodsWithSingleArgAndExpectedOutput()
    {
        $reflectionClassPairs = $this->getReflectionClassPairsCases();
        $result = [];
        foreach ($reflectionClassPairs as $caseName => $classPairs) {
            $result['getParsedClass() with ' . lcfirst($caseName)] = [
                '$staticMethodName' => 'getParsedClass',
                '$argument'         => $classPairs['$nativeClass'],
                '$expectedResult'   => $classPairs['$parsedClass'],
            ];
            $result['getNativeClass() with ' . lcfirst($caseName)] = [
                '$staticMethodName' => 'getNativeClass',
                '$argument'         => $classPairs['$parsedClass'],
                '$expectedResult'   => $classPairs['$nativeClass'],
            ];
        }
        return $result;
    }

    public function getInvalidStaticMethodsWithSingleArgAndExpectedExceptionMessage()
    {
        $reflectionClassPairs        = $this->getReflectionClassPairsCases();
        $nonReflectionBuiltinClasses = $this->getNonReflectionBuiltInClassCases();
        $result = [];
        foreach ($reflectionClassPairs as $caseName => $classPairs) {
            $result['getParsedClass() with backwards ' . lcfirst($caseName)] = [
                '$staticMethodName' => 'getParsedClass',
                '$argument'         => $classPairs['$parsedClass'],
                '$expectedMessage'  => $classPairs['$parsedClass'] . ' not a builtin Reflection class.',
            ];
            $result['getNativeClass() with backwards ' . lcfirst($caseName)] = [
                '$staticMethodName' => 'getNativeClass',
                '$argument'         => $classPairs['$nativeClass'],
                '$expectedMessage'  => $classPairs['$nativeClass'] . ' not a parsed Reflection class.',
            ];
        }
        foreach ($nonReflectionBuiltinClasses as $caseName => $classArr) {
            $result['getParsedClass() with non-reflection ' . lcfirst($caseName)] = [
                '$staticMethodName' => 'getParsedClass',
                '$argument'         => $classArr['$class'],
                '$expectedMessage'  => $classArr['$class'] . ' not a builtin Reflection class.',
            ];
            $result['getNativeClass() with non-reflection ' . lcfirst($caseName)] = [
                '$staticMethodName' => 'getNativeClass',
                '$argument'         => $classArr['$class'],
                '$expectedMessage'  => $classArr['$class'] . ' not a parsed Reflection class.',
            ];
            $fakeParsedClass = preg_replace('/^(\\\\?)/', '\\1Go\\\\ParserReflection\\\\', $classArr['$class']);
            $fakeClassCaseName = 'getNativeClass() with fake reflection ' . lcfirst(str_replace($classArr['$class'], $fakeParsedClass, $caseName));
            $result[$fakeClassCaseName] = [
                '$staticMethodName' => 'getNativeClass',
                '$argument'         => $fakeParsedClass,
                '$expectedMessage'  => $fakeParsedClass . ' not a parsed Reflection class.',
            ];
        }
        return $result;
    }

    /**
     * Tests IsParsedEquivilantToReflectionValue::toString() method
     *
     * @param string                $value                    The value to compare.
     * @param TextTransformer|null  $transformer              Any string transformer to globally compare.
     * @param string                $expectedStringification  Stringification of the value.
     * @param string                $expectedComparisonType   Type of comparison.
     *
     * @dataProvider getConstructorValuesWithExpectedState
     */
    public function testToString(
        $value,
        $transformer,
        $expectedStringification,
        $expectedComparisonType)
    {
        $obj             = new IsParsedEquivilantToReflectionValue($value, $transformer);
        $stringification = $obj->toString();
        $this->assertEquals(
            sprintf('is %s to %s', $expectedComparisonType, $expectedStringification),
            $stringification,
            'Correct toString() value.');
    }
}
