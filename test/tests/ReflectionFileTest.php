<?php
namespace Go\ParserReflection\Tests;

use Stub\Issue44\Locator;
use Go\ParserReflection\ReflectionEngine;
use Go\ParserReflection\ReflectionFile;
use Go\ParserReflection\ReflectionFileNamespace;

class ReflectionFileTest extends TestCaseBase
{
    const STUB_FILE        = '/FileWithNamespaces.php';
    const STUB_GLOBAL_FILE = '/FileWithGlobalNamespace.php';

    /**
     * @var ReflectionFile
     */
    protected $parsedRefFile;

    protected function setUp()
    {
        $fileName       = stream_resolve_include_path($this->getStubDir() . self::STUB_FILE);
        $reflectionFile = new ReflectionFile($fileName);

        $this->parsedRefFile = $reflectionFile;
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage $fileName must be a string, but a array was passed
     */
    public function testBadFilenameTypeArray()
    {
        new ReflectionFile([1, 3, 5, 7]);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage $fileName must be a string, but a object was passed
     */
    public function testBadFilenameTypeObject()
    {
        new ReflectionFile(new \DateTime());
    }

    public function testGetName()
    {
        $fileName     = $this->parsedRefFile->getName();
        $expectedName = stream_resolve_include_path($this->getStubDir() . self::STUB_FILE);
        $this->assertEquals($expectedName, $fileName);
    }

    public function testGetFileNamespaces()
    {
        $reflectionFileNamespaces = $this->parsedRefFile->getFileNamespaces();
        $this->assertCount(3, $reflectionFileNamespaces);
    }

    public function testGetFileNamespace()
    {
        $reflectionFileNamespace = $this->parsedRefFile->getFileNamespace($this->getStubNamespace());
        $this->assertInstanceOf(ReflectionFileNamespace::class, $reflectionFileNamespace);

        $reflectionFileNamespace = $this->parsedRefFile->getFileNamespace('Unknown');
        $this->assertFalse($reflectionFileNamespace);
    }

    public function testHasFileNamespace()
    {
        $hasFileNamespace = $this->parsedRefFile->hasFileNamespace($this->getStubNamespace());
        $this->assertTrue($hasFileNamespace);

        $hasFileNamespace = $this->parsedRefFile->hasFileNamespace('Unknown');
        $this->assertFalse($hasFileNamespace);
    }

    public function testGetGlobalFileNamespace()
    {
        $fileName       = stream_resolve_include_path($this->getStubDir() . self::STUB_GLOBAL_FILE);
        $reflectionFile = new ReflectionFile($fileName);

        $reflectionFileNamespace = $reflectionFile->getFileNamespace('');
        $this->assertInstanceOf(ReflectionFileNamespace::class, $reflectionFileNamespace);
    }

    /**
     * Tests if strict mode detected correctly
     *
     * @param string $fileName Filename to analyse
     * @param bool $shouldBeStrict
     *
     * @dataProvider fileNameProvider
     */
    public function testIsStrictType($fileName, $shouldBeStrict)
    {
        $fileName       = stream_resolve_include_path($this->getStubDir() . $fileName);
        $reflectionFile = new ReflectionFile($fileName);

        $this->assertSame($shouldBeStrict, $reflectionFile->isStrictMode());
    }

    public function fileNameProvider()
    {
        return [
            '/Stub/FileWithClasses56.php'       => ['/FileWithClasses56.php', false],
            '/Stub/FileWithClasses70.php'       => ['/FileWithClasses70.php', false],
            '/Stub/FileWithClasses71.php'       => ['/FileWithClasses71.php', true],
            '/Stub/FileWithGlobalNamespace.php' => ['/FileWithGlobalNamespace.php', true],
        ];
    }

    public function testGetInterfaceNamesWithExtends()
    {
        $fileName = $this->getStubDir() . '/Issue44/ClassWithoutNamespace.php';

        require_once $this->getStubDir() . '/Issue44/Locator.php';
        ReflectionEngine::init(new Locator());

        $reflectedFile = new ReflectionFile($fileName);
        $namespaces = $reflectedFile->getFileNamespaces();
        $namespace = array_pop($namespaces);
        $classes = $namespace->getClasses();
        $class = array_pop($classes);

        $interfaceNames = $class->getInterfaceNames();
        $this->assertEquals([], $interfaceNames);
    }
}
