# Aura.Router

Provides a web router implementation: given a URL path and a copy of
`$_SERVER`, it will extract path-info and `$_SERVER` values for a specific
route.

This package does not provide a dispatching mechanism. Your application is
expected to take the information provided by the matching route and dispatch
to a controller on its own. For one possible dispatch system, please see
[Aura.Dispatcher][].
  
## Foreword

### Requirements

This library requires PHP 5.3 or later, and has no userland dependencies.

### Installation

This library is installable and autoloadable via Composer with the following
`require` element in your `composer.json` file:

    "require": {
        "aura/router": "dev-develop-2"
    }
    
Alternatively, download or clone this repository, then require or include its
_autoload.php_ file.

### Tests

[![Build Status](https://travis-ci.org/auraphp/Aura.Router.png?branch=develop-2)](https://travis-ci.org/auraphp/Aura.Autoload)

This library has 100% code coverage with [PHPUnit][]. To run the tests at the
command line, go to the _tests_ directory and issue `phpunit`.

[PHPUnit]: http://phpunit.de/manual/

### PSR Compliance

This library attempts to comply with [PSR-1][], [PSR-2][], and [PSR-4][]. If
you notice compliance oversights, please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md


## Getting Started

### Instantiation

Instantiate a _Router_ like so:

```php
<?php
use Aura\Router\Router;
use Aura\Router\RouteFactory;

$router = new Router(new RouteFactory);
?>
```

You will need to place the _Router_ where you can get to it from your
application; e.g., in a registry, a service locator, or a dependency injection
container. One such system is the [Aura.Di](https://github.com/auraphp/Aura.Di)
package.


### Adding A Route

To create a route, call the `add()` method on the _Router_. Named path-info
params are placed inside braces in the path.

```php
<?php
// add a simple named route without params
$router->add('home', '/');

// add a simple unnamed route with params
$router->add(null, '/{controller}/{action}/{id}');

// add a complex named route
$router->add('read', '/blog/read/{id}{format}', array(
    'require' => array(
        'id'     => '\d+',
        'format' => '(\.[^/]+)?',
    ),
    'default' => array(
        'controller' => 'blog',
        'action'     => 'read',
        'format'     => '.html',
    ),
));
?>
```

### Matching A Route

To match a URL path against your routes, call `match()` with a path string
and the `$_SERVER` values.

```php
<?php
// get the incoming request URL path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// get the route based on the path and server
$route = $router->match($path, $_SERVER);
?>
```

The `match()` method does not parse the URL or use `$_SERVER` internally. This
is because different systems may have different ways of representing that
information; e.g., through a URL object or a context object. As long as you
can pass the string path and a server array, you can use the _Router_ in your
application foundation or framework.

The returned `$route` object will contain, among other things, a `$params`
array with values for each of the parameters identified by the route path. For
example, matching a route with the path `/{controller}/{action}/{id}` will
populate the `$route->params` array with `controller`, `action`, and `id`
keys.

### Dispatching A Route

Now that you have route, you can dispatch it. The following is what a
foundation or framework system might do with a route to invoke a page
controller.

```php
<?php
if (! $route) {
    // no route object was returned
    echo "No application route was found for that URL path.";
    exit();
}

// does the route indicate a controller?
if (isset($route->params['controller'])) {
    // take the controller class directly from the route
    $controller = $route->params['controller'];
} else {
    // use a default controller
    $controller = 'Index';
}

// does the route indicate an action?
if (isset($route->params['action'])) {
    // take the action method directly from the route
    $action = $route->params['action'];
} else {
    // use a default action
    $action = 'index';
}

// instantiate the controller class
$page = new $controller();

// invoke the action method with the route values
echo $page->$action($route->params);
?>
```

Again, note that the _Router_ will not dispatch for you; the above is provided
as a naive example only to show how to use route values.  For a more complex
dispatching system, try [Aura.Dispatcher][].


### Generating A Route Path

To generate a URL path from a route so that you can create links, call
`generate()` on the _Router_ and provide the route name with optional data.

```php
<?php
// $path => "/blog/read/42.atom"
$path = $router->generate('read', array(
    'id' => 42,
    'format' => '.atom',
));

$href = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
echo '<a href="$href">Atom feed for this blog entry</a>';
?>
```

The _Router_ does not do dynamic matching of routes; a route must have a name
to be able to generate a path from it.

The example shows that passing an array of data as the second parameter will
cause that data to be interpolated into the route path. This data array is
optional. If there are path params without matching data keys, those params
will *not* be replaced, leaving the `{param}` token in the path. If there are
data keys without matching params, those values will not be added to the path.

## Advanced Usage

### As a Micro-Framework

Sometimes you may wish to use the _Router_ as a micro-framework. This is 
possible by assigning a `callable` as a default param value, then calling that
param to dispatch it.

```php
<?php
$router->add('read', '/blog/read/{id}{format}', array(
	'require' => array(
		'id' => '\d+',
        'format' => '(\.[^/]+)?',
	),
	'default' => array(
		'controller' => function ($params) {
		    if ($params['format'] == '.json') {
    			$id = (int) $params['id'];
		        header('Content-Type: application/json');
		        echo json_encode(['id' => $id]);
		    } else {
    			$id = (int) $params['id'];
		        header('Content-Type: text/plain');
    			echo "Reading blog ID {$id}";
		    }
		},
		'format' => '.html',
	),
));
?>
```

A naive micro-framework dispatcher might work like this:

```php
<?php
// get the route params
$params = $route->params;

// extract the controller callable from the params
$controller = $params['controller'];
unset($params['controller']);

// invoke the callable
$controller($params);
?>
```

With the above example controller, the URL `/blog/read/1.json` will send JSON
ouput, but for `/blog/read/1` it will send plain text output.

### Complex Route Specification

When you add a complex route specification, you describe extra information
related to the path as an array with one or more of the following recognized
keys:

- `require` -- The regular expression subpatterns that params must match;
  these include `$_SERVER` values. For example:
        
        'require' => array(
            'id' => '\d+',
            'REQUEST_METHOD' => 'GET|POST',
        ]
        
- `default` -- The default values for the params. These will be overwritten by
  matching params.

        'default' => array(
            'controller' => 'blog',
            'action' => 'read',
            'id' => 1,
        ]
        
- `secure` -- When `true` the `$server['HTTPS']` value must be on, or the
  request must be on port 443; when `false`, neither of those must be in
  place.

- `routable` -- When `false` the route will be used only for generating paths,
  not for matching.

- `is_match` -- A custom callback or closure with the signature
  `function(array $server, \ArrayObject $matches)` that returns true on a
  match, or false if not. This allows developers to build any kind of matching
  logic for the route, and to change the `$matches` for param values from the
  path.

- `generate` -- A custom callback or closure with the signature
  `function(\Aura\Router\Route $route, array $data)` that returns a modified
  `$data` array to be used when generating the path.

Here is a full route specification named `read` with all keys in place:

```php
<?php
$router->add('read', '/blog/read/{id}{format}', array(
    'require' => array(
        'id' => '\d+',
        'format' => '(\.[^/]+)?',
        'REQUEST_METHOD' => 'GET|POST',
    ),
    'default' => array(
        'controller' => 'blog',
        'action' => 'read',
        'id' => 1,
        'format' => '.html',
    ),
    'secure' => false,
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
));
?>
```

Note that using closures, instead of callbacks, means you will not be able to
`serialize()` or `var_export()` the router for caching.


### Simple Routes

You don't need to specify a complex route specification. If you omit the final
array parameter ...

```php
<?php
$router->add('archive', '/archive/{year}/{month}/{day}');
?>
```

... then the _Router_ will use a default subpattern that matches everything
except slashes for the path params, and use the route name as the default
value for `action`. Thus, the above short-form route is equivalent to the
following long-form route:

```php
<?php
$router->add('archive', '/archive/{year}/{month}/{day}', array(
    'require' => array(
        'year'  => '[^/]+',
        'month' => '[^/]+',
        'day'   => '[^/]+',
    ),
    'default' => array(
        'action' => 'archive',
    ),
));
?>
```

### Optional Params

Sometimes it is useful to have a route with optional named params; that is,
none, some, or all of the optional params may be present, and the route will
still match.

To specify optional params, use the notation `{/param1,param2,param3}` in the
path. For example:

```php
<?php
$router->add('archive', '/archive{/year,month,day}', array(
    'require' => array(
        'year'  => '\d{4}',
        'month' => '\d{2}',
        'day'   => '\d{2}'
    ),
));
?>
```

> N.b.: Note that the leading slash separator is inside the params token, not
> outside it.

With that, the following routes will all match the 'archive' route, and will
set the appropriate values:

    /archive
    /archive/1979
    /archive/1979/11
    /archive/1979/11/07

Optional params are "sequentially" optional. This means that, in the above
example, you cannot have a "day" without a "month", and you cannot have a
"month" without a "year".

Only one set of optional params per path is recognized by the _Router_.

Optional params belong at the end of a route path; placing them elsewhere may
result in unexpected behavior.

If you `generate()` a link with optional params, the params will be filled in
if they are present in the data for the link. Remember, the optional params
are *sequentially* optional, so the params will not be filled in after the
first missing one:

```php
<?php
$router->add('archive', '/archive{/year,month,day}', array(
    'require' => array(
        'year'  => '\d{4}',
        'month' => '\d{2}',
        'day'   => '\d{2}'
    ),
));

$link = $router->generate('archive', array(
    'year' => '1979',
    'month' => '11',
)); // "/archive/1979/11"
?>
```

### Wildcard Params

Sometimes it is useful to allow the trailing part of the path be anything at
all. To specify that a route allows arbitrary trailing portions, pass a
'wildcard' key in the route definition; that key will be the param name under
which the arbitrary trailing param values will be placed in the route values.

```php
<?php
$router->add('wild_post', '/post/{id}', array(
    'wildcard' => 'other',
));

// this matches, with the following values
$route = $router->match('/post/88/foo/bar/baz', $_SERVER);
// $route->params['id'] = 88;
// $route->params['other'] = array('foo', 'bar', 'baz')

// this also matches, with the following values; note the trailing slash
$route = $router->match('/post/88/', $_SERVER);
// $route->params['id'] = 88;
// $route->params['other'] = array();

// this also matches, with the following values; note the missing slash
$route = $router->match('/post/88', $_SERVER);
// $route->params['id'] = 88;
// $route->params['other'] = array();
?>
```

If you `generate()` a link with wildcard params, the wildcard key in the data
will be used for the trailing arbitrary param values:

```php
<?php
$router->add('wild_post', '/post/{id}', array(
    'wildcard' => 'other',
));

$link = $router->generate('wild_post', array(
    'id' => '88',
    'other' => array(
        'foo',
        'bar',
        'baz',
    );
)); // "/post/88/foo/bar/baz"
?>
```

### Attaching Route Groups

You can add a series of routes all at once under a single "mount point" in
your application. For example, if you want all your blog-related routes to be
mounted at `'/blog'` in your application, you can do this:

```php
<?php
$router->attach('/blog', array(
    
    // the routes to attach
    'routes' => array(
        
        // a short-form route named 'browse'
        'browse' => '/',
        
        // a long-form route named 'read'
        'read' => array(
            'path' => '/{id}{format}',
            'require' => array(
                'id'     => '\d+',
                'format' => '(\.json|\.atom)?'
            ),
            'default' => array(
                'format' => '.html',
            ),
        ),
        
        // a short-form route named 'edit'
        'edit' => '/{id}/edit',
    ),
));
?>
```
    
Each of the route paths will be prefixed with `/blog`, so the effective paths
become:

- `browse: /blog/`
- `read:   /blog/{id}{format}`
- `edit:   /blog/{id}/edit`

You can set other route specification keys as part of the attachment
specification; these will be used as the defaults for each attached route, so
you don't need to repeat common information:

```php
<?php
$router->attach('/blog', array(
    
    // common param requirements for the routes
    'require' => array(
        'id'     => '\d+',
        'format' => '(\.json|\.atom)?',
    ),
    
    // common default param values for the routes
    'default' => array(
        'controller' => 'blog',
        'format'     => '.html',
    ),
    
    // the routes to attach
    'routes' => array(
        'browse' => '/',
        'read'   => '/{id}{format}',
        'edit'   => '/{id}/edit',
    ),
));
?>
```


### Constructor-Time Attachment

You can configure your routes in a single array of attachment groups, and then
pass them to the router constructor all at once. This allows you to
separate configuration and construction of routes.

Note that you can specify a `name_prefix` as part of the common route
information for each attached route group; the route names in that group will
be prefixed with that value. This helps with deconfliction of routes with the
same names in different groups.

```php
<?php
$attach = array(
    // attach to /blog
    '/blog' => array(
        
        // prefix for route names
        'name_prefix' => 'projectname.blog.',
        
        // common param requirements for the routes
        'require' => array(
            'id' => '\d+',
            'format' => '(\.json|\.atom)?',
        ),
    
        // common default param values for the routes
        'default' => array(
            'controller' => 'blog',
            'format' => '.html',
        ),
    
        // the routes to attach
        'routes' => array(
            'browse' => '/',
            'read'   => '/read/{id}{format}',
            'edit' => '/{id}/edit',
        ),
    ),
    
    // attach to '/forum'
    '/forum' => array(
        // prefix for route names
        'name_prefix' => 'projectname.forum.',
        // ...
    ),

    // attach to '/wiki'
    '/wiki' => array(
        // prefix for route names
        'name_prefix' => 'projectname.wiki.',
        // ...
    ),
];

// create the route factory
$route_factory = new \Aura\Router\RouteFactory;

// create a router with attached route groups
$router = new \Aura\Router\Router($route_factory, $attach);
?>
```

This technique can be very effective with modular application packages. Each
package can return an array for its own route group specification, and a
system-specific configuration mechanism can collect each spec into a common
array for the router. For example:

```php
<?php
// get a routes array from each application packages
$attach = array(
    '/blog'  => require 'projectname/blog/routes.php',
    '/forum' => require 'projectname/forum/routes.php',
    '/wiki'  => require 'projectname/wiki/routes.php',
);

// create the route factory
$route_factory = new \Aura\Router\RouteFactory;

// create a router with attached route groups
$router = new \Aura\Router\Router($route_factory, $attach);
?>
```


### Caching

You may wish to cache the router for production deployments so that the
router does not have to build the route objects from definitions on each page
load. The methods `getRoutes()` and `setRoutes()` may be used for that
purpose.

The following is a naive example for file-based caching and restoring of
routes:

```php
<?php
// the cache file location
$cache = '/path/to/routes.cache';

// does the cache exist?
if (file_exists($cache)) {
    
    // restore from the cache
    $routes = unserialize(file_get_contents($cache));
    $router->setRoutes($routes);
    
} else {
    
    // build the routes using add() and attach() ...
    // ... ... ...
    // ... then save to the cache for the next page load
    $routes = $router->getRoutes();
    file_put_contents($cache, serialize($routes));
    
}
?>
```

Note that if there are closures in the route definitions, you will not be able
to cache the routes; this is because closures cannot be represented
properly for caching. Use traditional callbacks instead of closures if you
wish to pursue a cache strategy.

[Aura.Dispatcher]: https://github.com/auraphp/Aura.Dispatcher
