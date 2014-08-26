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
    protected $path;
    protected $data;
    protected $repl;
    protected $raw;

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
        $this->data = $data;
        $this->raw = $raw;
        $this->path = $this->route->path;
        $this->repl = array();

        $this->generateData();
        $this->generateTokenReplacements();
        $this->generateOptionalReplacements();
        $this->path = strtr($this->path, $this->repl);
        $this->generateWildcardReplacement();

        return $this->path;
    }

    /**
     *
     * Generates the data for token replacements.
     *
     * @return array
     *
     */
    protected function generateData()
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
     * Generates urlencoded data for token replacements.
     *
     * @return array
     *
     */
    protected function generateTokenReplacements()
    {
        foreach ($this->data as $key => $val) {
            if (is_scalar($val) || $val === null) {
                $this->repl["{{$key}}"] = $this->encode($key, $val);
            }
        }
    }

    /**
     *
     * Generates replacements for params in the generated path.
     *
     * @return string
     *
     */
    protected function generateOptionalReplacements()
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
            if (is_scalar($this->data[$name])) {
                $this->repl[$key] .= '/' . $this->encode($name, $this->data[$name]);
            }
        }
    }

    /**
     *
     * Generates a wildcard replacement in the generated path.
     *
     * @return string
     *
     */
    protected function generateWildcardReplacement()
    {
        $wildcard = $this->route->wildcard;
        if ($wildcard && isset($this->data[$wildcard])) {
            $this->path = rtrim($this->path, '/');
            foreach ($this->data[$wildcard] as $val) {
                // encode the wildcard value
                if (is_scalar($val)) {
                    $this->path .= '/' . $this->encode($wildcard, $val);
                }
            }
        }
    }

    protected function encode($key, $val)
    {
        $encode = ! in_array($key, $this->raw)
               && (is_scalar($val) || $val === null);

        return ($encode) ? rawurlencode($val) : $val;
    }
}
