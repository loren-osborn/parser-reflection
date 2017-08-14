<?php
namespace Go\ParserReflection\Tests\TestingSupport\Mocks;

use Go\ParserReflection\TestingSupport\Mocks\GlobalFunctionMocker;

class GlobalFunctionMockerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * TDD: A place to start.
     */
    public function testCanCreate()
    {
        new GlobalFunctionMocker();
    }
}
