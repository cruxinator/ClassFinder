<?php


namespace Tests\Cruxinator\ClassFinder\Unit;

use Tests\Cruxinator\ClassFinder\ClassFinderConcrete;
use Tests\Cruxinator\ClassFinder\TestCase;

class HelperTest extends TestCase
{
    /**
     * @var ClassFinderConcrete
     */
    protected $classFinder;

    public function setUp():void
    {
        $this->classFinder = new ClassFinderConcrete();
    }

    /**
     * @dataProvider strStartsWithProvider
     * @param mixed $needle
     * @param mixed $haystack
     * @param mixed $result
     */
    public function teststrStartsWith($needle, $haystack, $result)
    {
        $this->assertEquals($result, $this->classFinder->strStartsWith($needle, $haystack));
    }


    public function strStartsWithProvider()
    {
        return [
            ['abcd', 'abcdefgh', true],
            ['efgh', 'abcdefgh', false],
            ['1234', '12345678', true],
            ['5678', '12345678', false],
        ];
    }
}
