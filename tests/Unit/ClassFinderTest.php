<?php


namespace Cruxinator\ClassFinder\Tests\Unit;

use Composer\Autoload\ClassLoader;
use Cruxinator\ClassFinder\ClassFinder;
use Cruxinator\ClassFinder\Tests\ClassFinderConcrete;
use Cruxinator\ClassFinder\Tests\TestCase;
use Exception;
use ReflectionClass;
use ReflectionProperty;

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

    public function testClassMapInitCache()
    {
        $forceInit = $this->classFinder->classLoaderInit;
        $this->classFinder->initClassMap();
        if ($forceInit) {
            $this->assertNull($this->classFinder->optimisedClassMap);
        } else {
            $this->assertIsArray($this->classFinder->optimisedClassMap);
        }
        $this->assertTrue($this->classFinder->classLoaderInit);
    }
    /**
     * @runInSeparateProcess
     */
    public function testErrorCheck()
    {
        $unoptimised = $this->classFinder->classLoaderInit;
        $this->eagerLoadPhpunit();
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
            $pass = true;
        } catch (Exception $e) {
            $dummyCL->unregister();
            $autoloader->register();
            $pass = false;
            if (!$unoptimised) {
                $this->fail('optimised class loader should not throw an exception');
            }
            $this->assertNull($this->classFinder->optimisedClassMap);
            $this->assertInstanceOf(Exception::class, $e);
            $this->assertStringContainsString('Cruxinator/ClassFinder', $e->getMessage());
            $this->assertStringContainsString('composer/composer', $e->getMessage());
            $this->assertStringContainsString('composer dump-autoload -o', $e->getMessage());
        }
        $this->assertEquals(!$unoptimised, $pass);
        if ($pass) {
            $this->assertNotNull($this->classFinder->optimisedClassMap);
        }
    }


    public function testFindCompatibleNamespace()
    {
        $psr4 = $this->classFinder->getComposerAutoloader()->getPrefixesPsr4();
        $namespaces = $this->classFinder->findCompatibleNamespace(\Composer\Installer\BinaryInstaller::class, $psr4);
        $this->assertCount(1, $namespaces);
        $this->assertArrayHasKey(0, $namespaces);
        $this->assertStringEndsWith('composer/composer/src/Composer', $namespaces[0]);
    }

    public function testFindCompatibleNamespaceUnknown()
    {
        $psr4 = $this->classFinder->getComposerAutoloader()->getPrefixesPsr4();
        $namespaces = $this->classFinder->findCompatibleNamespace('\\namespace\\does\\not\\exist', $psr4);
        $this->assertArrayHasKey(0, $namespaces);
        foreach ($namespaces as $directorys) {
            foreach ($directorys as $directory) {
                $this->assertDirectoryExists($directory);
            }
        }
    }
    /**
     * @runInSeparateProcess
     */
    public function testGetClassMapWithTrap()
    {
        $unoptimised = $this->classFinder->classLoaderInit;
        $autoloader = $this->classFinder->getComposerAutoloader();
        $classMap = $autoloader->getClassMap();
        $this->eagerLoadPhpunit();
        $this->classFinder->getComposerAutoloader()->addPsr4('PHPUnit\\Framework\\', dirname((new ReflectionClass(\PHPUnit\Framework\Assert::class))->getFileName()));
        unset($classMap[\PHPUnit\Framework\Assert::class]);
        $reflectionProperty = new ReflectionProperty(ClassLoader::class, 'classMap');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->classFinder->getComposerAutoloader(), $classMap);
        $this->classFinder->getComposerAutoloader()->addClassMap(['DummyNamesace\\DummyClass' => __FILE__]);
        if ($unoptimised) {
            $reflectionProperty->setValue($this->classFinder->getComposerAutoloader(), []);
        }
        $ourClassMap = $this->classFinder->getClassMap('PHPUnit\Framework');
        if ($unoptimised) {
            $this->assertArrayNotHasKey('DummyNamesace\\DummyClass\\', $ourClassMap);
            $this->assertArrayHasKey(\PHPUnit\Framework\Assert::class, $ourClassMap);
            $this->assertNull($this->classFinder->optimisedClassMap);

        } else {
            $this->assertArrayHasKey('DummyNamesace\\DummyClass', $ourClassMap);
            $this->assertEquals(__FILE__, $ourClassMap['DummyNamesace\\DummyClass']);
            $this->assertArrayNotHasKey(\PHPUnit\Framework\Assert::class, $ourClassMap);
            $this->assertNotNull($this->classFinder->optimisedClassMap);

        }
    }
}
