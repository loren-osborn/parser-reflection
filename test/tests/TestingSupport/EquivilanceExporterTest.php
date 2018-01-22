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

use SebastianBergmann\Exporter\Exporter;
use Go\ParserReflection\TestingSupport\EquivilanceExporter;

class EquivilanceExporterTest extends ParsedEquivilantComparitorTestBase
{
    public function testCanCreate()
    {
        $obj = new EquivilanceExporter();
        $this->assertInstanceOf(Exporter::class, $obj);
    }

    /**
     * Tests EquivilanceExporter::export() method
     *
     * @param string                $value                    The value to compare.
     * @param TextTransformer|null  $transformer              Any string transformer to globally compare.
     * @param string                $expectedStringification  Stringification of the value.
     * @param string                $expectedComparisonType   IGNORED type of comparison. 
     *
     * @dataProvider getConstructorValuesWithExpectedState
     */
    public function testExport(
        $value,
        $transformer,
        $expectedStringification,
        $expectedComparisonType)
    {
        $obj             = new EquivilanceExporter($transformer);
        $stringification = $obj->export($value);
        $this->assertEquals(
            $expectedStringification,
            $stringification,
            'Correct exported() value.');
    }
}
