<?php
/**
 * Parser Reflection API
 *
 * @copyright Copyright 2016, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Go\ParserReflection\Tests;

use Go\ParserReflection\TestingSupport\Stubs\AbstractClassWithMethods;
use Go\ParserReflection\ReflectionEngine;
use Go\ParserReflection\ReflectionFile;
use Go\ParserReflection\ReflectionFileNamespace;

abstract class AbstractClassTestCaseBase extends TestCaseBase
{
    const DEFAULT_STUB_FILENAME = 'FileWithClasses55.php';

    /**
     * @var string
     */
    protected $lastFileSetUp;

    /**
     * @var ReflectionFileNamespace
     */
    protected $parsedRefFileNamespace;

    /**
     * @var \Go\ParserReflection\ReflectionClass
     */
    protected $parsedRefClass;

    /**
     * Name of the class to compare
     *
     * @var string
     */
    protected static $reflectionClassToTest = \Reflection::class;

    /**
     * Name of the class to load for default tests
     *
     * @var string
     */
    protected static $defaultClassToLoad = AbstractClassWithMethods::class;

    public function testCoverAllMethods()
    {
        $allInternalMethods = get_class_methods(static::$reflectionClassToTest);
        $allMissedMethods   = [];

        foreach ($allInternalMethods as $internalMethodName) {
            if ('export' === $internalMethodName) {
                continue;
            }
            $refMethod    = new \ReflectionMethod('Go\\ParserReflection\\' . static::$reflectionClassToTest, $internalMethodName);
            $definerClass = $refMethod->getDeclaringClass()->getName();
            if (strpos($definerClass, 'Go\\ParserReflection\\') !== 0) {
                $allMissedMethods[] = $internalMethodName;
            }
        }

        if (count($allMissedMethods) > 0) {
            // This **SHOULD** fail, but we'll turn this off until PHP 7.2 support is complete:
            // $this->fail('Methods ' . join($allMissedMethods, ', ') . ' are not implemented');
            $this->markTestIncomplete('Methods ' . join($allMissedMethods, ', ') . ' are not implemented');
        }
    }


    /**
     * Provides a list of files for analysis
     *
     * @return array
     */
    public function getFilesToAnalyze()
    {
        $files = ['PHP5.5' => [$this->getStubDir() . '/FileWithClasses55.php']];

        if (PHP_VERSION_ID >= 50600) {
            $files['PHP5.6'] = [$this->getStubDir() . '/FileWithClasses56.php'];
        }
        if (PHP_VERSION_ID >= 70000) {
            $files['PHP7.0'] = [$this->getStubDir() . '/FileWithClasses70.php'];
        }
        if (PHP_VERSION_ID >= 70100) {
            $files['PHP7.1'] = [$this->getStubDir() . '/FileWithClasses71.php'];
        }

        return $files;
    }

    /**
     * Provides a list of classes for analysis in the form [Class, FileName]
     *
     * @return array
     */
    public function getClassesToAnalyze()
    {
        // Random selection of built in classes.
        $builtInClasses = ['stdClass', 'DateTime', 'Exception', 'Directory', 'Closure', 'ReflectionFunction'];
        $classes        = [];
        foreach ($builtInClasses as $className) {
            $classes[$className] = ['class' => $className, 'fileName'  => null, 'origClass' => $className];
        }
        $files = $this->getFilesToAnalyze();
        foreach ($files as $filenameArgList) {
            $argKeys                              = array_keys($filenameArgList);
            $fileName                             = $filenameArgList[$argKeys[0]];
            $resolvedFileName                     = stream_resolve_include_path($fileName);
            $fileNode                             = ReflectionEngine::parseFile($resolvedFileName);
            list($fakeFileName, $classNameFilter) = $this->getNeverIncludedFileFilter($resolvedFileName);
            $realAndFake = [
                'real' => ['file' => $resolvedFileName, 'classNameFilter' => 'strval'         ],
                'fake' => ['file' => $fakeFileName,     'classNameFilter' => $classNameFilter],
            ];

            $reflectionFile = new ReflectionFile($resolvedFileName, $fileNode);
            foreach ($reflectionFile->getFileNamespaces() as $fileNamespace) {
                foreach ($fileNamespace->getClasses() as $parsedClass) {
                    foreach ($realAndFake as $classFaker) {
                        $getClassName = $classFaker['classNameFilter'];
                        $classes[$argKeys[0] . ': ' . $getClassName($parsedClass->getName())] = [
                            'class'     => $getClassName($parsedClass->getName()),
                            'fileName'  => $classFaker['file'],
                            'origClass' => $parsedClass->getName(),
                        ];
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * Returns list of ReflectionMethod getters that be checked directly without additional arguments
     *
     * @return array
     */
    abstract protected function getGettersToCheck();

    /**
     * Setups file for parsing
     *
     * @param string $fileName File to use
     */
    protected function setUpFile($fileName)
    {
        if ($resolvedFileName = stream_resolve_include_path($fileName)) {
            $fileName = $resolvedFileName;
        }
        if ($this->lastFileSetUp && !$fileName) {
            $this->lastFileSetUp          = null;
            $this->parsedRefFileNamespace = null;
        } else if ($fileName && ($this->lastFileSetUp !== $fileName)) {
            $fileNode                     = ReflectionEngine::parseFile($fileName);
            $reflectionFile               = new ReflectionFile($fileName, $fileNode);
            $namespace                    = $this->getStubNamespaceFromFilename($fileName);
            $this->parsedRefFileNamespace = $reflectionFile->getFileNamespace($namespace);
            if (
                !($this->parsedRefFileNamespace instanceof ReflectionFileNamespace) ||
                !($this->parsedRefFileNamespace->getNode()) ||
                (preg_match('/\\bNeverIncluded\\b/', $fileName) xor preg_match('/\\bNeverIncluded\\b/', $namespace))
            ) {
                throw new \Exception(sprintf(
                    '$reflectionFile->getFileNamespace(%s) returned %s instead of %s where $reflectionFile->getName() returned %s',
                    $this->getStringificationOf($namespace),
                    $this->getStringificationOf($this->parsedRefFileNamespace),
                    ReflectionFileNamespace::class,
                    $this->getStringificationOf($reflectionFile->getName())));
            }
            $this->parsedRefClass = $this->parsedRefFileNamespace->getClass(static::$defaultClassToLoad);

            if (file_exists($fileName)) {
                include_once $fileName;
            } else if (preg_match(',[/\\\\]+NeverIncluded[/\\\\]+,', $fileName)) {
                $realFileName         = preg_replace(',[/\\\\]+NeverIncluded[/\\\\]+,', '/', $fileName);
                $resolvedRealFileName = stream_resolve_include_path($realFileName);
                if (file_exists($resolvedRealFileName)) {
                    include_once $resolvedRealFileName;
                }
                $this->setUpFakeFileLocator();
            }
            $this->lastFileSetUp = $fileName;
        }
    }

    /**
     * Setups file for parsing
     */
    protected function setUpFakeFileLocator()
    {
        ReflectionEngine::init(new CallableLocator(function ($className) {
            if (preg_match('/(^|\\\\)Stub\\\\NeverIncluded\\\\/', $className)) {
                $origClass = preg_replace('/(^|\\\\)Stub\\\\NeverIncluded\\\\/', '\\1Stub\\\\', $className);
                if (
                    class_exists($origClass, false) ||
                    interface_exists($origClass, false) ||
                    trait_exists($origClass, false)
                ) {
                    $origClassReflection = new \ReflectionClass($origClass);
                    $origFile            = $origClassReflection->getFileName();
                    if ($origFile && preg_match(',[/\\\\]+Stub[/\\\\]+,', $origFile)) {
                        $fakeFile = preg_replace(',([/\\\\]+)Stub([/\\\\]+),', '\\1Stub\\2NeverIncluded\\2', $origFile);
                        return $fakeFile;
                    }
                    else {
                        throw new \Exception(sprintf('locator failed becaue $origClass (%s) in file %s didn\'t contain Stub directory', var_export($origClass, true), var_export($origFile, true)));
                    }
                }
                else {
                    throw new \Exception(sprintf('locator failed becaue $origClass (%s) doesn\'t exist', var_export($origClass, true)));
                }
            }
            else {
                throw new \Exception(sprintf('locator failed becaue $className (%s) didn\'t contain Stub\\NeverIncluded', var_export($className, true)));
            }
            return false;
        }));
    }

    protected function setUp()
    {
        $this->setUpFile($this->getStubDir() . '/' . self::DEFAULT_STUB_FILENAME);
    }
}
