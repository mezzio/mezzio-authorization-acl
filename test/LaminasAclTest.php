<?php

/**
 * @see       https://github.com/mezzio/mezzio-authorization-acl for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-authorization-acl/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-authorization-acl/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Authorization\Acl;

use Laminas\Permissions\Acl\Acl;
use Mezzio\Authorization\Acl\Exception;
use Mezzio\Authorization\Acl\LaminasAcl;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class LaminasAclTest extends TestCase
{
    public function setUp()
    {
        $this->acl = $this->prophesize(Acl::class);
    }

    public function testConstructor()
    {
        $laminasAcl = new LaminasAcl($this->acl->reveal());
        $this->assertInstanceOf(LaminasAcl::class, $laminasAcl);
    }

    public function testIsGrantedWithoutRouteResult()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(RouteResult::class, false)->willReturn(false);

        $laminasAcl = new LaminasAcl($this->acl->reveal());

        $this->expectException(Exception\RuntimeException::class);
        $laminasAcl->isGranted('foo', $request->reveal());
    }

    public function testIsGranted()
    {
        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult->getMatchedRouteName()->willReturn('home');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(RouteResult::class, false)
                ->willReturn($routeResult->reveal());

        $this->acl->isAllowed('foo', 'home')->willReturn(true);
        $laminasAcl = new LaminasAcl($this->acl->reveal());

        $this->assertTrue($laminasAcl->isGranted('foo', $request->reveal()));
    }

    public function testIsNotGranted()
    {
        $routeResult = $this->prophesize(RouteResult::class);
        $routeResult->getMatchedRouteName()->willReturn('home');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(RouteResult::class, false)
                ->willReturn($routeResult->reveal());

        $this->acl->isAllowed('foo', 'home')->willReturn(false);
        $laminasAcl = new LaminasAcl($this->acl->reveal());

        $this->assertFalse($laminasAcl->isGranted('foo', $request->reveal()));
    }
}
