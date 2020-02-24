<?php


namespace Cruxinator\ClassFinder\Tests\Unit;

use Composer\Autoload\ClassLoader;
use Cruxinator\ClassFinder\ClassFinder;
use Cruxinator\ClassFinder\Tests\ClassFinderConcrete;
use Cruxinator\ClassFinder\Tests\TestCase;
use Exception;
use ReflectionClass;

class ClassFinderTest extends TestCase
{
    /**
     * @var ClassFinderConcrete
     */
    protected $classFinder;


    /**
     * @return array
     */
    protected function eagerLoadPhpunit(): array
    {
        /**
         * @var $autoloader ClassLoader
         */
        $autoloader = $this->classFinder->getComposerAutoloader();
        $rawCM = $autoloader->getClassMap();
        foreach ($rawCM as $class => $file) {
            (
                $this->classFinder->strStartsWith('PHPUnit', $class) ||
                $this->classFinder->strStartsWith('PHP_Token', $class) ||
                $this->classFinder->strStartsWith('SebastianBergmann', $class)
            ) &&
            class_exists($class);
        }
        return array($autoloader, $rawCM);
    }

    public function setUp():void
    {
        $this->classFinder = new ClassFinderConcrete();
    }

    /**
     * @throws Exception
     */
    public function testSelf()
    {
        $classes = $this->classFinder->getClasses('Cruxinator\\ClassFinder\\');
        $this->assertEquals(6, count($classes));
        $this->assertTrue(in_array(ClassFinder::class, $classes));
        $this->assertTrue(in_array(TestCase::class, $classes));
    }

    /**
     * @throws Exception
     */
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
    /**
     * @throws Exception
     */
    public function testTwoCallsSameFinder()
    {
        $this->testFindPsr();
        $this->testSelf();
    }

    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    public function testFindPsrNotAbstract()
    {
        $classes = $this->classFinder->getClasses('Psr\\Log\\', function ($class) {
            $reflectionClass = new ReflectionClass($class);
            return !$reflectionClass->isAbstract();
        });
        $this->assertTrue(count($classes) > 0);
        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class));
            $this->assertStringStartsWith('Psr\\Log\\', $class);
            $reflectionClass = new ReflectionClass($class);
            $this->assertFalse($reflectionClass->isAbstract());
        }
    }

    /**
     * @throws \ReflectionException
     * @throws Exception
     */
    public function testFindPsrOnlyAbstract()
    {
        $classes = $this->classFinder->getClasses('Psr\\Log\\', function ($class) {
            $reflectionClass = new ReflectionClass($class);
            return $reflectionClass->isAbstract();
        });
        $this->assertTrue(count($classes) > 0);
        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class));
            $this->assertStringStartsWith('Psr\\Log\\', $class);
            $reflectionClass = new ReflectionClass($class);
            $this->assertTrue($reflectionClass->isAbstract());
        }
    }

    /**
     * @throws Exception
     */
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
        $unoptimised = $this->classFinder->classLoaderInit;
        $this->eagerLoadPhpunit();
        /**
         * @var $autoloader ClassLoader
         */
        $autoloader = $this->classFinder->getComposerAutoloader();
        $rawCM = $autoloader->getClassMap();
        $dummyCL = new ClassLoader();
        $autoloader->unregister();
        unset($rawCM['Composer\Autoload\ClassMapGenerator']);
        $dummyCL->addClassMap($rawCM);
        $dummyCL->register();
        try {
            $this->classFinder->checkState();
            $dummyCL->unregister();
            $autoloader->register();
            if ($unoptimised) {
                $this->fail('unoptimized autoloader should not get this far');
            }
            return;
        } catch (Exception $e) {
            $dummyCL->unregister();
            $autoloader->register();
            if (!$unoptimised) {
                $this->fail('optimised class loader should not throw an exception');
            }
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertStringContainsString('Cruxinator/ClassFinder', $e->getMessage());
            $this->assertStringContainsString('composer/composer', $e->getMessage());
            $this->assertStringContainsString('composer dump-autoload -o', $e->getMessage());
        }
    }
}
