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

Writing a custom matching rule, say one that checks the authentication state, is a bit involved but not difficult:

1. Write a rule class that implements the RuleInterface.

2. Get the RuleIterator from the RouterContainer and append or prepend an instance of your new Rule. (You can also wrap it in a callable to make it lazy-loaded, such as with Aura.Di Lazy instances.)

3. Use the Matcher as normal.

4. If you want your rule to appear in the middle of the matching process, you'll need to set all the rules at once.


## Extending the Map and Route Classes

TBD

## Customizing The Container

TBD
