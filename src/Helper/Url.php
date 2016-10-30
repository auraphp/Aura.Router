<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/mit-license.php MIT
 *
 */
namespace Aura\Router\Helper;

use Aura\Router\Exception\RouteNotFound;
use Aura\Router\Generator;

/**
 *
 * Generic Url Helper class
 *
 * @package Aura.Router
 *
 */
class Url
{
    /**
     *
     * The Generator object used by the RouteContainer
     *
     * @var Generator
     *
     */
    protected $generator;

    /**
     *
     * Constructor.
     *
     * @param Generator $generator The generator object to use
     *
     */
    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    /**
     *
     * Returns the Generator
     *
     * @param string $name The name of the route to lookup.
     *
     * @param array $data The data to pass into the route.
     *
     * @param array | string $queryData The data that is used to create query string.
     *
     * @param string $fragment The data to pass into the route.
     *
     * @param bool $returnRawUrl Whether or not to return the raw url.
     *
     * @return string The results of calling the appropriate _Generator_ method .
     *
     * @throws RouteNotFound When the route cannot be found.
     *
     */
    public function __invoke($name, array $data = [], $queryData = [], $fragment = '', $returnRawUrl = false)
    {
        $url = $returnRawUrl
            ? $this->generator->generateRaw($name, $data)
            : $this->generator->generate($name, $data);

        if (! empty($queryData)) {
            if (is_array($queryData)) {
                $url .= '?' . http_build_query($queryData);
            }

            if (is_string($queryData)) {
                $url .= '?' . ltrim($queryData, "?");
            }
        }

        if (is_string($fragment) && ! empty($fragment)) {
            $url .= '#' . ltrim($fragment, "#");
        }

        return $url;
    }
}
