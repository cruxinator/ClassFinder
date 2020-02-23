<?php


namespace Cruxinator\ClassFinder\Tests\Unit;

use Cruxinator\ClassFinder\ClassFinder;
use Cruxinator\ClassFinder\Tests\ClassFinderConcrete;
use Cruxinator\ClassFinder\Tests\TestCase;

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

    public function testSelf()
    {
        $classes = $this->classFinder->getClasses('Cruxinator\\ClassFinder\\');
        $this->assertEquals(6, count($classes));
        $this->assertTrue(in_array(ClassFinder::class, $classes));
        $this->assertTrue(in_array(TestCase::class, $classes));
    }

    public function testFindPsr()
    {
        $classes = $this->classFinder->getClasses('Psr\\Log\\');
        $this->assertTrue(count($classes) > 0);
        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class));
            $this->assertStringStartsWith('Psr\\Log\\', $class);
        }
        $twoClasses = $this->classFinder->getClasses('Psr\\Log\\');
        $this->assertEquals(count($classes), count($twoClasses));
    }

    public function testTwoCallsSameFinder()
    {
        $this->testFindPsr();
        $this->testSelf();
    }

    public function testFindPsrNotAbstract()
    {
        $classes = $this->classFinder->getClasses('Psr\\Log\\', function ($class) {
            $reflectionClass = new \ReflectionClass($class);
            return !$reflectionClass->isAbstract();
        });
        $this->assertTrue(count($classes) > 0);
        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class));
            $this->assertStringStartsWith('Psr\\Log\\', $class);
            $reflectionClass = new \ReflectionClass($class);
            $this->assertFalse($reflectionClass->isAbstract());
        }
    }

    public function testFindPsrOnlyAbstract()
    {
        $classes = $this->classFinder->getClasses('Psr\\Log\\', function ($class) {
            $reflectionClass = new \ReflectionClass($class);
            return $reflectionClass->isAbstract();
        });
        $this->assertTrue(count($classes) > 0);
        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class));
            $this->assertStringStartsWith('Psr\\Log\\', $class);
            $reflectionClass = new \ReflectionClass($class);
            $this->assertTrue($reflectionClass->isAbstract());
        }
    }

    public function testFindPsrNotInVendor()
    {
        $classes = $this->classFinder->getClasses('Psr\\Log\\', null, false);
        $this->assertFalse(count($classes) > 0);
    }

    /**
     * @runInSeparateProcess
     */
    public function testErrorCheck()
    {
        $this->classFinder->classLoaderInit = true;
        $autoloader = $this->classFinder->getComposerAutoloader();
        $classMap = $autoloader->getClassMap();
        if (array_key_exists(__CLASS__, $classMap)) {
            $this->markTestSkipped('Error only works with non optimised autoloader');
        }
        $autoloader->unregister();

        try {
            $this->classFinder->checkState();
            $autoloader->register();
            $this->fail();
            return;
        } catch (\Exception $e) {
            $autoloader->register();
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertContains('Cruxinator/ClassFinder', $e->getMessage());
            $this->assertContains('composer/composer', $e->getMessage());
            $this->assertContains('composer dump-autoload -o', $e->getMessage());
        }
    }
}
