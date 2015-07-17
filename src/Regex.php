<?php
/**
 *
 * This file is part of the Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

/**
 *
 * A regular-expression tracker for a Route.
 *
 * @package Aura.Router
 *
 */
class Regex
{
    /**
     *
     * The Route this regex is associated with.
     *
     * @var Route
     *
     */
    protected $route;

    /**
     *
     * The regular expression.
     *
     * @var string
     *
     */
    protected $regex;

    /**
     *
     * Matches from the regex.
     *
     * @var array
     *
     */
    protected $matches;

    /**
     *
     * Does the Route match the requested URL path?
     *
     * @param Route $route The route being checked.
     *
     * @param string $path The requested URL path.
     *
     * @param string $basepath A basepath to prefix to the route path.
     *
     * @return bool
     *
     */
    public function match(Route $route, $path, $basepath = null)
    {
        $this->route = $route;
        $this->regex = $basepath . $this->route->path;
        $this->setRegexOptionalParams();
        $this->setRegexParams();
        $this->setRegexWildcard();
        $this->regex = '#^' . $this->regex . '$#';
        return preg_match($this->regex, $path, $this->matches);
    }

    /**
     *
     * Returns the matches.
     *
     * @return array
     *
     */
    public function getMatches()
    {
        return $this->matches;
    }

    /**
     *
     * Expands optional params in the regex from ``{/foo,bar,baz}` to
     * `(/{foo}(/{bar}(/{baz})?)?)?`.
     *
     * @return null
     *
     */
    protected function setRegexOptionalParams()
    {
        preg_match('#{/([a-z][a-zA-Z0-9_,]*)}#', $this->regex, $matches);
        if ($matches) {
            $repl = $this->getRegexOptionalParamsReplacement($matches[1]);
            $this->regex = str_replace($matches[0], $repl, $this->regex);
        }
    }

    /**
     *
     * Gets the replacement for optional params in the regex.
     *
     * @param array $list The optional params.
     *
     * @return string
     *
     */
    protected function getRegexOptionalParamsReplacement($list)
    {
        $list = explode(',', $list);
        $head = $this->getRegexOptionalParamsReplacementHead($list);
        $tail = '';
        foreach ($list as $name) {
            $head .= "(/{{$name}}";
            $tail .= ')?';
        }

        return $head . $tail;
    }

    /**
     *
     * Gets the leading portion of the optional params replacement.
     *
     * @param array $list The optional params.
     *
     * @return string
     *
     */
    protected function getRegexOptionalParamsReplacementHead(&$list)
    {
        // if the optional set is the first part of the path, make sure there
        // is a leading slash in the replacement before the optional param.
        $head = '';
        if (substr($this->regex, 0, 2) == '{/') {
            $name = array_shift($list);
            $head = "/({{$name}})?";
        }
        return $head;
    }

    /**
     *
     * Expands param names in the regex to named subpatterns.
     *
     * @return null
     *
     */
    protected function setRegexParams()
    {
        $find = '#{([a-z][a-zA-Z0-9_]*)}#';
        preg_match_all($find, $this->regex, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $name = $match[1];
            $subpattern = $this->getSubpattern($name);
            $this->regex = str_replace("{{$name}}", $subpattern, $this->regex);
            if (! isset($this->route->values[$name])) {
                $this->route->addValues(array($name => null));
            }
        }
    }

    /**
     *
     * Returns a named subpattern for a param name.
     *
     * @param string $name The param name.
     *
     * @return string The named subpattern.
     *
     */
    protected function getSubpattern($name)
    {
        // is there a custom subpattern for the name?
        if (isset($this->route->tokens[$name])) {
            return "(?P<{$name}>{$this->route->tokens[$name]})";
        }

        // use a default subpattern
        return "(?P<{$name}>[^/]+)";
    }

    /**
     *
     * Adds a wildcard subpattern to the end of the regex.
     *
     * @return null
     *
     */
    protected function setRegexWildcard()
    {
        if (! $this->route->wildcard) {
            return;
        }

        $this->regex = rtrim($this->regex, '/')
                     . "(/(?P<{$this->route->wildcard}>.*))?";
    }
}
