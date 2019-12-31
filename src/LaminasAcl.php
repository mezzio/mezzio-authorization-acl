<?php

/**
 * @see       https://github.com/mezzio/mezzio-authorization-acl for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-authorization-acl/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-authorization-acl/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Authorization\Acl;

use Laminas\Permissions\Acl\Acl;
use Mezzio\Authorization\AuthorizationInterface;
use Mezzio\Authorization\Exception;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

class LaminasAcl implements AuthorizationInterface
{
    /**
     * @var Acl
     */
    private $acl;

    public function __construct(Acl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    public function isGranted(string $role, ServerRequestInterface $request) : bool
    {
        $routeResult = $request->getAttribute(RouteResult::class, false);
        if (false === $routeResult) {
            throw new Exception\RuntimeException(sprintf(
                'The %s attribute is missing in the request; cannot perform ACL authorization checks',
                RouteResult::class
            ));
        }
        $routeName = $routeResult->getMatchedRouteName();

        return $this->acl->isAllowed($role, $routeName);
    }
}
