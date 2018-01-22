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
     * Tests IsParsedEquivilantToReflectionValue::getParsedClass() method
     *
     * @dataProvider getReflectionClassPairsCases
     *
     * @param string  $nativeClass          The native class name.
     * @param string  $expectedParsedClass  The parsed class name.
     */
    public function testGetParsedClass($nativeClass, $expectedParsedClass)
    {
        $actualParsedClass = IsParsedEquivilantToReflectionValue::getParsedClass($nativeClass);
        $this->assertEquals($expectedParsedClass, $actualParsedClass, 'Correct class name transformation.');
    }

    /**
     * Tests IsParsedEquivilantToReflectionValue::getParsedClass() method
     *
     * @dataProvider getReflectionClassPairsCases
     *
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage not a builtin Reflection class.
     *
     * @param string  $nativeClass  The native class name.
     * @param string  $parsedClass  The parsed class name.
     */
    public function testGetParsedClassBackwards($nativeClass, $parsedClass)
    {
        IsParsedEquivilantToReflectionValue::getParsedClass($parsedClass);
    }

    /**
     * Tests IsParsedEquivilantToReflectionValue::getParsedClass() method
     *
     * @dataProvider getNonReflectionBuiltInClassCases
     *
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage not a builtin Reflection class.
     *
     * @param string  $class  The class name.
     */
    public function testGetParsedClassNonReflection($class)
    {
        IsParsedEquivilantToReflectionValue::getParsedClass($class);
    }

    /**
     * Tests IsParsedEquivilantToReflectionValue::getNativeClass() method
     *
     * @dataProvider getReflectionClassPairsCases
     *
     * @param string  $expectedNativeClass  The native class name.
     * @param string  $parsedClass          The parsed class name.
     */
    public function testGetNativeClass($expectedNativeClass, $parsedClass)
    {
        $actualNativeClass = IsParsedEquivilantToReflectionValue::getNativeClass($parsedClass);
        $this->assertEquals($expectedNativeClass, $actualNativeClass, 'Correct class name transformation.');
    }

    /**
     * Tests IsParsedEquivilantToReflectionValue::getNativeClass() method
     *
     * @dataProvider getReflectionClassPairsCases
     *
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage not a parsed Reflection class.
     *
     * @param string  $nativeClass  The native class name.
     * @param string  $parsedClass  The parsed class name.
     */
    public function testGetNativeClassBackwards($nativeClass, $parsedClass)
    {
        IsParsedEquivilantToReflectionValue::getNativeClass($nativeClass);
    }

    /**
     * Tests IsParsedEquivilantToReflectionValue::getNativeClass() method
     *
     * @dataProvider getNonReflectionBuiltInClassCases
     *
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage not a parsed Reflection class.
     *
     * @param string  $class  The class name.
     */
    public function testGetNativeClassNonReflection($class)
    {
        IsParsedEquivilantToReflectionValue::getNativeClass($class);
    }

    /**
     * Tests IsParsedEquivilantToReflectionValue::getNativeClass() method
     *
     * @dataProvider getNonReflectionBuiltInClassCases
     *
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage not a parsed Reflection class.
     *
     * @param string  $class  The class name.
     */
    public function testGetFakeParsedClassNonReflection($class)
    {
        $fakeParsedClass = preg_replace('/^(\\\\?)/', '\\1Go\\\\ParserReflection\\\\', $class);
        IsParsedEquivilantToReflectionValue::getNativeClass($fakeParsedClass);
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
