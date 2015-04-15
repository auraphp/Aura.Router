<?php
namespace Aura\Router\Matcher;

use Aura\Router\Route;
use Psr\Http\Message\ServerRequestInterface;

class Secure implements MatcherInterface
{
    /**
     *
     * Checks that the Route `$secure` matches the corresponding server values.
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
        if ($route->secure === null) {
            return true;
        }

        $server = $request->getServerParams();
        $secure = (isset($server['HTTPS']) && $server['HTTPS'] == 'on')
               || (isset($server['SERVER_PORT']) && $server['SERVER_PORT'] == 443);

        return $route->secure == $secure;
    }
}
