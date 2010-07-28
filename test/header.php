<?php

// test root directory
define('TEST_ROOT', realpath(dirname(__FILE__).'/../').'/');

// include the core for the framework
require TEST_ROOT.'core/header.php';

// http://www.phpunit.de/manual/current/en/writing-tests-for-phpunit.html
require_once 'PHPUnit/Framework.php';

// include our wrapper "test" for the PHPUnit_Framework_TestCase
require 'test.php';

