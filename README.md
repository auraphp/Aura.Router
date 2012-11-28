Aura Router
===========

Aura Router is a PHP package that implements web routing. Given a URI path and
a copy of `$_SERVER`, it will extract controller, action, and parameter values
for a specific application route.

Your application foundation or framework is expected to take the information
provided by the matching route and dispatch to a controller on its own. As
long as your system can provide a URI path string and a representative copy of
`$_SERVER`, you can use Aura Router.

Aura Router is inspired by [Solar rewrite
rules](http://solarphp.com/manual/dispatch-cycle.rewrite-rules) and
<http://routes.groovie.org>.

This package is compliant with [PSR-0][], [PSR-1][], and [PSR-2][]. If you
notice compliance oversights, please send a patch via pull request.

[PSR-0]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md

Basic Usage
===========

Instantiation
-------------

The easiest way to instantiate a router is to include the
`scripts/instance.php` file:

```php
<?php
$router_map = include '/path/to/Aura.Router/scripts/instance.php';
```

Alternatively, you can add Aura Router to your autoloader and instantiate it
manually:

```php
<?php
use Aura\Router\Map;
use Aura\Router\DefinitionFactory;
use Aura\Router\RouteFactory;

$router_map = new Map(new DefinitionFactory, new RouteFactory);
```

Adding A Route
---------------

To create a route, call the `add()` method.

```php
<?php
// create the router map object
$router_map = require '/path/to/Aura.Router/scripts/instance.php';

// add a simple named route without params
$router_map->add('home', '/');

// add a simple unnamed route with params
$router_map->add(null, '/{:controller}/{:action}/{:id:(\d+)}');

// add a complex named route
$router_map->add('read', '/blog/read/{:id}{:format}', [
    'params' => [
        'id'     => '(\d+)',
        'format' => '(\..+)?',
    ],
    'values' => [
        'controller' => 'blog',
        'action'     => 'read',
        'format'     => 'html',
    ],
));
```

You will need to place the router object where you can get to it from your
application; e.g., in a registry, a service locator, or a dependency injection
container. One such system is the [Aura DI](https://github.com/auraphp/Aura.Di)
package.


Matching A Route
----------------

To match a URI path against your routes, call `match()` with a path string
and the `$_SERVER` values.

```php
<?php
// get the incoming request URI path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// get the route based on the path and server
$route = $router_map->match($path, $_SERVER);
```

The `match()` method does not parse the URI or use `$_SERVER` internally. This
is because different systems may have different ways of representing that
information; e.g., through a URI object or a context object. As long as you
can pass the string path and a server array, you can use Aura Router in your
application foundation or framework.

The returned `$route` object will contain, among other things, a `$values`
array with values for each of the parameters identified by the route path. For
example, matching a route with the path `/{:controller}/{:action}/{:id}` will
populate the `$route->values` array with `controller`, `action`, and `id`
keys.


Dispatching A Route
-------------------

Now that you have route, you can dispatch it. The following is what a
foundation or framework system might do with a route to invoke a page
controller.

```php
<?php
if (! $route) {
    // no route object was returned
    echo "No application route was found for that URI path.";
    exit();
}

// does the route indicate a controller?
if (isset($route->values['controller'])) {
    // take the controller class directly from the route
    $controller = $route->values['controller'];
} else {
    // use a default controller
    $controller = 'Index';
}

// does the route indicate an action?
if (isset($route->values['action'])) {
    // take the action method directly from the route
    $action = $route->values['action'];
} else {
    // use a default action
    $action = 'index';
}

// instantiate the controller class
$page = new $controller();

// invoke the action method with the route values
echo $page->$action($route->values);
```

Again, note that Aura Router will not dispatch for you; the above is provided
as a naive example only to show how to use route values.


Generating A Route Path
-----------------------

To generate a URI path from a route so that you can create links, call
`generate()` on the router object and provide the route name.

```php
<?php
// $path => "/blog/read/42.atom"
$path = $router_map->generate('read', [
    'id' => 42,
    'format' => '.atom',
]);

$href = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
echo '<a href="$href">Atom feed for this blog entry</a>';
```

Aura Router does not do dynamic matching of routes; a route must have a name
to be able to generate a path from it.

The example shows that passing an array of data as the second parameter will
cause that data to be interpolated into the route path. This data array is
optional. If there are path params without matching data keys, those params
will *not* be replaced, leaving the `{:param}` token in the path. If there are
data keys without matching params, those values will not be added to the path.


Advanced Usage
==============

Complex Route Specification
---------------------------

When you add a complex route specification, you describe extra information
related to the path as an array with one or more of the following recognized
keys:

- `params` -- The regular expression subpatterns for path params; inline params will override these settings. For example:
        
        'params' => [
            'id' => '(\d+)',
        ]
        
  Note that the path itself is allowed to contain param tokens with inline regular expressions; e.g., `/read/{:id:(\d+)}`.  This may be easier to read in some cases.

- `values` -- The default values for the route. These will be overwritten by matching params from the path.

        'values' => [
            'controller' => 'blog',
            'action' => 'read',
            'id' => 1,
        ]
        
- `method` -- The `$server['REQUEST_METHOD']` must match one of these values.

- `secure` -- When `true` the `$server['HTTPS']` value must be on, or the request must be on port 443; when `false`, neither of those must be in place.

- `routable` -- When `false` the route will not be used for matching, only for generating paths.

- `is_match` -- A custom callback or closure with the signature `function(array $server, \ArrayObject $matches)` that returns true on a match, or false if not. This allows developers to build any kind of matching logic for the route, and to change the `$matches` for param values from the path.

- `generate` -- A custom callback or closure with the signature `function(\aura\router\Route $route, array $data)` that returns a modified `$data` array to be used when generating the path.

Here is a full route specification named `read` with all keys in place:

```php
<?php
$router_map->add('read', '/blog/read/{:id}{:format}', [
    'params' => [
        'id' => '(\d+)',
        'format' => '(\..+)?',
    ],
    'values' => [
        'controller' => 'blog',
        'action' => 'read',
        'id' => 1,
        'format' => '.html',
    ],
    'secure' => false,
    'method' => ['GET'],
    'routable' => true,
    'is_match' => function(array $server, \ArrayObject $matches) {
            
        // disallow matching if referred from example.com
        if ($server['HTTP_REFERER'] == 'http://example.com') {
            return false;
        }
        
        // add the referer from $server to the match values
        $matches['referer'] = $server['HTTP_REFERER'];
        return true;
        
    },
    'generate' => function(\Aura\Router\Route $route, array $data) {
        $data['foo'] = 'bar';
        return $data;
    }
]);
```

Note that using closures, instead of callbacks, means you will not be able to
`serialize()` or `var_export()` the router for caching.


Simple Routes
-------------

You don't need to specify a complex route specification. If you pass a string
for the route instead of an array ...

```php
<?php
$router_map->add('archive', '/archive/{:year}/{:month}/{:day}');
```

... then Aura Router will use a default subpattern that matches everything
except slashes for the path params, and use the route name as the default
value for `'action'`. Thus, the above short-form route is equivalent to the
following long-form route:

```php
<?php
$router_map->add('archive', '/archive/{:year}/{:month}/{:day}', [
    'params' => [
        'year'  => '([^/]+)',
        'month' => '([^/]+)',
        'day'   => '([^/]+)',
    ],
    'values' => [
        'action' => 'archive',
    ],
]);
```

Wildcard Routes
---------------

Sometimes it is useful to allow the trailing part of the path be anything at
all, with no named parameters. You can set up a "wildcard" route of this type
by adding `/*` to the end of the route. This will allow the route to match
anything at all after that point; on a match, it will retain the wildcard
values in a sequential array keyed on `'*'`.


```php
<?php
$router_map->add('wild_post', '/post/{:id}/*');
$route = $router_map->match('/post/88/foo/bar/baz');

// $route->values['id'] = 88;
// $route->values['*'] = ['foo', 'bar', 'baz'];
```

Wildcard routing of this sort works only at the end of the path.


Attaching Route Groups
----------------------

You can add a series of routes all at once under a single "mount point" in
your application. For example, if you want all your blog-related routes to be
mounted at `'/blog'` in your application, you can do this:

```php
<?php
$router_map->attach('/blog', [
    
    // the routes to attach
    'routes' => [
        
        // a short-form route named 'browse'
        'browse' => '/',
        
        // a long-form route named 'read'
        'read' => [
            'path' => '/{:id}{:format}',
            'params' => [
                'id'     => '(\d+)',
                'format' => '(\.json|\.atom)?'
            ],
            'values' => [
                'format' => '.html',
            ],
        ],
        
        // a short-form route named 'edit'
        'edit' => '/{:id:(\d+)}/edit',
    ],
));
```
    
Each of the route paths will be prefixed with `/blog`, so the effective paths
become:

- `browse: /blog/`
- `read:   /blog/{:id}{:format}`
- `edit:   /blog/{:id}/edit`

You can set other route specification keys as part of the attachment
specification; these will be used as the defaults for each attached route, so
you don't need to repeat common information:

```php
<?php
$router_map->attach('/blog', [
    
    // common params for the routes
    'params' => [
        'id'     => '(\d+)',
        'format' => '(\.json|\.atom)?',
    ],
    
    // common values for the routes
    'values' => [
        'controller' => 'blog',
        'format'     => '.html',
    ],
    
    // the routes to attach
    'routes' => [
        'browse' => '/',
        'read'   => '/{:id}{:format}',
        'edit'   => '/{:id}/edit',
    ],
));
```


Constructor-Time Attachment
---------------------------

You can configure your routes in a single array of attachment groups, and then
pass them to the router constructor all at once. This allows you to
separate configuration and construction of routes.

Note that you can specify a `name_prefix` as part of the common route
information for each attached route group; the route names in that group will
be prefixed with that value. This helps with deconfliction of routes with the
same names in different groups.

```php
<?php
$attach = [
    // attach to /blog
    '/blog' => [
        
        // prefix for route names
        'name_prefix' => 'projectname.blog.',
        
        // common params for the routes
        'params' => [
            'id' => '(\d+)',
            'format' => '(\.json|\.atom)?',
        ],
    
        // common values for the routes
        'values' => [
            'controller' => 'blog',
            'format' => '.html',
        ],
    
        // the routes to attach
        'routes' => [
            'browse' => '/',
            'read' => 'path' => '/{:id}{:format}',
            'edit' => '/{:id}/edit',
        ],
    ],
    
    // attach to '/forum'
    '/forum' => [
        // prefix for route names
        'name_prefix' => 'projectname.forum.',
        // ...
    ],

    // attach to '/wiki'
    '/wiki' => [
        // prefix for route names
        'name_prefix' => 'projectname.wiki.',
        // ...
    ],
];

// create the route factory
$route_factory = new \Aura\Router\RouteFactory;

// create a router map with attached route groups
$router_map = new \Aura\Router\Map($route_factory, $attach);
```

This technique can be very effective with modular application packages. Each
package can return an array for its own route group specification, and a
system-specific configuration mechanism can collect each spec into a common
array for the router. For example:

```php
<?php
// get a routes array from each application packages
$attach = [
    '/blog'  => require 'projectname/blog/routes.php',
    '/forum' => require 'projectname/forum/routes.php',
    '/wiki'  => require 'projectname/wiki/routes.php',
];

// create the route factory
$route_factory = new \Aura\Router\RouteFactory;

// create a router map with attached route groups
$router_map = new \Aura\Router\Map($route_factory, $attach);
```


Caching
-------

You may wish to cache the router map for production deployments so that the
router does not have to build the route objects from definitions on each page
load. The methods `getRoutes()` and `setRoutes()` may be used for that
purpose.

The following is a naive example for file-based caching and restoring of
routes:

```php
<?php
// create a router map object
$router_map = require '/path/to/Aura.Router/instance.php';

// the cache file location
$cache = '/path/to/routes.cache';

// does the cache exist?
if (file_exists($cache)) {
    
    // restore from the cache
    $routes = unserialize(file_get_contents($cache));
    $router_map->setRoutes($routes);
    
} else {
    
    // build the routes using add() and attach() ...
    // ... ... ...
    // ... then save to the cache for the next page load
    $routes = $router_map->getRoutes();
    file_put_contents($cache, serialize($routes));
    
}
```

Note that if there are closures in the route definitions, you will not be able
to cache the routes; this is because closures cannot be represented
properly for caching. Use traditional callbacks instead of closures if you
wish to pursue a cache strategy.
