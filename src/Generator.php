<?php
/**
 *
 * This file is part of the Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use ArrayObject;

/**
 *
 * Generates URL paths from routes.
 *
 * @package Aura.Router
 *
 */
class Generator
{
    /**
     *
     * The map of all routes.
     *
     * @var Map
     *
     */
    protected $map;

    /**
     *
     * The route from which the path is being generated.
     *
     * @var Route
     *
     */
    protected $route;

    /**
     *
     * The path being generated.
     *
     * @var string
     *
     */
    protected $path;

    /**
     *
     * Data being interpolated into the path.
     *
     * @var array
     *
     */
    protected $data;

    /**
     *
     * Replacement data.
     *
     * @var array
     *
     */
    protected $repl;

    /**
     *
     * Leave values raw?
     *
     * @var bool
     *
     */
    protected $raw;

    /**
     *
     * Constructor.
     *
     * @param Map $map A route collection object.
     *
     */
    public function __construct(Map $map)
    {
        $this->map = $map;
    }

    /**
     *
     * Looks up a route by name, and interpolates data into it to return
     * a URI path.
     *
     * @param string $name The route name to look up.
     *
     * @param array $data The data to interpolate into the URI; data keys
     * map to param tokens in the path.
     *
     * @return string|false A URI path string if the route name is found, or
     * boolean false if not.
     *
     * @throws Exception\RouteNotFound
     *
     */
    public function generate($name, $data = array())
    {
        $route = $this->getRouteForGenerate($name);
        $this->raw = false;
        return $this->buildPath($route, $data);
    }

    /**
     *
     * Generate the route without url encoding.
     *
     * @param string $name The route name to look up.
     *
     * @param array $data The data to interpolate into the URI; data keys
     * map to param tokens in the path.
     *
     * @return string|false A URI path string if the route name is found, or
     * boolean false if not.
     *
     * @throws Exception\RouteNotFound
     *
     */
    public function generateRaw($name, $data = array())
    {
        $route = $this->getRouteForGenerate($name);
        $this->raw = true;
        return $this->buildPath($route, $data);
    }

    /**
     *
     * Gets a Route for generation.
     *
     * @param string $name Get this route name.
     *
     * @return Route
     *
     * @throws Exception\RouteNotFound when the named route does not exist.
     *
     */
    protected function getRouteForGenerate($name)
    {
        if (! $this->map->offsetExists($name)) {
            throw new Exception\RouteNotFound($name);
        }

        return $this->map->offsetGet($name);
    }

    /**
     *
     * Gets the path for a Route.
     *
     * @param Route $route The route to generate a path for.
     *
     * @param array $data An array of key-value pairs to interpolate into the
     * param tokens in the path for the Route.
     *
     * @return string
     *
     */
    protected function buildPath(Route $route, $data = array())
    {
        $this->route = $route;
        $this->data = $data;
        $this->path = $this->route->path;
        $this->repl = array();

        $this->buildData();
        $this->buildTokenReplacements();
        $this->buildOptionalReplacements();
        $this->path = strtr($this->path, $this->repl);
        $this->buildWildcardReplacement();

        return $this->path;
    }

    /**
     *
     * Builds the data for token replacements.
     *
     * @return array
     *
     */
    protected function buildData()
    {
        // the data for replacements
        $this->data = array_merge($this->route->values, $this->data);

        // use a callable to modify the data?
        if ($this->route->generate) {
            // pass the data as an object, not as an array, so we can avoid
            // tricky hacks for references
            $arrobj = new ArrayObject($this->data);
            // modify
            call_user_func($this->route->generate, $arrobj);
            // convert back to array
            $this->data = $arrobj->getArrayCopy();
        }
    }

    /**
     *
     * Builds urlencoded data for token replacements.
     *
     * @return array
     *
     */
    protected function buildTokenReplacements()
    {
        foreach ($this->data as $key => $val) {
            $this->repl["{{$key}}"] = $this->encode($val);
        }
    }

    /**
     *
     * Builds replacements for params in the generated path.
     *
     * @return string
     *
     */
    protected function buildOptionalReplacements()
    {
        // replacements for optional params, if any
        preg_match('#{/([a-z][a-zA-Z0-9_,]*)}#', $this->path, $matches);
        if (! $matches) {
            return;
        }

        // this is the full token to replace in the path
        $key = $matches[0];
        // start with an empty replacement
        $this->repl[$key] = '';
        // the optional param names in the token
        $names = explode(',', $matches[1]);
        // look for data for each of the param names
        foreach ($names as $name) {
            // is there data for this optional param?
            if (! isset($this->data[$name])) {
                // options are *sequentially* optional, so if one is
                // missing, we're done
                break;
            }
            // encode the optional value
            $this->repl[$key] .= '/' . $this->encode($this->data[$name]);
        }
    }

    /**
     *
     * Builds a wildcard replacement in the generated path.
     *
     * @return string
     *
     */
    protected function buildWildcardReplacement()
    {
        $wildcard = $this->route->wildcard;
        if ($wildcard && isset($this->data[$wildcard])) {
            $this->path = rtrim($this->path, '/');
            foreach ($this->data[$wildcard] as $val) {
                $this->path .= '/' . $this->encode($val);
            }
        }
    }

    /**
     *
     * Encodes values, or leaves them raw.
     *
     * @param string $val The value to encode or leave raw.
     *
     * @return mixed
     *
     */
    protected function encode($val)
    {
        if ($this->raw) {
            return $val;
        }

        return is_scalar($val) ? rawurlencode($val) : null;
    }
}
