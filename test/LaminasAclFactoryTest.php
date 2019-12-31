<?php

/**
 * @see       https://github.com/mezzio/mezzio-authorization-acl for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-authorization-acl/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-authorization-acl/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Authorization\Acl;

use Mezzio\Authorization\Acl\Exception;
use Mezzio\Authorization\Acl\LaminasAcl;
use Mezzio\Authorization\Acl\LaminasAclFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LaminasAclFactoryTest extends TestCase
{
    protected function setUp()
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
        $this->container->get('config')->willReturn(['authorization' => []]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWithoutResources()
    {
        $this->container->get('config')->willReturn([
            'authorization' => [
                'roles' => []
            ]
        ]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryWithEmptyRolesResources()
    {
        $this->container->get('config')->willReturn([
            'authorization' => [
                'roles' => [],
                'resources' => []
            ]
        ]);

        $factory = new LaminasAclFactory();
        $laminasAcl = $factory($this->container->reveal());
        $this->assertInstanceOf(LaminasAcl::class, $laminasAcl);
    }

    public function testFactoryWithoutAllowOrDeny()
    {
        $config = [
            'authorization' => [
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
                ]
            ]
        ];
        $this->container->get('config')->willReturn($config);

        $factory = new LaminasAclFactory();
        $laminasAcl = $factory($this->container->reveal());
        $this->assertInstanceOf(LaminasAcl::class, $laminasAcl);
    }

    public function testFactoryWithInvalidRole()
    {
        $this->container->get('config')->willReturn([
            'authorization' => [
                'roles' => [
                    1 => [],
                ],
                'permissions' => []
            ]
        ]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $laminasAcl = $factory($this->container->reveal());
    }

    public function testFactoryWithUnknownRole()
    {
        $this->container->get('config')->willReturn([
            'authorization' => [
                'roles' => [
                    'administrator' => [],
                ],
                'resources' => [
                    'admin.dashboard',
                    'admin.posts',
                ],
                'allow' => [
                    'editor' => ['admin.dashboard']
                ]
            ]
        ]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $laminasAcl = $factory($this->container->reveal());
    }
}
