<?php
namespace Go\ParserReflection\TestingSupport\Traits;

use ReflectionClass;

trait SingletonTestingTrait
{
  private $singletonClassName;
  private $singletonAccessMethod;

  protected function initSingletonTestTarget($className, $methodName)
  {
    $this->singletonClassName    = $className;
    $this->singletonAccessMethod = $methodName;
  }

  public function testSingletonClassExists()
  {
    $this->assertTrue(class_exists($this->singletonClassName), sprintf('%s class exists', $this->singletonClassName));
  }

  public function testCannotInstantiateSingleton()
  {
    $classRef = new ReflectionClass($this->singletonClassName);
    $this->assertFalse($classRef->isInstantiable(), sprintf("%s isn't instantiable", $this->singletonClassName));
  }

  public function testCannotDeriveFromSingleton()
  {
    $classRef = new ReflectionClass($this->singletonClassName);
    $this->assertTrue($classRef->isFinal(), sprintf("%s is final", $this->singletonClassName));
  }

  public function testGetSingletonInstance()
  {
  	$instance = call_user_func([$this->singletonClassName, $this->singletonAccessMethod]);
    $this->assertInstanceOf($this->singletonClassName, $instance, sprintf("%s::%s() returns instance of self", $this->singletonClassName, $this->singletonAccessMethod));
  }

  public function testSingleInstance()
  {
    $firstInstance = call_user_func([$this->singletonClassName, $this->singletonAccessMethod]);
    $secondInstance = call_user_func([$this->singletonClassName, $this->singletonAccessMethod]);
    $this->assertEquals(spl_object_hash($firstInstance), spl_object_hash($secondInstance), sprintf("%s::%s() returns same instance", $this->singletonClassName, $this->singletonAccessMethod));
  }

  public function testCanNotCloneSingleton()
  {
    $classRef = new ReflectionClass($this->singletonClassName);
    $this->assertFalse($classRef->isCloneable(), sprintf("%s isn't cloneable", $this->singletonClassName));
  }
}
