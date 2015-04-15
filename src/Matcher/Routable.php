<?php
namespace Aura\Router\Matcher;

use Aura\Router\Route;
use Psr\Http\Message\ServerRequestInterface;

class Routable implements MatcherInterface
{
    /**
     *
     * Is the route even routable to begin with?
     *
     * @param ServerRequestInterface $request The HTTP request.
     *
     * @param Route $route The route.
     *
     * @return bool True on success, false on failure.
     *
     */
    public function __invoke(ServerRequestInterface $request, Route $route)
    {
        return (bool) $route->routable;
    }
}
