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
     * Gets the path for a Route with **encoded** data replacements for param
     * tokens.
     *
     * @param Route $route The route to generate a path for.
     *
     * @param array $data An array of key-value pairs to interpolate into the
     * param tokens in the path for the Route. Keys that do not map to
     * params are discarded; param tokens that have no mapped key are left in
     * place. All values are rawurlencoded.
     *
     * @return string
     *
     */
    public function generate(Route $route, $data = array())
    {
        $this->raw = false;
        return $this->buildPath($route, $data);
    }

    /**
     *
     * Gets the path for a Route with **raw** data replacements for param
     * tokens.
     *
     * @param Route $route The route to generate a path for.
     *
     * @param array $data An array of key-value pairs to interpolate into the
     * param tokens in the path for the Route. Keys that do not map to
     * params are discarded; param tokens that have no mapped key are left in
     * place. All values are left raw; you will need to encode them yourself.
     *
     * @return string
     *
     */
    public function generateRaw(Route $route, $data = array())
    {
        $this->raw = true;
        return $this->buildPath($route, $data);
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
