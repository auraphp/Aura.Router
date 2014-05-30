<?php
namespace Aura\Router;

use ArrayObject;

class Generator
{
    /**
     *
     * Gets the path for this Route with data replacements for param tokens.
     *
     * @param array $data An array of key-value pairs to interpolate into the
     * param tokens in the path for this Route. Keys that do not map to
     * params are discarded; param tokens that have no mapped key are left in
     * place.
     *
     * @return string
     *
     */
    public function generate(Route $route, $data = array())
    {
        $link = $route->path;
        $data = $this->generateData($route, $data);
        $repl = $this->generateTokenReplacements($data);
        $repl = $this->generateParamReplacements($link, $repl, $data);
        $link = strtr($link, $repl);
        $link = $this->generateWildcard($route, $link, $data);
        return $link;
    }

    protected function generateData(Route $route, array $data)
    {
        // the data for replacements
        $data = array_merge($route->values, $data);

        // use a callable to modify the data?
        if ($route->generate) {
            // pass the data as an object, not as an array, so we can avoid
            // tricky hacks for references
            $arrobj = new ArrayObject($data);
            // modify
            call_user_func($route->generate, $arrobj);
            // convert back to array
            $data = $arrobj->getArrayCopy();
        }

        return $data;
    }

    protected function generateTokenReplacements($data)
    {
        $repl = array();
        foreach ($data as $key => $val) {
            if (is_scalar($val) || $val === null) {
                $repl["{{$key}}"] = rawurlencode($val);
            }
        }
        return $repl;
    }

    protected function generateParamReplacements($link, $repl, $data)
    {
        // replacements for optional params, if any
        preg_match('#{/([a-z][a-zA-Z0-9_,]*)}#', $link, $matches);
        if (! $matches) {
            return $repl;
        }

        // this is the full token to replace in the link
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
                $repl[$key] .= '/' . rawurlencode($data[$name]);
            }
        }
        return $repl;
    }

    protected function generateWildcard(Route $route, $link, $data)
    {
        $wildcard = $route->wildcard;
        if ($wildcard && isset($data[$wildcard])) {
            $link = rtrim($link, '/');
            foreach ($data[$wildcard] as $val) {
                // encode the wildcard value
                if (is_scalar($val)) {
                    $link .= '/' . rawurlencode($val);
                }
            }
        }
        return $link;
    }
}
