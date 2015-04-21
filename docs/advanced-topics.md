# Advanced Topics

## Catchall Routes

You can use optional placeholder tokens to create a generic catchall route:

```php
<?php
$map->get('catchall', '{/controller,action,id}')
    ->defaults([
        'controller' => 'index',
        'action' => 'browse',
        'id' => null,
    ]);
?>
```

That will match these paths, with these attribute values:

- `/           : ['controller' => 'index', 'action' => 'browse', 'id' => null]`
- `/foo        : ['controller' => 'foo',   'action' => 'browse', 'id' => null]`
- `/foo/bar    : ['controller' => 'foo',   'action' => 'bar',    'id' => null]`
- `/foo/bar/42 : ['controller' => 'foo',   'action' => 'bar',    'id' => '42']`

Because routes are matched in the order they are added, the catchall should be the last route in the _Map_ so that more specific routes may match first.

## Caching Routes

You may wish to cache the _Map_ for production deployments so that you do not have to add the routes from scratch on each page load. The `Map::getRoutes()` and `Map::setRoutes()` methods may be used for that purpose.

The following is a naive example for file-based caching and restoring of _Map_ routes:

```php
<?php
// the cache file location
$cache = '/path/to/routes.cache';

// does the cache exist?
if (file_exists($cache)) {

    // restore from the cache
    $routes = unserialize(file_get_contents($cache));
    $map->setRoutes($routes);

} else {

    // map the routes ...
    $map->get(...);
    $map->post(...);

    // ... then save them to the cache for the next page load
    $routes = $map->getRoutes();
    file_put_contents($cache, serialize($routes));
}
?>
```

Note that if there are closures in the _Route_ objects (e.g. in the handlers), you will not be able to cache the routes. This is because closures cannot be serialized by PHP. Consider using non-closure callables instead.

## Custom Matching Rules

Writing a custom matching rule, say one that checks the authentication state, is a bit involved but not difficult.

1. Write a rule class that implements the RuleInterface.

2. Get the RuleIterator from the RouterContainer and `append()` or `prepend()` your new Rule. (You can also wrap it in a callable to make it lazy-loaded, such as with Aura.Di Lazy instances.)

3. Use the Matcher as normal.

Here is a naive rule that checks to see if the request has a particular header set; the rule passes if it is, and fails if it does not.  The header value is captured into the route attributes.

```php
<?php
use Aura\Router\Route;
use Aura\Router\Rule\RuleInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApiVersionRule implements RuleInterface
{
    public function __invoke(ServerRequestInterface $request, Route $route)
    {
        $versions = $request->getHeader('X-Api-Version');
        if (count($versions) !== 1) {
            return false;
        }

        $route->attributes(['apiVersion' => $versions[0]]);
        return true;
    }
}
?>
```

You can then `prepend()` or `append()` the rule to the _RuleIterator_ from the _RouterContainer_. (Prepended rules get run first, appended ones last.)

```php
<?php
$ruleIterator = $routerContainer->getRuleIterator();
$ruleIterator->append(new ApiVersionRule());
?>
```

Then you can run the _Matcher_ as normal, and your new rule will be honored.

```php
<?php
$matcher = $routerContainer->getMatcher();
$route = $matcher->match($request);
?>
```

## Extending the Map Class

You might want to extend the _Map_ class to provide convenience methods specific to your application. As with writing a custom matching rule, this is a bit involved but not difficult:

1. Write your _Map_ extended class.

2. Add your extended class factory to the _RouterContainer_.

3. Use the _RouterContainer_ as normal, and it will return your custom _Map_ class.

For example, you may wish to have a `resource()` method that automatically attaches a series of routes all at once for a given route name and route path.  First, write your extension of the _Map_ class:

```php
<?php
use Aura\Router\Map;

class MyResourceMap extends Map
{
    public function resource($namePrefix, $pathPrefix)
    {
        return $this->attach($namePrefix, $pathPrefix, function ($map) {
            $map->get('browse', '');
            $map->get('read', '/{id}');
            $map->patch('edit', '/{id}');
            $map->post('add', '');
            $map->delete('delete', '/{id}');
        });
    }
}
?>
```

Then tell the _RouterContainer_ how to create your extended class using a factory callable:

```php
<?php
use Aura\Router\Route;

$routerContainer->setMapFactory(function () {
    return new MyResourceMap(new Route());
});
?>
```

Now you can get the _Map_ from the _RouterContainer_, and it will be your custom extended class:

```php
<?php
$map = $routerContainer->getMap();
echo get_class($map); // "ResourceMap"
?>
```

## Logging

You may wish to log the _Matcher_ activity using a PSR-3 compliant logger. You can tell the _RouterContainer_ how to create a logger instance by passing a factory callable to `setLoggerFactory()`.
```php
<?php
$routerContainer->setLoggerFactory(function () {
    return new Psr3Logger();
});
?>
```

The _RouterContainer_ will use that callable to create the logger and inject it into the _Matcher_. YOu can then review the debug-level logs for _Matcher_ activity.
