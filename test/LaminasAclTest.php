<?php

declare(strict_types=1);

namespace MezzioTest\Authorization\Acl;

use Laminas\Permissions\Acl\Acl;
use Mezzio\Authorization\Acl\LaminasAcl;
use Mezzio\Authorization\Exception;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class LaminasAclTest extends TestCase
{
    /** @var Acl&MockObject */
    private $acl;

    protected function setUp(): void
    {
        $this->acl = $this->createMock(Acl::class);
    }

    public function testConstructor(): void
    {
        $laminasAcl = new LaminasAcl($this->acl);
        self::assertInstanceOf(LaminasAcl::class, $laminasAcl);
    }

    public function testIsGrantedWithoutRouteResult(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())
            ->method('getAttribute')
            ->with(RouteResult::class, false)
            ->willReturn(false);

        $laminasAcl = new LaminasAcl($this->acl);

        $this->expectException(Exception\RuntimeException::class);
        $laminasAcl->isGranted('foo', $request);
    }

    public function testIsGranted(): void
    {
        $routeResult = $this->getSuccessRouteResult('home');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())
            ->method('getAttribute')
            ->with(RouteResult::class, false)
            ->willReturn($routeResult);

        $this->acl->expects(self::once())
            ->method('isAllowed')
            ->with('foo', 'home')
            ->willReturn(true);

        $laminasAcl = new LaminasAcl($this->acl);

        self::assertTrue($laminasAcl->isGranted('foo', $request));
    }

    public function testIsNotGranted(): void
    {
        $routeResult = $this->getSuccessRouteResult('home');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())
            ->method('getAttribute')
            ->with(RouteResult::class, false)
            ->willReturn($routeResult);

        $this->acl->expects(self::once())
            ->method('isAllowed')
            ->with('foo', 'home')
            ->willReturn(false);

        $laminasAcl = new LaminasAcl($this->acl);

        self::assertFalse($laminasAcl->isGranted('foo', $request));
    }

    public function testIsGrantedWithFailedRouting(): void
    {
        $routeResult = $this->getFailureRouteResult(Route::HTTP_METHOD_ANY);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())
            ->method('getAttribute')
            ->with(RouteResult::class, false)
            ->willReturn($routeResult);

        $laminasAcl = new LaminasAcl($this->acl);

        $result = $laminasAcl->isGranted('foo', $request);
        self::assertTrue($result);
    }

    private function getSuccessRouteResult(string $routeName): RouteResult
    {
        $route = $this->createMock(Route::class);
        $route->expects(self::atLeast(1))
            ->method('getName')
            ->willReturn($routeName);

        return RouteResult::fromRoute($route);
    }

    private function getFailureRouteResult(?array $methods): RouteResult
    {
        return RouteResult::fromRouteFailure($methods);
    }
}
