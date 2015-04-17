# Aura.Router

A PSR-7 compliant web router implementation.

This package does not provide a dispatching mechanism. Your application is
expected to take the information provided by the matching route and dispatch
it on its own. We provide an example below.

## Getting Started

### Instantiation

We get all our router objects through a library-specific container, so we need
to instantiate it first.

```php
<?php
use Aura\Router\RouterContainer;

$routerContainer = new RouterContainer();
?>
```

We can then retrieve a _Map_ (for adding routes), a _Matcher_ (for matching the
incoming request to a route), and a _Generator_ (for generating links from
routes).

Let's go step-by-step to add a route, then match it.


### Adding A Route

To add a route, get the _Map_ and call `route()` method on it, passing a name
and a path. (We always give our routes a name so we can look them up later.)

This route named `Blog\Read` will match against any request with a path of
`/blog/` followed by any string:

```php
<?php
$map->route('Blog\Read', '/blog/{id}');
?>
```

We will see later how to specify more complex routes.

### Matching A Route

To match a PSR-7 _ServerRequest_ to a mapped _Route_, and add the _Route_
attributes to the _ServerRequest_ attributes, get the _Matcher_ from the
_RouterContainer_ and call its `matchAndSet()` method:

```php
<?php
/**
 * @var Psr\Http\Message\ServerRequestInterface $request
 */
$matcher = $routerContainer->getMatcher();
$route = $matcher->matchAndSet($request);
?>
```

> N.b.: The `$route` result returned from `matchAndSet()` is the matched
> _Route_, or `false` if no match was found.

We can then dispatch to an action or controller.

### Dispatching A Route

Given the above example, we will use the _Route_ name as a double for the action
class to dispatch to. In this case, the name was `Blog\Read`, so we'll use an
invokable class like this:

```php
<?php
namespace Blog;

use Psr\Http\Message\ServerRequestInterface;

class Read
{
    public function __invoke(ServerRequestInterface $request)
    {
        $attributes = $request->getAttributes();
        $id = $attributes['id'];
        // look up the blog $id and respond with its data
    }
}
?>
```

We can then use three lines of generic code to dispatch the request to the
action object:

```php
<?php
$class = $route->name; // get the action class name from the route name
$action = new $class(); // instantiate the action object
$action($request); // invoke the action object with the request
?>
```

## Advanced Topics


### Handling Failure To Match

When `$map->match()` returns empty, it means there was no matching route for the URL path and server variables. However, we can still discover something about the matching process; in particular, whether the failure is related to an HTTP method or an `Accept` header.

```php
<?php
// get the first of the best-available non-matched routes
$failure = $map->getFailedRoute();

// inspect the failed route
if ($failure->failedMethod()) {
    // the route failed on the allowed HTTP methods.
    // this is a "405 Method Not Allowed" error.
} elseif ($failure->failedAccept()) {
    // the route failed on the available content-types.
    // this is a "406 Not Acceptable" error.
} else {
    // there was some other unknown matching problem.
}
?>
```

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

// does the route indicate an action?
if (isset($route->params['action'])) {
    // take the action class directly from the route
    $action_class = $route->params['action'];
} else {
    // use a default action class
    $action_class = 'IndexAction';
}

// instantiate the action class
$action = new $action_class();

// call the __invoke() method on the action
// class using the route params
echo $action->__invoke($route->params);
?>
```

Again, note that the _Map_ will not dispatch for you; the above is provided
as a naive example only to show how to use route values.  For a more complex
dispatching system, try [Aura.Dispatcher][].

### Generating A Route Path

To generate a URL path from a route so that you can create links, call
`generate()` on the _Map_ and provide the route name with optional data.

```php
<?php
// $path => "/blog/read/42.atom"
$path = $generator->generate('read', array(
    'id' => 42,
    'format' => '.atom',
));

$href = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
echo '<a href="' . $href .'">Atom feed for this blog entry</a>';
?>
```

The _Map_ does not do dynamic matching of routes; a route must have a name
to be able to generate a path from it.

The example shows that passing an array of data as the second parameter will
cause that data to be interpolated into the route path. This data array is
optional. If there are path params without matching data keys, those params
will *not* be replaced, leaving the `{param}` token in the path. If there are
data keys without matching params, those values will not be added to the path.

### Extended Route Specification

You can extend a route specification with the following methods:

- `addTokens()` -- Adds regular expression subpatterns that params must
  match.

        addTokens(array(
            'id' => '\d+',
        ))

    Note that `addTokens()` is also available, but this will replace any
    previous subpatterns entirely, instead of merging with the existing
    subpatterns.

- `addAccept()` -- Adds a list of content types that the route responds to. Note that this is *not* content negotiation per se, only a "sanity check" to make sure the route can eventually provide the content types specified by the request.

        addAccept(array(
            'application/json',
            'application/xml',
            'text/csv',
        ));

    Note that `addAccept()` is also available, but this will replace any
    previous content types entirely, instead of merging with the existing
    types.

- `addDefaults()` -- Adds default values for the params.

        addDefaults(array(
            'year' => '1979',
            'month' => '11',
            'day' => '07'
        ))

    Note that `setDefaults()` is also available, but this will replace any
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

- `setHost()`

- `addCustom()`

Here is a full extended route specification named `Blog\Edit`:

```php
<?php
$map->route('Blog\Read', '/blog/{id}')
    ->addMethods(['PUT', 'POST', 'PATCH'])
    ->addTokens(array(
        'id' => '\d+',
    ))
    ->addDefaults(array(
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

You can set the default route specifications with the following _Map_
methods; the values will apply to all routes added thereafter.

```php
<?php
// add to the default 'tokens' expressions; addTokens() is also available
$map->addTokens(array(
    'id' => '\d+',
));

// add to the default 'server' expressions; setHeaders() is also available
$map->addHeaders(array(
    'REQUEST_METHOD' => 'PUT|PATCH',
));

// add to the default param values; setDefaults() is also available
$map->addDefaults(array(
    'format' => null,
));

// set the default 'secure' value
$map->setSecure(true);

// set the default wildcard param name
$map->setWildcard('other');

// set the default 'routable' flag
$map->setRoutable(false);

// set the default 'isMatch()' callable
$map->setIsMatchCallable(function (...) { ... });

// set the default 'generate()' callable
$map->setGenerateCallable(function (...) { ... });
?>
```

### Simple Routes

You don't need to specify an extended route specification. With the following
simple route ...

```php
<?php
$map->route('archive', '/archive/{year}/{month}/{day}');
?>
```

... the _Map_ will use a default subpattern that matches everything except
slashes for the path params. Thus, the above simple route is equivalent to the
following extended route:

```php
<?php
$map->route('archive', '/archive/{year}/{month}/{day}')
    ->setDefaults(array(
        'action' => 'archive',
    ))
    ->addTokens(array(
        'year'  => '[^/]+',
        'month' => '[^/]+',
        'day'   => '[^/]+',
    ));
?>
```

### Automatic Params

The _Map_ will automatically populate values for the `action`
route param if one is not set manually.

```php
<?php
// ['action' => 'foo.bar'] because it has not been set otherwise
$map->route('foo.bar', '/path/to/bar');

// ['action' => 'zim'] because we add it explicitly
$map->route('foo.dib', '/path/to/dib')
       ->addDefaults(array('action' => 'zim'));

// the 'action' param here will be whatever the path value for {action} is
$map->route('/path/to/{action}');
?>
```

### Optional Params

Sometimes it is useful to have a route with optional named params. None, some,
or all of the optional params may be present, and the route will still match.

To specify optional params, use the notation `{/param1,param2,param3}` in the
path. For example:

```php
<?php
$map->route('archive', '/archive{/year,month,day}')
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

Only one set of optional params per path is recognized by the _Map_.

Optional params belong at the end of a route path; placing them elsewhere may
result in unexpected behavior.

If you `generate()` a link with optional params, the params will be filled in
if they are present in the data for the link. Remember, the optional params
are *sequentially* optional, so the params will not be filled in after the
first missing one:

```php
<?php
$map->route('archive', '/archive{/year,month,day}')
    ->addTokens(array(
        'year'  => '\d{4}',
        'month' => '\d{2}',
        'day'   => '\d{2}'
    ));

$link = $generator->generate('archive', array(
    'year' => '1979',
    'month' => '11',
)); // "/archive/1979/11"
?>
```

Similarly, optional params can be used as a generic catchall route:

```php
<?php
$map->route('generic', '{/controller,action,id}')
    ->setDefaults(array(
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
$map->route('wild_post', '/post/{id}')
    ->setWildcard('other');

// this matches, with the following values
$route = $map->match('/post/88/foo/bar/baz', $_SERVER);
// $route->params['id'] = 88;
// $route->params['other'] = array('foo', 'bar', 'baz')

// this also matches, with the following values; note the trailing slash
$route = $map->match('/post/88/', $_SERVER);
// $route->params['id'] = 88;
// $route->params['other'] = array();

// this also matches, with the following values; note the missing slash
$route = $map->match('/post/88', $_SERVER);
// $route->params['id'] = 88;
// $route->params['other'] = array();
?>
```

If you `generate()` a link with wildcard params, the wildcard key in the data
will be used for the trailing arbitrary param values:

```php
<?php
$map->route('wild_post', '/post/{id}')
    ->setWildcard('other');

$link = $generator->generate('wild_post', array(
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

$map->attach($name_prefix, $path_prefix, function ($router) {

    $map->route('browse', '{format}')
        ->addTokens(array(
            'format' => '(\.json|\.atom|\.html)?'
        ))
        ->addDefaults(array(
            'format' => '.html',
        ));

    $map->route('read', '/{id}{format}')
        ->addTokens(array(
            'id'     => '\d+',
            'format' => '(\.json|\.atom|\.html)?'
        ))
        ->addDefaults(array(
            'format' => '.html',
        ));

    $map->route('edit', '/{id}/edit{format}')
        ->addTokens(array(
            'id' => '\d+',
            'format' => '(\.json|\.atom|\.html)?'
        ))
        ->addDefaults(array(
            'format' => '.html',
        ));
});
?>
```

Each of the route names will be prefixed with 'blog.', and each of the route paths
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

$map->attach($name_prefix, $path_prefix, function ($router) {

    $map->addTokens(array(
        'id'     => '\d+',
        'format' => '(\.json|\.atom)?'
    ));

    $map->setDefaults(array(
        'format' => '.html',
    ));

    $map->route('browse', '');
    $map->route('read', '/{id}{format}');
    $map->route('edit', '/{id}/edit');
});
?>
```

### Attaching REST Resource Routes

The router can attach a series of REST resource routes for you with the
`attachResource()` method:

```php
<?php
$map->attachResource('blog', '/blog');
?>
```

That method call will result in the following routes being added:

| Route Name    | HTTP Method   | Route Path            | Purpose
| ------------- | ------------- | --------------------- | -------
| blog.browse   | GET           | /blog{format}         | Browse multiple resources
| blog.read     | GET           | /blog/{id}{format}    | Read a single resource
| blog.edit     | GET           | /blog/{id}/edit       | The form for editing a resource
| blog.add      | GET           | /blog/add             | The form for adding a resource
| blog.delete   | DELETE        | /blog/{id}            | Delete a single resource
| blog.create   | POST          | /blog                 | Create a new resource
| blog.update   | PATCH         | /blog/{id}            | Update part of an existing resource
| blog.replace  | PUT           | /blog/{id}            | Replace an entire existing resource

The `{id}` token is whatever has already been defined in the router; if not
already defined, it will be any series of numeric digits. Likewise, the
`{format}` token is whatever has already been defined in the router; if not
already defined, it is an optional dot-format file extension (including the
dot itself).

The `action` value is the same as the route name.

If you want calls to `attachResource()` to create a different series of REST
routes, use the `setResourceCallable()` method to set your own callable to
create them.

```php
<?php
$map->setResourceCallable(function ($router) {
    $map->addTokens(array(
        'id' => '([a-f0-9]+)'
    ));
    $map->post('create', '/{id}');
    $map->get('read', '/{id}');
    $map->patch('update', '/{id}');
    $map->delete('delete', '/{id}');
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
    $map->setRoutes($routes);

} else {

    // build the routes using add() and attach() ...
    // ... ... ...
    // ... then save to the cache for the next page load
    $routes = $map->getRoutes();
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

Sometimes you may wish to use the _Map_ as a micro-framework. This is
possible by assigning a `callable` as a default param value, usually `action`,
then calling that param to dispatch it.

```php
<?php
$map->route('read', '/blog/read/{id}{format}')
    ->addTokens(array(
        'id' => '\d+',
        'format' => '(\.[^/]+)?',
    ))
    ->addDefaults(array(
        'action' => function ($params) {
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
Alternatively, and perhaps more easily, you may specify a third parameter to the `add()` method; this will be used as the `action` value in the params. The following is identical to the above:

```php
<?php
$map->route(
    'read',
    '/blog/read/{id}{format}',
    function ($params) {
        if ($params['format'] == '.json') {
            $id = (int) $params['id'];
            header('Content-Type: application/json');
            echo json_encode(['id' => $id]);
        } else {
            $id = (int) $params['id'];
            header('Content-Type: text/plain');
            echo "Reading blog ID {$id}";
        }
    })
    ->addTokens(array(
        'id' => '\d+',
        'format' => '(\.[^/]+)?',
    ))
    ->addDefaults(array(
        'format' => '.html',
    ));
?>
```

A naive micro-framework dispatcher might then work like this:

```php
<?php
// get the route params
$params = $route->params;

// extract the action callable from the params
$action = $params['action'];
unset($params['action']);

// invoke the callable
$action($params);
?>
```

With the above example action, the URL `/blog/read/1.json` will send JSON
ouput, but for `/blog/read/1` it will send plain text output.
