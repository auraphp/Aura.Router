<?php
namespace Aura\Router\Rule;

use Aura\Router\Route;
use Psr\Http\Message\ServerRequestInterface;

interface RuleInterface
{
    public function __invoke(ServerRequestInterface $request, Route $route);
}
