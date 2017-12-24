<?php
namespace Go\ParserReflection\Tests\Locator;

use Go\ParserReflection\ReflectionClass;
use Go\ParserReflection\Locator\ComposerLocator;

class ComposerLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testLocateClass()
    {
        $locator         = new ComposerLocator();
        $reflectionClass = new \ReflectionClass(ReflectionClass::class);
        $this->assertSame(
            $reflectionClass->getFileName(),
            $locator->locateClass(ReflectionClass::class)
        );
    }
}
