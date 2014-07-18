<?php
namespace Aura\Router;

class Regex
{
    protected $route;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     *
     * Sets the regular expression for this Route.
     *
     * @return null
     *
     */
    public function match($path)
    {
        $this->regex = $this->route->path;
        $this->setRegexOptionalParams();
        $this->setRegexParams();
        $this->setRegexWildcard();
        $this->regex = '#^' . $this->regex . '$#';
        return preg_match($this->regex, $path, $this->matches);
    }

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
