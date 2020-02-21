<?php


namespace Tests\Cruxinator\ClassFinder\Unit;


use Tests\Cruxinator\ClassFinder\ClassFinderConcrete;
use Tests\Cruxinator\ClassFinder\TestCase;

class ClassFinderTest extends TestCase
{
    /**
     * @var ClassFinderConcrete
     */
    protected $classFinder;

    public function setUp():void
    {
        $this->classFinder = new ClassFinderConcrete();
    }


    public function testFindPsr(){
        $classes = $this->classFinder->getClasses("Psr\\Log\\");
        $this->assertTrue(count($classes) > 0);
        foreach($classes as $class){
            $this->assertTrue(class_exists($class));
            $this->assertStringStartsWith("Psr\\Log\\",$class);
        }
    }

    public function testFindPsrNotAbstract(){
        $classes = $this->classFinder->getClasses("Psr\\Log\\", function($class){
            $reflectionClass = new \ReflectionClass($class);
            return !$reflectionClass->isAbstract();
        });
        $this->assertTrue(count($classes) > 0);
        foreach($classes as $class){
            $this->assertTrue(class_exists($class));
            $this->assertStringStartsWith("Psr\\Log\\",$class);
            $reflectionClass = new \ReflectionClass($class);
            $this->assertFalse($reflectionClass->isAbstract());
        }
    }

    public function testFindPsrOnlyAbstract(){
        $classes = $this->classFinder->getClasses("Psr\\Log\\", function($class){
            $reflectionClass = new \ReflectionClass($class);
            return $reflectionClass->isAbstract();
        });
        $this->assertTrue(count($classes) > 0);
        foreach($classes as $class){
            $this->assertTrue(class_exists($class));
            $this->assertStringStartsWith("Psr\\Log\\",$class);
            $reflectionClass = new \ReflectionClass($class);
            $this->assertTrue($reflectionClass->isAbstract());
        }
    }
}