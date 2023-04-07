<?php

declare(strict_types=1);

namespace MezzioTest\Authorization\Acl;

use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Authorization\Acl\ConfigProvider;
use Mezzio\Authorization\Acl\LaminasAcl;
use PHPUnit\Framework\TestCase;

use function array_merge_recursive;
use function assert;
use function class_exists;
use function file_get_contents;
use function is_array;
use function is_callable;
use function json_decode;
use function sprintf;

/** @psalm-import-type ServiceManagerConfigurationType from ConfigInterface */
class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    public function testReturnedArrayContainsDependencies(): void
    {
        $config = $this->provider->__invoke();

        self::assertArrayHasKey('dependencies', $config);
        self::assertIsArray($config['dependencies']);
        self::assertArrayHasKey('factories', $config['dependencies']);

        $factories = $config['dependencies']['factories'];
        self::assertIsArray($factories);
        self::assertArrayHasKey(LaminasAcl::class, $factories);
    }

    public function testServicesDefinedInConfigProvider(): void
    {
        $config = ($this->provider)();

        $json = json_decode(
            file_get_contents(__DIR__ . '/../composer.lock'),
            true
        );
        assert(is_array($json) && is_array($json['packages'] ?? null));
        foreach ($json['packages'] as $package) {
            assert(is_array($package));
            if (! isset($package['extra']['laminas']['config-provider'])) {
                continue;
            }

            $providerClass = $package['extra']['laminas']['config-provider'];
            self::assertIsString($providerClass);
            self::assertTrue(class_exists($providerClass));
            /** @psalm-suppress MixedMethodCall */
            $configProvider = new $providerClass();
            assert(is_callable($configProvider));
            $data = $configProvider();
            assert(is_array($data));
            $config = array_merge_recursive($config, $data);
        }

        self::assertIsArray($config['dependencies']);
        /** @psalm-var ServiceManagerConfigurationType $dependencies */
        $dependencies = $config['dependencies'];
        unset($dependencies['services']['config']);
        $dependencies['services']['config'] = [
            'mezzio-authorization-acl' => ['roles' => [], 'resources' => []],
        ];

        $container = $this->getContainer($dependencies);

        $dependencies = $this->provider->getDependencies();
        foreach ($dependencies['factories'] as $name => $factory) {
            self::assertIsString($factory);
            self::assertIsString($name);
            self::assertTrue($container->has($name), sprintf('Container does not contain service %s', $name));
            self::assertIsObject(
                $container->get($name),
                sprintf('Cannot get service %s from container using factory %s', $name, $factory)
            );
        }
    }

    /** @param ServiceManagerConfigurationType $dependencies */
    private function getContainer(array $dependencies): ServiceManager
    {
        return new ServiceManager($dependencies);
    }
}
