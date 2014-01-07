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
        "aura/router": "2.*@dev"
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
use Aura\Router\RouterFactory;

$router_factory = new RouterFactory;
$router = $router_factory->newInstance();
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

// add a named route with an extended specification
$router->add('read', '/blog/read/{id}{format}')
    ->addTokens(array(
        'id'     => '\d+',
        'format' => '(\.[^/]+)?',
    ))
    ->addValues(array(
        'controller' => 'blog',
        'action'     => 'read',
        'format'     => '.html',
    ));
?>
```

You can create a route that matches only against a particular HTTP method
as well. The following _Router_ methods are identical to `add()` but require
the related HTTP method:

- `$router->addGet()`
- `$router->addDelete()`
- `$router->addOption()`
- `$router->addPatch()`
- `$router->addPost()`
- `$router->addPut()`

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

### Extended Route Specification

You can extend a route specification with the following methods:

- `addTokens()` -- Adds regular expression subpatterns that params must
  match.
        
        addTokens(array(
            'id' => '\d+',
        ))
    
    Note that `setTokens()` is also available, but this will replace any
    previous subpatterns entirely, instead of merging with the existing
    subpatterns.
        
- `addServer()` -- Adds regular expressions that server values must
  match.
        
        addServer(array(
            'REQUEST_METHOD' => 'PUT|PATCH',
        ))
    
    Note that `setServer()` is also available, but this will replace any
    previous expressions entirely, instead of merging with the existing
    expressions.
        
- `addValues()` -- Adds default values for the params.

        addValues(array(
            'controller' => 'blog',
            'action' => 'read',
            'id' => 1,
        ))
        
    Note that `setValues()` is also available, but this will replace any
    previous default values entirely, instead of merging with the existing
    default value.

- `setSecure()` -- When `true` the `$server['HTTPS']` value must be on, or the
  request must be on port 443; when `false`, neither of those must be in
  place.

- `setWildcard()` -- Sets the name of a wildcard param; this is where
  arbitrary slash-separated values appearing after the route path will be
  stored.
  
- `setRoutable()` -- When `false` the route will be used only for generating
  paths, not for matching (`true` by default).

- `setIsMatchCallable()` -- A custom callable with the signature
  `function(array $server, \ArrayObject $matches)` that returns true on a
  match, or false if not. This allows developers to build any kind of matching
  logic for the route, and to change the `$matches` for param values from the
  path.

- `setGenerateCallable()` -- A custom callable with the signature
  `function(\ArrayObject $data)`. This allows developers to modify the data
  for path interpolation.

Here is a full extended route specification named `read`:

```php
<?php
$router->add('read', '/blog/read/{id}{format}')
    ->addTokens(array(
        'id' => '\d+',
        'format' => '(\.[^/]+)?',
        'REQUEST_METHOD' => 'GET|POST',
    ))
    ->addValues(array(
        'controller' => 'blog',
        'action' => 'read',
        'id' => 1,
        'format' => '.html',
    ))
    ->setSecure(false)
    ->setRoutable(false)
    ->setIsMatchCallable(function(array $server, \ArrayObject $matches) {
            
        // disallow matching if referred from example.com
        if ($server['HTTP_REFERER'] == 'http://example.com') {
            return false;
        }
        
        // add the referer from $server to the match values
        $matches['referer'] = $server['HTTP_REFERER'];
        return true;
        
    })
    ->setGenerateCallable(function (\ArrayObject $data) {
        $data['foo'] = 'bar';
    });
?>
```

### Default Route Specifications

You can set the default route specifications with the following _Router_
methods; the values will apply to all routes added thereafter.

```php
<?php
// add to the default 'tokens' expressions; setTokens() is also available
$router->addTokens(array(
    'id' => '\d+',
));

// add to the default 'server' expressions; setServer() is also available
$router->addServer(array(
    'REQUEST_METHOD' => 'PUT|PATCH',
));

// add to the default param values; setValues() is also available
$router->addValues(array(
    'format' => null,
));

// set the default 'secure' value 
$router->setSecure(true);

// set the default wildcard param name
$router->setWildcard('other');

// set the default 'routable' flag
$router->setRoutable(false);

// set the default 'isMatch()' callable
$router->setIsMatchCallable(function (...) { ... });

// set the default 'generate()' callable
$router->setGenerateCallable(function (...) { ... });
?>
```
  
### Simple Routes

You don't need to specify an extended route specification. With the following
simple route ...

```php
<?php
$router->add('archive', '/archive/{year}/{month}/{day}');
?>
```

... the _Router_ will use a default subpattern that matches everything except
slashes for the path params. Thus, the above simple route is equivalent to the
following extended route:

```php
<?php
$router->add('archive', '/archive/{year}/{month}/{day}')
    ->addTokens(array(
        'year'  => '[^/]+',
        'month' => '[^/]+',
        'day'   => '[^/]+',
    ));
?>
```

### Automatic Params

The _Router_ will automatically populate values for `controller` and `action`
route params if those params do not already have values. The _Router_ combines
the route name prefix (if one exists) with a dot and the route name; the
portion before the last dot is the `controller` value, and the `action` value
is the portion after the last dot.

```php
<?php
// controller = 'foo' and action = 'bar'
// because they have not been set otherwise
$router->add('foo.bar', '/path/to/bar');

// the 'action' param value on this route will be 'baz'
// because we explicitly set a default in the router;
// 'controller' is still 'foo'
$router->setValues(array('action' => 'baz'));
$route->add('foo.bar', '/path/to/bar');

// the value for the 'action' param on this route will be
// 'zim' because we explicitly set it on the extended route spec
$route->add('foo.dib', '/path/to/dib')
    ->setValues(array('action' => 'zim'));

// the 'action' param here will be whatever the path value for {action} is
$route->add('/path/to/{action}');
?>
```

> N.b. If there are no dots in the combined name, the `controller` value will
> be null and the `action` value will be the name.

### Optional Params

Sometimes it is useful to have a route with optional named params. None, some,
or all of the optional params may be present, and the route will still match.

To specify optional params, use the notation `{/param1,param2,param3}` in the
path. For example:

```php
<?php
$router->add('archive', '/archive{/year,month,day}')
    ->addTokens(array(
        'year'  => '\d{4}',
        'month' => '\d{2}',
        'day'   => '\d{2}'
    ));
?>
```

> N.b.: The leading slash separator is inside the params token, not outside.

With that, the following routes will all match the 'archive' route, and will
set the appropriate values:

    /archive
    /archive/1979
    /archive/1979/11
    /archive/1979/11/07

Optional params are *sequentially* optional. This means that, in the above
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
$router->add('archive', '/archive{/year,month,day}')
    ->addTokens(array(
        'year'  => '\d{4}',
        'month' => '\d{2}',
        'day'   => '\d{2}'
    ));

$link = $router->generate('archive', array(
    'year' => '1979',
    'month' => '11',
)); // "/archive/1979/11"
?>
```

Similarly, optional params can be used as a generic catchall route:

```php
<?php
$router->add('generic', '{/controller,action,id}')
    ->setValues(array(
        'controller' => 'index',
        'action' => 'browse',
        'id' => null,
    );
?>
```

That will match these paths, with these param values:

    /           : 'controller' => 'index', 'action' => 'browse', 'id' => null
    /foo        : 'controller' => 'foo',   'action' => 'browse', 'id' => null
    /foo/bar    : 'controller' => 'foo',   'action' => 'bar',    'id' => null
    /foo/bar/42 : 'controller' => 'foo',   'action' => 'bar',    'id' => '42'


### Wildcard Params

Sometimes it is useful to allow the trailing part of the path be anything at
all. To allow arbitrary trailing params on a route, extend the route
definition with `setWildcard()` to specify param name under which the
arbitrary trailing param values will be stored.

```php
<?php
$router->add('wild_post', '/post/{id}')
    ->setWildcard('other');

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
$router->add('wild_post', '/post/{id}')
    ->setWildcard('other');

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
mounted at `/blog` in your application, you can do this:

```php
<?php
$name_prefix = 'blog';
$path_prefix = '/blog';

$router->attach($name_prefix, $path_prefix, function ($router) {
    
    $router->add('browse', '{format}')
        ->addTokens(array(
            'format' => '(\.json|\.atom|\.html)?'
        ))
        ->addValues(array(
            'format' => '.html',
        ));
    
    $router->add('read', '/{id}{format}', array(
        ->addTokens(array(
            'id'     => '\d+',
            'format' => '(\.json|\.atom|\.html)?'
        )),
        ->addValues(array(
            'format' => '.html',
        ));
    
    $router->add('edit', '/{id}/edit{format}', array(
        ->addTokens(array(
            'id' => '\d+',
            'format' => '(\.json|\.atom|\.html)?'
        ))
        ->addValues(array(
            'format' => '.html',
        ));
});
?>
```
    
Each of the route names will be prefixed with 'blog.', and of the route paths
will be prefixed with `/blog`, so the effective route names and paths become:

- `blog.browse  =>  /blog{format}`
- `blog.read    =>  /blog/{id}{format}`
- `blog.edit    =>  /blog/{id}/edit{format}`

You can set other route specification values as part of the attachment
specification; these will be used as the defaults for each attached route, so
you don't need to repeat common information. (Setting these values will
not affect routes outside the attached group.)

```php
<?php
$name_prefix = 'blog';
$path_prefix = '/blog';

$router->attach($name_prefix, $path_prefix, function ($router) {
    
    $router->setTokens(array(
        'id'     => '\d+',
        'format' => '(\.json|\.atom)?'
    ));
    
    $router->setValues(array(
        'format' => '.html',
    ));
    
    $router->add('browse', '');
    $router->add('read', '/{id}{format}');
    $router->add('edit', '/{id}/edit');
});
?>
```

### Attaching REST Resource Routes

The router can attach a series of REST resource routes for you with the
`attachResource()` method:

```php
<?php
$router->attachResource('blog', '/blog');
?>
```

That method call will result in the following routes being added:

| Route Name    | HTTP Method   | Route Path            | Route Values                      | Purpose
| ------------- | ------------- | --------------------- | --------------------------------- | -------
| blog.browse   | GET           | /blog{format}         | controller=>blog, action=>browse  | Browse multiple resources
| blog.read     | GET           | /blog/{id}{format}    | controller=>blog, action=>read    | Read a single resource
| blog.edit     | GET           | /blog/{id}/edit       | controller=>blog, action=>edit    | The form for editing a resource
| blog.add      | GET           | /blog/add             | controller=>blog, action=>add     | The form for adding a resource
| blog.delete   | DELETE        | /blog/{id}            | controller=>blog, action=>delete  | Delete a single resource
| blog.create   | POST          | /blog                 | controller=>blog, action=>create  | Create a new resource
| blog.update   | PATCH         | /blog/{id}            | controller=>blog, action=>update  | Update part of an existing resource
| blog.replace  | PUT           | /blog/{id}            | controller=>blog, action=>replace | Replace an entire existing resource

The `{id}` token is whatever has already been defined in the router; it not
already defined, it will be any series of numeric digits. Likewise, the
`{format}` token is whatever has already been defined in the router; if not
already defined, it is an optional dot-format file extension (including the
dot itself).

The `controller` value comes from the route name prefix, and the `action`
value comes from the route name.

If you want calls to `attachResource()` to create a different series of REST
routes, use the `setResourceCallable()` method to set your own callable to
create them.

```php
<?php
$router->setResourceCallable(function ($router) {
    $router->setTokens(array(
        'id' => '([a-f0-9]+)'
    ));
    $router->addPost('create', '/{id}');
    $router->addGet('read', '/{id}');
    $router->addPatch('update', '/{id}');
    $router->addDelete('delete', '/{id}');
});
?>
```

That example will cause only four CRUD routes, using hexadecimal resource IDs,
to be added for the resource when you call `attachResource()`.

### Caching Route Information

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

Note that if there are closures in the _Route_ objects (e.g. for `isMatch()`
or `generate()`), you will not be able to cache the routes; this is because
closures cannot be serialized properly for caching. Consider using non-closure
callables instead.

[Aura.Dispatcher]: https://github.com/auraphp/Aura.Dispatcher

### As a Micro-Framework

Sometimes you may wish to use the _Router_ as a micro-framework. This is 
possible by assigning a `callable` as a default param value, then calling that
param to dispatch it.

```php
<?php
$router->add('read', '/blog/read/{id}{format}')
    ->addTokens(array(
		'id' => '\d+',
        'format' => '(\.[^/]+)?',
	))
	->addValues(array(
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
