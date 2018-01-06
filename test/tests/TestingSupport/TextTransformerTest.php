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

class TextTransformerTest extends TestCaseBase
{
    /**
     * Tests TextTransformer->filter() method
     *
     * @dataProvider getTestCases
     *
     * @param array[] $transforms     Array of arrays of the form [pattern, replacement].
     * @param string  $input          Input string to filter.
     * @param string  $expectedOutput Expected output string.
     */
    public function testFilter(array $transforms, $input, $expectedOutput)
    {
        $obj          = new TextTransformer($transforms);
        $beforeState  = var_export($obj, true);
        $actualOutput = $obj->filter($input);
        $afterState   = var_export($obj, true);
        $this->assertEquals($beforeState, $afterState, 'Object should not change state.');
        $this->assertEquals($expectedOutput, $actualOutput, 'Expected filter output.');
    }

    /**
     * Tests TextTransformer->__invoke() method
     *
     * @dataProvider getTestCases
     *
     * @param array[] $transforms     Array of arrays of the form [pattern, replacement].
     * @param string  $input          Input string to filter.
     * @param string  $expectedOutput Expected output string.
     */
    public function testInvoke(array $transforms, $input, $expectedOutput)
    {
        $obj          = new TextTransformer($transforms);
        $beforeState  = var_export($obj, true);
        $actualOutput = $obj($input);
        $afterState   = var_export($obj, true);
        $this->assertEquals($beforeState, $afterState, 'Object should not change state.');
        $this->assertEquals($expectedOutput, $actualOutput, 'Expected filter output.');
    }

    public function getTestCases()
    {
        return [
            'null transform on empty' => [
                '$transforms'     => [],
                '$input'          => '',
                '$expectedOutput' => '',
            ],
            'null transform on nonempty' => [
                '$transforms'     => [],
                '$input'          => 'foo bar baz',
                '$expectedOutput' => 'foo bar baz',
            ],
            'nonmatching transform on nonempty' => [
                '$transforms'     => [['/fred/', 'george']],
                '$input'          => 'fee figh foh fum',
                '$expectedOutput' => 'fee figh foh fum',
            ],
            'Matching transform' => [
                '$transforms'     => [['/\\bFred\\b/', 'George']],
                '$input'          => 'Only joking, I am Fred.',
                '$expectedOutput' => 'Only joking, I am George.',
            ],
            'More than one transform pair' => [
                '$transforms'     => [
                	['/\\bGeorge\\b/', 'Fred'],
                	['/red/',          'green'],
                	['/ok/',           'fail'],
                ],
                '$input'          => 'Only joking, I am George.',
                '$expectedOutput' => 'Only jfailing, I am Fgreen.',
            ],
        ];
    }
}
