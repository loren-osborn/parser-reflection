<?php
namespace Go\ParserReflection\Testing\Tests\Instrument;

use Go\ParserReflection\Instrument\PathResolver;

class PathResolverTest extends \PHPUnit_Framework_TestCase
{
    public function getRealPathTestCases()
    {
        $testCases = [
            'Testing falsy input null' => [
                '$input'          =>  [null],
                '$expectedOutput' =>  null,
            ],
            'Testing falsy input false' => [
                '$input'          => [false],
                '$expectedOutput' => false,
            ],
            'Testing falsy input zero' => [
                '$input'          =>  [0],
                '$expectedOutput' =>  0,
            ],
            'Testing falsy input zero string' => [
                '$input'          =>  ['0'],
                '$expectedOutput' =>  '0',
            ],
            'Testing falsy input empty string' => [
                '$input'          => [''],
                '$expectedOutput' => '',
            ],
            // Handle arrays below
            'Testing URLs' => [
                '$input'          => ['http://www.google.com/'],
                '$expectedOutput' => 'http://www.google.com/',
            ],
            'Testing absolute paths' => [
                '$input'          => [__FILE__],
                '$expectedOutput' => realpath(__FILE__),
            ],
            'Testing relative paths' => [
                '$input'          => ['fred'],
                '$expectedOutput' => getcwd() . DIRECTORY_SEPARATOR . 'fred',
            ],
            // Work in progress:
            // 'Testing missing drive letter' => [
            //     '$input'          => ['\\fred'],
            //     '$expectedOutput' => 'F:\\fred',
            //     '$reason'         => '',
            //     '$specialSetup'   => (function () {
            //         putenv('SystemDrive=F:');
            //     }),
            // ],
        ];
        foreach (array_keys($testCases) as $testName) {
            $testCases[$testName]['$reason'] = $testName;
        }
        return $testCases;
    }

    /**
     * @dataProvider getRealPathTestCases
     */
    public function testRealPath(
        $input,
        $expectedOutput,
        $reason,
        callable $specialSetup    = NULL,
        callable $specialTeardown = NULL)
    {
        if ($specialSetup) {
            $specialSetup();
        }
        $actualOutput = call_user_func_array(
            [PathResolver::class, 'realpath'],
            $input);
        $this->assertSame($expectedOutput, $actualOutput, $reason);
        if ($specialTeardown) {
            $specialTeardown();
        }
    }
}
