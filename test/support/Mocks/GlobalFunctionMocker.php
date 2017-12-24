<?php
namespace Go\ParserReflection\TestingSupport\Mocks;

final class GlobalFunctionMocker
{
	private static $instance = NULL;

	private function __construct() {
	}

	private function __clone() {
	}

	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

}
