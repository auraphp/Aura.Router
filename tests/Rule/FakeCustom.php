<?php
namespace Aura\Router\Rule;

use Aura\Router\Route;
use Psr\Http\Message\ServerRequestInterface;

class FakeCustom
{
    public function __invoke(ServerRequestInterface $request, Route $route)
    {
        $pass = isset($route->custom['aura/router:fake'])
             && $route->custom['aura/router:fake'];

        if ($pass) {
            $route->addAttributes(['aura/router:fake' => 'fake']);
        }

        return $pass;
    }
}
