<?php

declare(strict_types=1);

namespace Mezzio\Authorization\Acl;

use Laminas\Permissions\Acl\Acl;
use Mezzio\Authorization\AuthorizationInterface;
use Mezzio\Authorization\Exception;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;

use function sprintf;

class LaminasAcl implements AuthorizationInterface
{
    private Acl $acl;

    public function __construct(Acl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\RuntimeException
     */
    public function isGranted(string $role, ServerRequestInterface $request): bool
    {
        $routeResult = $request->getAttribute(RouteResult::class, false);
        if (! $routeResult instanceof RouteResult) {
            throw new Exception\RuntimeException(sprintf(
                'The %s attribute is missing in the request; cannot perform ACL authorization checks',
                RouteResult::class
            ));
        }

        // No matching route. Everyone can access.
        if ($routeResult->isFailure()) {
            return true;
        }

        $routeName = $routeResult->getMatchedRouteName();

        return $this->acl->isAllowed($role, $routeName);
    }
}
