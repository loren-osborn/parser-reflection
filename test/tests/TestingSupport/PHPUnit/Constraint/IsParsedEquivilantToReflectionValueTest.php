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
     * Tests IsParsedEquivilantToReflectionValue::toString() method
     *
     * @param string                $value                    The value to compare.
     * @param TextTransformer|null  $transformer              Any string transformer to globally compare.
     * @param string                $expectedStringification  Stringification of the value.
     * @param string                $expectedComparisonType   Type of comparison.
     * @param null|string           $testSkipReason           If non-null forces test to be skipped.
     *
     * @dataProvider getConstructorValuesWithExpectedState
     */
    public function testToString(
        $value,
        $transformer,
        $expectedStringification,
        $expectedComparisonType,
        $testSkipReason)
    {
        if (!is_null($testSkipReason)) {
            $this->markTestSkipped($testSkipReason);
        }
        $obj             = new IsParsedEquivilantToReflectionValue($value, $transformer);
        $stringification = $obj->toString();
        $this->assertEquals(
            sprintf('is %s to %s', $expectedComparisonType, $expectedStringification),
            $stringification,
            'Correct toString() value.');
    }
}
