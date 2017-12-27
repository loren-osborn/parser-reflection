<?php

ini_set('display_errors', 1);
ini_set('memory_limit','1G');

if (defined("AUTOLOAD_PATH")) {
    if (is_file(__DIR__ . '/../' .AUTOLOAD_PATH)) {
        $loader = include_once __DIR__ . '/../' . AUTOLOAD_PATH;
    } else {
        throw new InvalidArgumentException("Cannot load custom autoload file located at ".AUTOLOAD_PATH);
    }
}

const GLOBAL_FUNCTIONS_TO_MOCK_BY_NAMESPACE = 'Go\\ParserReflection\\Instrument:realpath';

foreach (explode(';', GLOBAL_FUNCTIONS_TO_MOCK_BY_NAMESPACE) as $eachNamespaceSpec) {
}
