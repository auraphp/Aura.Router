<?php
namespace Aura\Router;
require_once dirname(__DIR__) . '/src.php';
return new Map(new DefinitionFactory, new RouteFactory);
