<?php

/**
 * @see       https://github.com/mezzio/mezzio-authorization-acl for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-authorization-acl/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-authorization-acl/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Authorization\Acl;

use Mezzio\Authorization\Acl\LaminasAcl;
use Mezzio\Authorization\Acl\LaminasAclFactory;
use Mezzio\Authorization\Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

class LaminasAclFactoryTest extends TestCase
{
    use ProphecyTrait;

    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    protected function setUp() : void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testFactoryWithoutConfig()
    {
        $this->container->get('config')->willReturn([]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWithoutLaminasAclConfig()
    {
        $this->container->get('config')->willReturn(['mezzio-authorization-acl' => []]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWithoutResources()
    {
        $this->container->get('config')->willReturn([
            'mezzio-authorization-acl' => [
                'roles' => [],
            ],
        ]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWithEmptyRolesResources()
    {
        $this->container->get('config')->willReturn([
            'mezzio-authorization-acl' => [
                'roles' => [],
                'resources' => [],
            ],
        ]);

        $factory = new LaminasAclFactory();
        $laminasAcl = $factory($this->container->reveal());
        $this->assertInstanceOf(LaminasAcl::class, $laminasAcl);
    }

    public function testFactoryWithoutAllowOrDeny()
    {
        $config = [
            'mezzio-authorization-acl' => [
                'roles' => [
                    'admini'      => [],
                    'editor'      => ['administrator'],
                    'contributor' => ['editor'],
                ],
                'resources' => [
                    'admin.dashboard',
                    'admin.posts',
                    'admin.publish',
                    'admin.settings',
                ],
            ],
        ];
        $this->container->get('config')->willReturn($config);

        $factory = new LaminasAclFactory();
        $laminasAcl = $factory($this->container->reveal());
        $this->assertInstanceOf(LaminasAcl::class, $laminasAcl);
    }

    public function testFactoryWithInvalidRole()
    {
        $this->container->get('config')->willReturn([
            'mezzio-authorization-acl' => [
                'roles' => [
                    1 => [],
                ],
                'permissions' => [],
            ],
        ]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWithUnknownRole()
    {
        $this->container->get('config')->willReturn([
            'mezzio-authorization-acl' => [
                'roles' => [
                    'administrator' => [],
                ],
                'resources' => [
                    'admin.dashboard',
                    'admin.posts',
                ],
                'allow' => [
                    'editor' => ['admin.dashboard'],
                ],
            ],
        ]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }
}
