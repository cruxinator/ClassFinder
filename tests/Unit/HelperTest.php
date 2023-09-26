<?php

namespace Cruxinator\ClassFinder\Tests\Unit;

use Cruxinator\ClassFinder\Tests\ClassFinderConcrete;
use Cruxinator\ClassFinder\Tests\TestCase;

class HelperTest extends TestCase
{
    /**
     * @var ClassFinderConcrete
     */
    protected $classFinder;

    public function setUp(): void
    {
        $this->classFinder = new ClassFinderConcrete();
    }

    /**
     * @dataProvider strStartsWithProvider
     *
     * @param mixed $needle
     * @param mixed $haystack
     * @param mixed $result
     */
    public function testStrStartsWith($needle, $haystack, $result)
    {
        $this->assertEquals($result, $this->classFinder->strStartsWith($needle, $haystack));
    }

    public static function strStartsWithProvider()
    {
        return [
            ['abcd', 'abcdefgh', true],
            ['efgh', 'abcdefgh', false],
            ['1234', '12345678', true],
            ['5678', '12345678', false],
        ];
    }
}
