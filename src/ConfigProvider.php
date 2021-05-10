<?php

declare(strict_types=1);

namespace Mezzio\Authorization\Acl;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            // Legacy Zend Framework aliases
            'aliases' => [
                \Zend\Expressive\Authorization\Acl\ZendAcl::class => LaminasAcl::class,
            ],
            'factories' => [
                LaminasAcl::class => LaminasAclFactory::class,
            ],
        ];
    }
}
