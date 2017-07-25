<?php
/**
 * Parser Reflection API
 *
 * @copyright Copyright 2016, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Go\ParserReflection\Testing\Tests;

use PHPUnit_Framework_TestCase;

class TestCaseBase extends PHPUnit_Framework_TestCase
{
    public function getStubDir()
    {
        return dirname(__DIR__) . '/Support/Stub';
    }

    public function getStubNamespace()
    {
        return 'Go\\ParserReflection\\Testing\\Support\\Stub';
    }
}
