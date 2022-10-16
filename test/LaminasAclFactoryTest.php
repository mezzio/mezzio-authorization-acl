<?php

declare(strict_types=1);

namespace MezzioTest\Authorization\Acl;

use Mezzio\Authorization\Acl\LaminasAcl;
use Mezzio\Authorization\Acl\LaminasAclFactory;
use Mezzio\Authorization\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LaminasAclFactoryTest extends TestCase
{
    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testFactoryWithoutConfig(): void
    {
        $this->container->expects(self::once())
            ->method('get')
            ->willReturn('config')
            ->willReturn([]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container);
    }

    public function testFactoryWithoutLaminasAclConfig(): void
    {
        $this->container->expects(self::once())
            ->method('get')
            ->willReturn('config')
            ->willReturn(['mezzio-authorization-acl' => []]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container);
    }

    public function testFactoryWithoutResources(): void
    {
        $this->container->expects(self::once())
            ->method('get')
            ->willReturn('config')
            ->willReturn([
                'mezzio-authorization-acl' => [
                    'roles' => [],
                ],
            ]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container);
    }

    public function testFactoryWithEmptyRolesResources(): void
    {
        $this->container->expects(self::once())
            ->method('get')
            ->willReturn('config')
            ->willReturn([
                'mezzio-authorization-acl' => [
                    'roles'     => [],
                    'resources' => [],
                ],
            ]);

        $factory    = new LaminasAclFactory();
        $laminasAcl = $factory($this->container);
        self::assertInstanceOf(LaminasAcl::class, $laminasAcl);
    }

    public function testFactoryWithoutAllowOrDeny(): void
    {
        $config = [
            'mezzio-authorization-acl' => [
                'roles'     => [
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
        $this->container->expects(self::once())
            ->method('get')
            ->willReturn('config')
            ->willReturn($config);

        $factory    = new LaminasAclFactory();
        $laminasAcl = $factory($this->container);
        self::assertInstanceOf(LaminasAcl::class, $laminasAcl);
    }

    public function testFactoryWithInvalidRole(): void
    {
        $this->container->expects(self::once())
            ->method('get')
            ->willReturn('config')
            ->willReturn([
                'mezzio-authorization-acl' => [
                    'roles'       => [
                        1 => [],
                    ],
                    'permissions' => [],
                ],
            ]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container);
    }

    public function testFactoryWithUnknownRole(): void
    {
        $this->container->expects(self::once())
            ->method('get')
            ->willReturn('config')
            ->willReturn([
                'mezzio-authorization-acl' => [
                    'roles'     => [
                        'administrator' => [],
                    ],
                    'resources' => [
                        'admin.dashboard',
                        'admin.posts',
                    ],
                    'allow'     => [
                        'editor' => ['admin.dashboard'],
                    ],
                ],
            ]);

        $factory = new LaminasAclFactory();

        $this->expectException(Exception\InvalidConfigException::class);
        $factory($this->container);
    }
}
