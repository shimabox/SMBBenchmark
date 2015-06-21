<?php

/*
 |--------------------------------------------------------------------
 | phpunit bootstrap
 |--------------------------------------------------------------------
 */
require_once __DIR__ . '/Benchmark/TestCaseBase.php';
require_once 'SplClassLoader.php';

$includePath = realpath(__DIR__ . '/../src');
$classLoader = new SplClassLoader('SMB', $includePath);
$classLoader->register();
