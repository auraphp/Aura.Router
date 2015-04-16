<?php
namespace Aura\Router\Rule;

use Aura\Router\Route;
use Psr\Http\Message\ServerRequestInterface;

class Server implements RuleInterface
{
    /**
     *
     * Checks that $_SERVER values match their related regular expressions.
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
        $server = $request->getServerParams();
        $attributes = [];
        foreach ($route->server as $name => $regex) {
            $matches = $this->match($server, $name, $regex);
            if (! $matches) {
                return false;
            }
            $attributes[$name] = $matches[$name];
        }

        $route->addMatches($attributes);
        return true;
    }

    /**
     *
     * Does a server key match a regex?
     *
     * @param ServerRequestInterface $request The HTTP request.
     *
     * @param string $name The server key.
     *
     * @param string $regex The regex to match against.
     *
     * @return array The matches.
     *
     */
    protected function match($server, $name, $regex)
    {
        $value = isset($server[$name])
               ? $server[$name]
               : '';
        $regex = "#(?P<{$name}>{$regex})#";
        preg_match($regex, $value, $matches);
        return $matches;
    }
}
