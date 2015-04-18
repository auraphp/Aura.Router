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

## Extending the Map and Route Classes

TBD

## Custom Matching Rules

TBD
