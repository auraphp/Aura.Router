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

class Accepts
{
    /**
     *
     * Check that the request Accept headers match one Route accept value.
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
        if (! $route->accepts) {
            return true;
        }

        $requestAccepts = $request->getHeaders('Accept');
        if (! $requestAccepts) {
            return true;
        }

        $header = $this->stringify($requestAccepts);
        if ($this->match('*/*', $header)) {
            return true;
        }

        foreach ($route->accepts as $type) {
            if ($this->match($type, $header)) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * Convert the Request 'Accept' header values to a string.
     *
     * @param array $requestAccepts The Accept header values in the Request.
     *
     * @return string
     *
     */
    protected function stringify(array $requestAccepts)
    {
        $result = '';
        foreach ($requestAccepts as $label => $values) {
            foreach ($values as $value) {
                $result .= $value . ';';
            }
        }
        return $result;
    }

    /**
     *
     * Is the Accept header a match?
     *
     * @param string $type The Route accept type.
     *
     * @param string $header The Request accept header.
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
