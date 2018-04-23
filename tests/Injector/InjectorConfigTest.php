<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 10:53
 */

namespace TS\DependencyInjection\Injector;


use PHPUnit\Framework\TestCase;
use TS\DependencyInjection\Exception\InjectorConfigException;
use TS\DependencyInjection\Reflection\Reflector;
use TS\DependencyInjection\TestSubjects\ExtendsStandalone;
use TS\DependencyInjection\TestSubjects\MixedArgumentService;
use TS\DependencyInjection\TestSubjects\Standalone;
use TS\DependencyInjection\TestSubjects\StandaloneInterface;


class InjectorConfigTest extends TestCase
{

    /**
     * @var InjectorConfig
     */
    protected $config;


    public function test_registerClassAlias()
    {
        $this->config->registerClassAlias(StandaloneInterface::class, Standalone::class);
        $class = $this->config->resolveClassAlias(StandaloneInterface::class);
        $this->assertEquals(Standalone::class, $class);
    }

    public function test_registerClassAlias_targetNotInstantiable()
    {
        $this->expectException(InjectorConfigException::class);
        $this->config->registerClassAlias(StandaloneInterface::class, StandaloneInterface::class);
    }

    public function test_registerClassAlias_aliasClassNotFound()
    {
        $this->expectException(InjectorConfigException::class);
        $this->config->registerClassAlias('class_does_not_exist', StandaloneInterface::class);
    }

    public function test_registerClassAlias_aliasNotAssignable()
    {
        $this->expectException(InjectorConfigException::class);
        $this->config->registerClassAlias(StandaloneInterface::class, self::class);
    }

    public function test_registerClassAlias_isSingleton()
    {
        $this->config->registerSingleton(Standalone::class);
        $this->config->setSingletonInstantiated(Standalone::class);
        $this->expectException(InjectorConfigException::class);
        $this->config->registerClassAlias(StandaloneInterface::class, Standalone::class);
    }

    public function test_registerClassAlias_sourceIsSingleton()
    {
        $this->config->registerSingleton(Standalone::class);
        $this->expectException(InjectorConfigException::class);
        $this->config->registerClassAlias(Standalone::class, ExtendsStandalone::class);
    }


    public function test_registerParameters()
    {
        $this->config->registerClassParameters(Standalone::class, []);
        $this->assertTrue($this->config->getClassParameters(Standalone::class)->isEmpty());

        $this->config->registerClassParameters(MixedArgumentService::class, ['$a' => 'str']);
        $conf = $this->config->getClassParameters(MixedArgumentService::class);
        $this->assertFalse($conf->isEmpty());
        $this->assertTrue($conf->hasValue('a'));
    }

    public function test_registerParameters_classNotFound()
    {
        $this->expectException(InjectorConfigException::class);
        $this->config->registerClassParameters('class_does_not_exist', []);
    }

    public function test_registerParameters_targetNotInstantiable()
    {
        $this->expectException(InjectorConfigException::class);
        $this->config->registerClassParameters(StandaloneInterface::class, []);
    }

    public function test_registerParameters_isSingleton()
    {
        $this->config->registerSingleton(Standalone::class);
        $this->config->setSingletonInstantiated(Standalone::class);
        $this->expectException(InjectorConfigException::class);
        $this->config->registerClassParameters(Standalone::class, []);
    }


    public function test_registerSingleton()
    {
        $this->assertFalse($this->config->isSingleton(Standalone::class));
        $this->config->registerSingleton(Standalone::class);
        $this->assertTrue($this->config->isSingleton(Standalone::class));
    }

    public function test_registerSingleton_classNotFound()
    {
        $this->expectException(InjectorConfigException::class);
        $this->config->registerSingleton('class_does_not_exist');
    }

    public function test_registerSingleton_notInstantiable()
    {
        $this->expectException(InjectorConfigException::class);
        $this->config->registerSingleton(StandaloneInterface::class);
    }

    public function test_registerSingleton_alreadyInstantiated()
    {
        $this->config->registerSingleton(Standalone::class);
        $this->config->setSingletonInstantiated(Standalone::class);
        $this->expectException(\LogicException::class);
        $this->config->registerSingleton(Standalone::class);
    }


    public function test_registerSingleton_aliased()
    {
        $this->config->registerClassAlias(Standalone::class, ExtendsStandalone::class);
        $this->expectException(\LogicException::class);
        $this->config->registerSingleton(Standalone::class);
    }




    protected function setUp()
    {
        $reflector = new Reflector();
        $this->config = new InjectorConfig($reflector);
    }



}