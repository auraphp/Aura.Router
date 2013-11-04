<?php
namespace Aura\Router;
require_once dirname(__DIR__) . '/src.php';
return new Router(new DefinitionFactory, new RouteFactory);
