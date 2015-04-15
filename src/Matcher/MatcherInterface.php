<?php
namespace Aura\Router\Matcher;

use Aura\Router\Route;
use Psr\Http\Message\ServerRequestInterface;

interface MatcherInterface
{
    public function __invoke(ServerRequestInterface $request, Route $route);
}
