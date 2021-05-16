<?php

declare(strict_types=1);

namespace Mezzio\Authorization\Acl;

use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Exception\ExceptionInterface as AclExceptionInterface;
use Mezzio\Authorization\AuthorizationInterface;
use Mezzio\Authorization\Exception;
use Psr\Container\ContainerInterface;

use function in_array;
use function sprintf;

class LaminasAclFactory
{
    /**
     * @throws Exception\InvalidConfigException
     */
    public function __invoke(ContainerInterface $container): AuthorizationInterface
    {
        $config = $container->get('config')['mezzio-authorization-acl'] ?? null;
        if (null === $config) {
            throw new Exception\InvalidConfigException(
                'No mezzio-authorization-acl config provided'
            );
        }
        if (! isset($config['roles'])) {
            throw new Exception\InvalidConfigException(
                'No mezzio-authorization-acl roles configured for LaminasAcl'
            );
        }
        if (! isset($config['resources'])) {
            throw new Exception\InvalidConfigException(
                'No mezzio-authorization-acl resources configured for LaminasAcl'
            );
        }

        $acl = new Acl();

        $this->injectRoles($acl, $config['roles']);
        $this->injectResources($acl, $config['resources']);
        $this->injectPermissions($acl, $config['allow'] ?? [], 'allow');
        $this->injectPermissions($acl, $config['deny'] ?? [], 'deny');

        return new LaminasAcl($acl);
    }

    /**
     * @throws Exception\InvalidConfigException
     */
    private function injectRoles(Acl $acl, array $roles): void
    {
        foreach ($roles as $role => $parents) {
            foreach ($parents as $parent) {
                if (! $acl->hasRole($parent)) {
                    try {
                        $acl->addRole($parent);
                    } catch (AclExceptionInterface $e) {
                        throw new Exception\InvalidConfigException($e->getMessage(), $e->getCode(), $e);
                    }
                }
            }
            try {
                $acl->addRole($role, $parents);
            } catch (AclExceptionInterface $e) {
                throw new Exception\InvalidConfigException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @throws Exception\InvalidConfigException
     */
    private function injectResources(Acl $acl, array $resources): void
    {
        foreach ($resources as $resource) {
            try {
                $acl->addResource($resource);
            } catch (AclExceptionInterface $e) {
                throw new Exception\InvalidConfigException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @throws Exception\InvalidConfigException
     */
    private function injectPermissions(Acl $acl, array $permissions, string $type): void
    {
        if (! in_array($type, ['allow', 'deny'], true)) {
            throw new Exception\InvalidConfigException(sprintf(
                'Invalid permission type "%s" provided in configuration; must be one of "allow" or "deny"',
                $type
            ));
        }

        foreach ($permissions as $role => $resources) {
            try {
                $acl->$type($role, $resources);
            } catch (AclExceptionInterface $e) {
                throw new Exception\InvalidConfigException($e->getMessage(), $e->getCode(), $e);
            }
        }
    }
}
