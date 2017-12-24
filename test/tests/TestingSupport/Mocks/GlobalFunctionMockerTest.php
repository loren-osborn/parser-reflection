<?php
namespace Go\ParserReflection\Tests\TestingSupport\Mocks;

use Go\ParserReflection\TestingSupport\Mocks\GlobalFunctionMocker;
use Go\ParserReflection\TestingSupport\Traits\SingletonTestingTrait;

class GlobalFunctionMockerTest extends \PHPUnit_Framework_TestCase
{
	use SingletonTestingTrait;

    protected function setUp()
    {
        $this->initSingletonTestTarget(GlobalFunctionMocker::class, 'getInstance');
    }

    /**
     * TDD: A place to start.
     */
    // public function testCanCreate()
    // {
    //     new GlobalFunctionMocker();
    // }
}
