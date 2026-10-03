<?php

/**
 * TestCaseBase
 *
 * @author shimabox.net
 */
abstract class TestCaseBase extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }
}
