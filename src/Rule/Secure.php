<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router\Rule;

use Aura\Router\Route;
use Psr\Http\Message\ServerRequestInterface;

class Secure implements RuleInterface
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
