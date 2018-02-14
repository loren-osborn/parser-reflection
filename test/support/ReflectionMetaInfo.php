<?php
/**
 * Parser Reflection API
 *
 * @copyright Copyright 2016, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Go\ParserReflection\TestingSupport;

use InvalidArgumentException;

/**
 * Source of some meta information about the classes we're testing.
 */
class ReflectionMetaInfo
{

    /**
     * Check if the input is a native reflection class.
     *
     * @param string $nativeClass The name of the native reflection class.
     *
     * @return boolean Is a native class?
     */
    public function isNativeClass($nativeClass)
    {
        return preg_match('/^\\\\?Reflect(ion([A-Z]\\w*)?|or)$/', $nativeClass);
    }

    /**
     * Returns the parsed reflection class equivilant to the given native class.
     *
     * @param string $nativeClass The name of the native reflection class.
     *
     * @return string The equivilant parsed class.
     */
    public function getParsedClass($nativeClass)
    {
        if (!$this->isNativeClass($nativeClass)) {
            throw new InvalidArgumentException("$nativeClass not a builtin Reflection class.");
        }
        return preg_replace('/^(\\\\?)/', '\\1Go\\\\ParserReflection\\\\', $nativeClass);
    }

    /**
     * Returns the string input with parsed reflection classes replaced with native equivilants.
     *
     * @param string $input String that may contain parsed reflection class names.
     *
     * @return string Tranformed output.
     */
    public function replaceNativeClasses($input)
    {
        return preg_replace(
                    '/((?<![a-zA-Z0-9_\\x7f-\\xff])\\\\+|[^\\\\]|^)\\b(Reflect(ion([A-Z]\\w*)?|or))\\b/',
                    '\\1Go\\\\ParserReflection\\\\\\2',
                    $input);
    }

    /**
     * Check if the input is a parsed reflection class.
     *
     * @param string $nativeClass The name of the parsed reflection class.
     *
     * @return boolean Is a parsed class?
     */
    public function isParsedClass($parsedClass)
    {
        return preg_match('/^\\\\?Go\\\\ParserReflection\\\\Reflect(ion([A-Z]\\w*)?|or)$/', $parsedClass);
    }

    /**
     * Returns the native reflection class equivilant to the given parsed class.
     *
     * @param string $parsedClass The name of the native reflection class.
     *
     * @return string The equivilant native class.
     */
    public function getNativeClass($parsedClass)
    {
        if (!$this->isParsedClass($parsedClass)) {
            throw new InvalidArgumentException("$parsedClass not a parsed Reflection class.");
        }
        return preg_replace('/^(\\\\?)Go\\\\ParserReflection\\\\/', '\\1', $parsedClass);
    }

    /**
     * Returns the string input with native reflection classes replaced with parsed equivilants.
     *
     * @param string $input String that may contain native reflection class names.
     *
     * @return string Tranformed output.
     */
    public function replaceParsedClasses($input)
    {
        return preg_replace(
                    '/((?<![a-zA-Z0-9_\\x7f-\\xff])\\\\+|[^\\\\]|^)\\bGo\\\\+ParserReflection\\\\+(Reflect(ion([A-Z]\\w*)?|or))\\b/',
                    '\\1\\2',
                    $input);
    }

    /**
     * Get constructor arguments that would yeild reflection class object.
     *
     * @param  Reflector|ReflectionException $obj  The object to inspect
     * @return array  Constructor arguments that would create equivilant object.
     *
     */
    public function getReflectionRepresentation($obj)
    {
        $getFunctionNameParameter = (function ($refFunc) {
            $fileLineInfo = [];
            if ($refFunc->getFileName()) {
                $fileLineInfo['file']  = $refFunc->getFileName();
                $fileLineInfo['lines'] = $refFunc->getStartLine();
                if ($refFunc->getStartLine() != $refFunc->getEndLine()) {
                    $fileLineInfo['lines'] .= '-' . $refFunc->getEndLine();
                }
            }
            if ($refFunc instanceof \ReflectionFunction) {
                if ($refFunc->isClosure()) {
                    // getClosure() is not as documented for closures:
                    // It returns the ORIGINAL closure, rather than a new
                    // dynamically created one.
                    return array_merge(
                        [
                            'type'   => 'Closure',
                            'value'  => $refFunc->getClosure(),
                        ],
                        $fileLineInfo);
                }
                return [
                    'type'   => 'value',
                    'value'  => $refFunc->getName(),
                ];
            }
            if ($refFunc instanceof \ReflectionMethod) {
                if ($refFunc->isClosure()) {
                    return array_merge(['type'   => 'Closure'], $fileLineInfo);
                }
                return [
                        'type'   => 'value',
                        'value'  => [
                                $refFunc->getDeclaringClass()->getName(),
                                $refFunc->getName(),
                            ],
                ];
            }
            throw new \Exception("INTERNAL ERROR: Parameter's declaring function is neither ReflectionFunction or ReflectionMethod: " . var_export([
                    '$refFunc' => $refFunc,
                    '$refFunc instanceof \ReflectionMethod' => ($refFunc instanceof \ReflectionMethod),
                    '$refFunc instanceof \ReflectionFunction' => ($refFunc instanceof \ReflectionFunction),
                ], true));
        });
        $constructorParamsByClassName = [
            'ReflectionClass'         => ['name'],
            'ReflectionClassConstant' => [
                [
                    'name'      => 'class',
                    'callChain' => ['getDeclaringClass', 'getName'],
                ],
                'name'
            ],
            'ReflectionException'     => [
                'message',
                [
                    'name'         => 'code',
                    'defaultValue' => 0,
                ],
                [
                    'name'         => 'previous',
                    'defaultValue' => null,
                ],
            ],
            'ReflectionExtension' => ['name'],
            'ReflectionFunction'  => [
                [
                    'name'         => 'name',
                    'getValueFrom' => $getFunctionNameParameter,
                ],
            ],
            'ReflectionMethod' => [
                [
                    'name'      => 'class',
                    'callChain' => ['getDeclaringClass', 'getName'],
                ],
                'name'
            ],
            'ReflectionParameter' => [
                [
                    'name'         => 'function',
                    'getValueFrom' =>
                        (function ($refParam) use ($getFunctionNameParameter) {
                            $refFunc = $refParam->getDeclaringFunction();
                            return $getFunctionNameParameter($refFunc);
                        }),
                ],
                [
                    'name'      => 'parameter',
                    'callChain' => ['getName']
                ],
            ],
            'ReflectionProperty' => [
                [
                    'name'      => 'class',
                    'callChain' => ['getDeclaringClass', 'getName'],
                ],
                'name'
            ],
            'ReflectionGenerator' => [
                [
                    'name'      => 'generator',
                    'callChain' => ['getExecutingGenerator'],
                ],
            ],
            // Untested but unused: (Here for completeness)
            'ReflectionZendExtension' => ['name'],
        ];
        $class = get_class($obj);
        if ($obj instanceof \Exception) {
            $class = 'ReflectionException';
        }
        if (!array_key_exists($class, $constructorParamsByClassName)) {
            throw new \Exception(sprintf('INTERNAL ERROR: EquivilanceExport params for class %s not implemented.', $class));
        }
        $result = [
            'class' => get_class($obj),
            'constructorArgs' => null,
            'displayValues' => [],
        ];
        $supressedDefaultValueInfos = [];
        $argList                    = [];
        foreach ($constructorParamsByClassName[$class] as $paramNameSpec) {
           $normalizedSpec = $paramNameSpec;
            if (is_string($paramNameSpec)) {
                $normalizedSpec = ['name' => $paramNameSpec];
            }
            if (!is_array($normalizedSpec)) {
                throw new \Exception(sprintf('$normalizedSpec [%s] should be an array at this point.', var_export($normalizedSpec, true)));
            }
            if (!array_key_exists('getValueFrom', $normalizedSpec)) {
                if (!array_key_exists('callChain', $normalizedSpec)) {
                    $normalizedSpec['callChain'] = ['get' . ucfirst($normalizedSpec['name'])];
                }
                $normalizedSpec['getValueFrom'] = (function ($inObj) use ($normalizedSpec) {
                    $outVal = $inObj;
                    foreach ($normalizedSpec['callChain'] as $methodName) {
                        $outVal = $outVal->$methodName();
                    }
                    return ['type' => 'value', 'value' => $outVal];
                });
            }
            $getValueFrom = $normalizedSpec['getValueFrom'];
            $paramValInfo = $getValueFrom($obj);
            if (
                !array_key_exists('defaultValue', $normalizedSpec) ||
                !array_key_exists('value', $paramValInfo) ||
                ($paramValInfo['value'] !== $normalizedSpec['defaultValue']) ||
                !is_array($argList)
            ) {
                if (count($supressedDefaultValueInfos) > 0) {
                    foreach ($supressedDefaultValueInfos as $defaultParamName => $defaultParamValInfo) {
                        $result['displayValues'][$defaultParamName] = $defaultParamValInfo;
                        if (is_array($argList)) {
                            $argList[$defaultParamName] = $defaultParamValInfo['value'];
                        }
                    }
                    $supressedDefaultValueInfos = [];
                }
                $result['displayValues'][$normalizedSpec['name']] = $paramValInfo;
                if (!array_key_exists('value', $paramValInfo)) {
                    $argList = null;
                }
                if (is_array($argList)) {
                    $argList[$normalizedSpec['name']] = $paramValInfo['value'];
                }
            }
            else {
                $supressedDefaultValueInfos[$normalizedSpec['name']] = $paramValInfo;
            }
        }
        if (is_array($argList)) {
            $result['constructorArgs'] = $argList;
        }
        else {
            unset($result['constructorArgs']);
        }
        return $result;
    }
}
