<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 23/02/20
 * Time: 9:35 PM.
 */
namespace Cruxinator\ClassFinder\Tests\Unit;

class UnoptimisedClassFinderTest extends ClassFinderTest
{
    public function setUp():void
    {
        parent::setUp();
        $this->classFinder->classLoaderInit = true;
    }
}
