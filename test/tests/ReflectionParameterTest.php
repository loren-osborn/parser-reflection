<?php
namespace Go\ParserReflection\Tests;

use Go\ParserReflection\TestingSupport\Stubs\Foo;
use Go\ParserReflection\TestingSupport\Stubs\SubFoo;
use Go\ParserReflection\ReflectionEngine;
use Go\ParserReflection\ReflectionFile;
use Go\ParserReflection\ReflectionParameter;
use Go\ParserReflection\ReflectionFunction;
use TestParametersForRootNsClass;

class ReflectionParameterTest extends TestCaseBase
{
    /**
     * @var string
     */
    protected $lastFileSetUp;

    /**
     * @var ReflectionFile
     */
    protected $parsedRefFile;

    protected function setUp()
    {
        $this->setUpFile($this->getStubDir() . '/FileWithParameters55.php');
    }

    /**
     * @dataProvider functionProvider
     */
    public function testGeneralInfoGetters($funcName, $fileName, $origFunction)
    {
        $allNameGetters = [
            'isArray', 'isCallable', 'isOptional', 'isPassedByReference', 'isDefaultValueAvailable',
            'getPosition', 'canBePassedByValue', 'allowsNull', 'getDefaultValue', 'getDefaultValueConstantName',
            'isDefaultValueConstant', '__toString'
        ];
        $onlyWithDefaultValues = array_flip([
            'getDefaultValue', 'getDefaultValueConstantName', 'isDefaultValueConstant'
        ]);
        if (PHP_VERSION_ID >= 50600) {
            $allNameGetters[] = 'isVariadic';
        }
        if (PHP_VERSION_ID >= 70000) {
            $allNameGetters[] = 'hasType';
        }

        if ($fileName) {
            $this->setUpFile($fileName);
            $fileNamespace = $this->parsedRefFile->getFileNamespace(
                $this->getNamespaceFromName($funcName));
            $refFunction   = $fileNamespace->getFunction(
                $this->getShortNameFromName($funcName));
        } else {
            $this->lastFileSetUp = null;
            $this->parsedRefFile = null;
            $refFunction = new ReflectionFunction($funcName);
        }
        $functionName  = $refFunction->getName();
        $comparisonTransformer = 'strval';
        if (preg_match('/\\bNeverIncluded\\b/', $functionName)) {
            $comparisonTransformer = (function ($inStr) {
                return preg_replace(',([/\\\\])Stub\\b,', '\\1Stub\\1NeverIncluded', $inStr);
            });
        }
        foreach ($refFunction->getParameters() as $refParameter) {
            $parameterName        = $refParameter->getName();
            $originalRefParameter = new \ReflectionParameter($origFunction, $parameterName);
            foreach ($allNameGetters as $getterName) {

                // skip some methods if there is no default value
                $isDefaultValueAvailable = $originalRefParameter->isDefaultValueAvailable();
                if (isset($onlyWithDefaultValues[$getterName]) && !$isDefaultValueAvailable) {
                    continue;
                }
                $expectedValue = $originalRefParameter->$getterName();
                $actualValue   = $refParameter->$getterName();
                $this->assertReflectorValueSame(
                    $expectedValue,
                    $actualValue,
                    "{$getterName}() for parameter {$functionName}:{$parameterName} should be equal",
                    $comparisonTransformer
                );
            }
        }
    }

    /**
     * Provides a list of files for analysis
     *
     * @return array
     */
    public function fileProvider()
    {
        $files = ['PHP5.5' => [$this->getStubDir() . '/FileWithParameters55.php']];

        if (PHP_VERSION_ID >= 50600) {
            $files['PHP5.6'] = [$this->getStubDir() . '/FileWithParameters56.php'];
        }
        if (PHP_VERSION_ID >= 70000) {
            $files['PHP7.0'] = [$this->getStubDir() . '/FileWithParameters70.php'];
        }

        return $files;
    }

    /**
     * Provides a list of functions for analysis in the form [Function, FileName]
     *
     * @return array
     */
    public function functionProvider()
    {
        // Random selection of built in functions.
        $builtInFunctions = ['preg_match', 'date', 'create_function'];
        $functions = [];
        foreach ($builtInFunctions as $functionsName) {
            $functions[$functionsName] = [
                'function'     => $functionsName,
                'fileName'     => null,
                'origFunction' => $functionsName,
            ];
        }
        $files = $this->fileProvider();
        foreach ($files as $filenameArgList) {
            $argKeys = array_keys($filenameArgList);
            $fileName = $filenameArgList[$argKeys[0]];
            $resolvedFileName = stream_resolve_include_path($fileName);
            $fileNode = ReflectionEngine::parseFile($resolvedFileName);
            list($fakeFileName, $funcNameFilter) = $this->getNeverIncludedFileFilter($resolvedFileName);
            $realAndFake = [
                'real' => ['file' => $resolvedFileName, 'funcNameFilter' => 'strval'       ],
                'fake' => ['file' => $fakeFileName,     'funcNameFilter' => $funcNameFilter],
            ];

            $reflectionFile = new ReflectionFile($resolvedFileName, $fileNode);
            foreach ($reflectionFile->getFileNamespaces() as $fileNamespace) {
                foreach ($fileNamespace->getFunctions() as $parsedFunction) {
                    foreach ($realAndFake as $funcFaker) {
                        $funcNameFilter = $funcFaker['funcNameFilter'];
                        if (
                            ($funcNameFilter === 'strval') ||
                            ($funcNameFilter($parsedFunction->getName()) != $parsedFunction->getName())
                        ) {
                            $functions[$argKeys[0] . ': ' . $funcNameFilter($parsedFunction->getName())] = [
                                'function'     => $funcNameFilter($parsedFunction->getName()),
                                'fileName'     => $funcFaker['file'],
                                'origFunction' => $parsedFunction->getName(),
                            ];
                        }
                    }
                }
            }
        }

        return $functions;
    }

    public function testGetClassMethod()
    {
        $parsedNamespace = $this->parsedRefFile->getFileNamespace($this->getStubNamespace());
        $parsedFunction  = $parsedNamespace->getFunction('miscParameters');

        $parameters = $parsedFunction->getParameters();
        $this->assertSame(null, $parameters[0 /* array $arrayParam*/]->getClass());
        $this->assertSame(null, $parameters[3 /* callable $callableParam */]->getClass());

        $objectParam = $parameters[5 /* \stdClass $objectParam */]->getClass();
        $this->assertInstanceOf(\ReflectionClass::class, $objectParam);
        $this->assertSame(\stdClass::class, $objectParam->getName());

        $typehintedParamWithNs = $parameters[7 /* ReflectionParameter $typehintedParamWithNs */]->getClass();
        $this->assertInstanceOf(\ReflectionClass::class, $typehintedParamWithNs);
        $this->assertSame(ReflectionParameter::class, $typehintedParamWithNs->getName());

        $internalInterfaceParam = $parameters[12 /* \Traversable $traversable */]->getClass();
        $this->assertInstanceOf(\ReflectionClass::class, $internalInterfaceParam);
        $this->assertSame(\Traversable::class, $internalInterfaceParam->getName());
    }

    public function testGetClassMethodReturnsSelfAndParent()
    {
        $parsedNamespace = $this->parsedRefFile->getFileNamespace($this->getStubNamespace());
        $parsedClass     = $parsedNamespace->getClass(SubFoo::class);
        $parsedFunction  = $parsedClass->getMethod('anotherMethodParam');

        $parameters = $parsedFunction->getParameters();
        $selfParam = $parameters[0 /* self $selfParam */]->getClass();
        $this->assertInstanceOf(\ReflectionClass::class, $selfParam);
        $this->assertSame(SubFoo::class, $selfParam->getName());

        $parentParam = $parameters[1 /* parent $parentParam */]->getClass();
        $this->assertInstanceOf(\ReflectionClass::class, $parentParam);
        $this->assertSame(Foo::class, $parentParam->getName());
    }

    public function testNonConstantsResolvedForGlobalNamespace()
    {
        $parsedNamespace = $this->parsedRefFile->getFileNamespace('');
        $parsedClass     = $parsedNamespace->getClass(TestParametersForRootNsClass::class);
        $parsedFunction  = $parsedClass->getMethod('foo');

        $parameters = $parsedFunction->getParameters();
        $this->assertSame(null, $parameters[0]->getDefaultValue());
        $this->assertSame(false, $parameters[1]->getDefaultValue());
        $this->assertSame(true, $parameters[2]->getDefaultValue());
    }

    public function testGetDeclaringClassMethodReturnsObject()
    {
        $parsedNamespace = $this->parsedRefFile->getFileNamespace($this->getStubNamespace());
        $parsedClass     = $parsedNamespace->getClass(Foo::class);
        $parsedFunction  = $parsedClass->getMethod('methodParam');

        $parameters = $parsedFunction->getParameters();
        $this->assertSame($parsedClass->getName(), $parameters[0]->getDeclaringClass()->getName());
    }

    public function testParamWithDefaultConstValue()
    {
        $parsedNamespace = $this->parsedRefFile->getFileNamespace($this->getStubNamespace());
        $parsedClass     = $parsedNamespace->getClass(Foo::class);
        $parsedFunction  = $parsedClass->getMethod('methodParamConst');

        $parameters = $parsedFunction->getParameters();
        $this->assertTrue($parameters[0]->isDefaultValueConstant());
        $this->assertSame('self::CLASS_CONST', $parameters[0]->getDefaultValueConstantName());

        $this->assertTrue($parameters[2]->isDefaultValueConstant());
        $this->assertSame($this->getStubNamespace() . '\\TEST_PARAMETER', $parameters[2]->getDefaultValueConstantName());

        $this->assertTrue($parameters[3]->isDefaultValueConstant());
        $this->assertSame($this->getStubNamespace() . '\\SubFoo::ANOTHER_CLASS_CONST', $parameters[3]->getDefaultValueConstantName());
    }

    public function testParamBuiltInClassConst()
    {
        $parsedNamespace = $this->parsedRefFile->getFileNamespace($this->getStubNamespace());
        $parsedClass     = $parsedNamespace->getClass(Foo::class);
        $parsedFunction  = $parsedClass->getMethod('methodParamBuiltInClassConst');

        $parameters = $parsedFunction->getParameters();
        $this->assertTrue($parameters[0]->isDefaultValueConstant());
        $this->assertSame('DateTime::ATOM', $parameters[0]->getDefaultValueConstantName());
    }

    public function testGetDeclaringClassMethodReturnsNull()
    {
        $parsedNamespace = $this->parsedRefFile->getFileNamespace($this->getStubNamespace());
        $parsedFunction  = $parsedNamespace->getFunction('miscParameters');

        $parameters = $parsedFunction->getParameters();
        $this->assertNull($parameters[0]->getDeclaringClass());
    }

    public function testDebugInfoMethod()
    {
        $parsedNamespace = $this->parsedRefFile->getFileNamespace($this->getStubNamespace());
        $parsedFunction  = $parsedNamespace->getFunction('miscParameters');

        $parsedRefParameters  = $parsedFunction->getParameters();
        $parsedRefParameter   = $parsedRefParameters[0];
        $originalRefParameter = new \ReflectionParameter($this->getStubNamespace() . '\\miscParameters', 'arrayParam');
        $expectedValue        = (array) $originalRefParameter;
        $this->assertSame($expectedValue, $parsedRefParameter->___debugInfo());
    }

    /**
     * @dataProvider listOfDefaultGetters
     *
     * @param string $getterName Name of the getter to call
     */
    public function testGetDefaultValueThrowsAnException($getterName)
    {
        $originalException = null;
        $parsedException   = null;

        try {
            $originalRefParameter = new \ReflectionParameter($this->getStubNamespace() . '\\miscParameters', 'arrayParam');
            $originalRefParameter->$getterName();
        } catch (\ReflectionException $e) {
            $originalException = $e;
        }

        try {
            $parsedNamespace = $this->parsedRefFile->getFileNamespace($this->getStubNamespace());
            $parsedFunction  = $parsedNamespace->getFunction('miscParameters');

            $parsedRefParameters  = $parsedFunction->getParameters();
            $parsedRefParameter   = $parsedRefParameters[0];
            $parsedRefParameter->$getterName();
        } catch (\ReflectionException $e) {
            $parsedException = $e;
        }

        $this->assertInstanceOf(\ReflectionException::class, $originalException);
        $this->assertInstanceOf(\ReflectionException::class, $parsedException);
        $this->assertSame($originalException->getMessage(), $parsedException->getMessage());
    }

    public function listOfDefaultGetters()
    {
        return [
            ['getDefaultValue'],
            ['getDefaultValueConstantName']
        ];
    }

    public function testCoverAllMethods()
    {
        $allInternalMethods = get_class_methods(\ReflectionParameter::class);
        $allMissedMethods   = [];

        foreach ($allInternalMethods as $internalMethodName) {
            if ('export' === $internalMethodName) {
                continue;
            }
            $refMethod    = new \ReflectionMethod(ReflectionParameter::class, $internalMethodName);
            $definerClass = $refMethod->getDeclaringClass()->getName();
            if (strpos($definerClass, 'Go\\ParserReflection\\') !== 0) {
                $allMissedMethods[] = $internalMethodName;
            }
        }

        if ($allMissedMethods) {
            $this->markTestIncomplete('Methods ' . join($allMissedMethods, ', ') . ' are not implemented');
        }
    }

    public function testGetTypeMethod()
    {
        if (PHP_VERSION_ID < 70000) {
            $this->markTestSkipped('Test available only for PHP7.0 and newer');
        }
        $this->setUpFile($this->getStubDir() . '/FileWithParameters70.php');

        foreach ($this->parsedRefFile->getFileNamespaces() as $fileNamespace) {
            foreach ($fileNamespace->getFunctions() as $refFunction) {
                $functionName = $refFunction->getName();
                foreach ($refFunction->getParameters() as $refParameter) {
                    $parameterName        = $refParameter->getName();
                    $originalRefParameter = new \ReflectionParameter($functionName, $parameterName);
                    $hasType              = $refParameter->hasType();
                    $this->assertSame(
                        $originalRefParameter->hasType(),
                        $hasType,
                        "Presence of type for parameter {$functionName}:{$parameterName} should be equal"
                    );
                    $message= "Parameter $functionName:$parameterName not equals to the original reflection";
                    if ($hasType) {
                        $parsedReturnType   = $refParameter->getType();
                        $originalReturnType = $originalRefParameter->getType();
                        $this->assertSame($originalReturnType->allowsNull(), $parsedReturnType->allowsNull(), $message);
                        $this->assertSame($originalReturnType->isBuiltin(), $parsedReturnType->isBuiltin(), $message);
                        $this->assertSame($originalReturnType->__toString(), $parsedReturnType->__toString(), $message);
                    } else {
                        $this->assertSame(
                            $originalRefParameter->getType(),
                            $refParameter->getType(),
                            $message
                        );
                    }
                }
            }
        }
    }

    /**
     * Setups file for parsing
     *
     * @param string $fileName File name to use
     */
    private function setUpFile($fileName)
    {
        $resolvedFileName = stream_resolve_include_path($fileName);
        if ($resolvedFileName) {
            $fileName = $resolvedFileName;
        }
        if ($this->lastFileSetUp !== $fileName) {
            $fileNode = ReflectionEngine::parseFile($fileName);

            $reflectionFile = new ReflectionFile($fileName, $fileNode);
            $this->parsedRefFile = $reflectionFile;

            if ($resolvedFileName) {
                include_once $fileName;
            }
            $this->lastFileSetUp = $fileName;
        }
    }
}
