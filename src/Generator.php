<?php
/**
 *
 * This file is part of the Aura for PHP.
 *
 * @package Aura.Router
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
    protected $route;

    /**
     *
     * Gets the path for a Route with data replacements for param tokens.
     *
     * @param Route $route The route to generate a path for.
     *
     * @param array $data An array of key-value pairs to interpolate into the
     * param tokens in the path for the Route. Keys that do not map to
     * params are discarded; param tokens that have no mapped key are left in
     * place.
     *
     * @return string
     *
     */
    public function generate(Route $route, $data = array(), $raw = array())
    {
        $this->route = $route;

        $path = $this->route->path;
        $data = $this->generateData($data);
        $repl = $this->generateTokenReplacements($data, $raw);
        $repl = $this->generateOptionalReplacements($path, $repl, $data, $raw);
        $path = strtr($path, $repl);
        $path = $this->generateWildcardReplacement($path, $data, $raw);
        return $path;
    }

    /**
     *
     * Generates the data for token replacements.
     *
     * @param array $data Data for the token replacements.
     *
     * @return array
     *
     */
    protected function generateData(array $data)
    {
        // the data for replacements
        $data = array_merge($this->route->values, $data);

        // use a callable to modify the data?
        if ($this->route->generate) {
            // pass the data as an object, not as an array, so we can avoid
            // tricky hacks for references
            $arrobj = new ArrayObject($data);
            // modify
            call_user_func($this->route->generate, $arrobj);
            // convert back to array
            $data = $arrobj->getArrayCopy();
        }

        return $data;
    }

    /**
     *
     * Generates urlencoded data for token replacements.
     *
     * @param array $data Data for the token replacements.
     *
     * @return array
     *
     */
    protected function generateTokenReplacements($data, $raw)
    {
        $repl = array();
        foreach ($data as $key => $val) {
            if (is_scalar($val) || $val === null) {
                $repl["{{$key}}"] = $this->encode($key, $val, $raw);
            }
        }
        return $repl;
    }

    /**
     *
     * Generates replacements for params in the generated path.
     *
     * @param string $path The generated path.
     *
     * @param array $repl The token replacements.
     *
     * @param array $data The original data.
     *
     * @return string
     *
     */
    protected function generateOptionalReplacements($path, $repl, $data, $raw)
    {
        // replacements for optional params, if any
        preg_match('#{/([a-z][a-zA-Z0-9_,]*)}#', $path, $matches);
        if (! $matches) {
            return $repl;
        }

        // this is the full token to replace in the path
        $key = $matches[0];
        // start with an empty replacement
        $repl[$key] = '';
        // the optional param names in the token
        $names = explode(',', $matches[1]);
        // look for data for each of the param names
        foreach ($names as $name) {
            // is there data for this optional param?
            if (! isset($data[$name])) {
                // options are *sequentially* optional, so if one is
                // missing, we're done
                break;
            }
            // encode the optional value
            if (is_scalar($data[$name])) {
                $repl[$key] .= '/' . $this->encode($name, $data[$name], $raw);
            }
        }
        return $repl;
    }

    /**
     *
     * Generates a wildcard replacement in the generated path.
     *
     * @param string $path The generated path.
     *
     * @param array $data Data for the token replacements.
     *
     * @return string
     *
     */
    protected function generateWildcardReplacement($path, $data, $raw)
    {
        $wildcard = $this->route->wildcard;
        if ($wildcard && isset($data[$wildcard])) {
            $path = rtrim($path, '/');
            foreach ($data[$wildcard] as $val) {
                // encode the wildcard value
                if (is_scalar($val)) {
                    $path .= '/' . $this->encode($wildcard, $val, $raw);
                }
            }
        }
        return $path;
    }

    protected function encode($key, $val, $raw)
    {
        if (in_array($key, $raw)) {
            return $val;
        }
        return rawurlencode($val);
    }
}
