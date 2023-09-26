<?php


namespace Cruxinator\ClassFinder\Tests;

class TestCase extends \PHPUnit\Framework\TestCase
{
    public static function assertStringContainsStringShim(string $substring, string $string, string $message = ''): void
    {
        if (method_exists(\PHPUnit\Framework\Assert::class, 'assertStringContainsString')) {
            parent::assertStringContainsString($substring, $string, $message);
        } else {
            parent::assertContains($substring, $string, $message);
        }
    }
}
