<?php
namespace Aura\Router\Rule;

use Aura\Router\Route;
use Psr\Http\Message\ServerRequestInterface;

// must match **at least one** accept value
class Accept implements RuleInterface
{
    /**
     *
     * Check that the request Accept headers match the Route accept values.
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

        if (! $route->accept || ! isset($server['HTTP_ACCEPT'])) {
            return true;
        }

        $header = str_replace(' ', '', $server['HTTP_ACCEPT']);

        if ($this->match('*/*', $header)) {
            return true;
        }

        foreach ($route->accept as $type) {
            if ($this->match($type, $header)) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * Is the accept header a match?
     *
     * @param string $type
     *
     * @param string $header
     *
     * @return bool True on a match, false if not.
     *
     */
    protected function match($type, $header)
    {
        list($type, $subtype) = explode('/', $type);
        $type = preg_quote($type);
        $subtype = preg_quote($subtype);
        $regex = "#$type/($subtype|\*)(;q=(\d\.\d))?#";

        $found = preg_match($regex, $header, $matches);
        if (! $found) {
            return false;
        }

        if (isset($matches[3])) {
            return $matches[3] !== '0.0';
        }

        return true;
    }
}
