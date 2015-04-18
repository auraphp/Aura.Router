# Aura.Router

A PSR-7 compliant web router implementation.

This package does not provide a dispatching mechanism. Your application is expected to take the information provided by the matching route and dispatch it on its own. We provide an example below.

## Getting Started

### Instantiation

We get all our router objects through a library-specific container, so we need to instantiate it first.

```php
<?php
use Aura\Router\RouterContainer;

$routerContainer = new RouterContainer();
?>
```

We can then retrieve a _Map_ (for adding routes), a _Matcher_ (for matching the incoming request to a route), and a _Generator_ (for generating links from routes).

Let's go step-by-step to add a route, then match it.


### Adding A Route

To add a route, first retrieve the _Map_ from the _RouterContainer_.

```php
<?php
$map = $routerContainer->getMap();
?>
```

Then call one of its route-adding methods:

- `$map->get()` adds a GET route
- `$map->put()` adds a PUT route
- `$map->post()` adds a POST route
- `$map->patch()` adds a PATCH route
- `$map->delete()` adds a DELETE route
- `$map->options()` adds a OPTIONS route
- `$map->head()` adds a HEAD route

Each route-adding method takes three parameters:

1. A `$name` (for when you need to generate link from the route)
2. A `$path` (with optional named token placeholders)
3. An optional `$handler` (a closure, callback, action class, controller class, etc); if you do not pass a handler, the route will use the $name parameter as the handler.

For example, this route named `blog.read` will match against a `GET` request on the path `/blog/42` (or any other `{id}` value). It also defines a closure as a handler for the route, using a _ServerRequestInterface_ instance and a _ResponseInterface_ instance as arguments.

```php
<?php
$map->get('blog.read', '/blog/{id}', function ($request, $response) {
    $id = (int) $request->getAttribute('id');
    $response->body()->write("You asked for blog entry {$id}.");
    return $response;
});
?>
```

### Matching A Request To A Route

To match a PSR-7 _ServerRequestInterface_ instance to a mapped _Route_, first get the _Matcher_ from the _RouterContainer_.

```php
<?php
$matcher = $routerContainer->getMatcher();
?>
```

Then call `Mather::matchAndSet()` method to get back the matched _Route_. Incidentally, this will also update the _Request_ with the matched _Route_ attributes from any named placeholder tokens.

```php
<?php
/**
 * @var Psr\Http\Message\ServerRequestInterface $request
 */
$route = $matcher->matchAndSet($request);
?>
```

We can then dispatch to the route handler.

### Dispatching A Route

Given the above example, disptching to the route handler is trivial. Because the handler is a callable, you can invoke the route directly and it will run the handler for you.

```php
<?php
/**
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var Psr\Http\Message\ResponseInterface $response
 */
$response = $route($request, $response);
?>

We can then do whatever we like with the return value; in this case, you would probably send the response.

### Generating A Route Path

To generate a path from a route so that you can create links, first retrieve the _Generator_ from the _RouterContainer_.

```php
<?php
$generator = $routerContainer->getGenerator();
?>
```

You can then call `Generagor::generate()` with the route name and optional attributes to use for named placeholder tokens.

```php
<?php
$path = $generator->generate('blog.read', ['id' => 42]);
$href = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
echo "<a href=\"{$href}\">Blog link</a>";
?>
```

The `generate()` method will URL-encode the placeholder token values automatically. Use the `generateRaw()` method to leave them un-encoded.

If there are named placeholder tokens in path without corresponding attributes, those tokens will *not* be replaced, leaving the placeholder token in the path.

If there are attributes without corresponding tokens, those attributes will not be added to the path.


## Advanced Topics

### Handling Failure To Match

When `$map->matchAndSet()` returns empty, it means there was no matching route for the URL path and server variables. However, we can still discover something about the matching process; in particular, whether the failure is related to an HTTP method or an `Accept` header.

```php
<?php
// get the first of the best-available non-matched routes
$failedRoute = $map->getFailedRoute();

switch ($failure->failedRule) {
    case 'Aura\Router\Rule\Method':
        // 405 METHOD NOT ALLOWED
        // Send the $failedRoute->methods as 'Accepts:'
        break;
    case 'Aura\Router\Rule\Accept':
        // 406 NOT ACCEPTABLE
        // Send the $failedRoute->accepts as 'Accepts:'
        break;
    default:
        // 404 NOT FOUND
        break;
}
?>
```

### Extended Route Specification

You can extend a route specification with the following methods:

- `tokens()` -- Adds placeholder token names and regular expressions.

        tokens(array(
            'id' => '\d+',
        ))

- `defaults()` -- Adds default values for the attributes.

        defaults(array(
            'year' => '1979',
            'month' => '11',
            'day' => '07'
        ))

- `secure()` -- When `true` the `$server['HTTPS']` value must be on, or the
  request must be on port 443; when `false`, neither of those must be in
  place.

- `wildcard()` -- Sets the name of a wildcard attribute; this is where
  arbitrary slash-separated values appearing after the route path will be
  stored.

- `routable()` -- When `false` the route will be used only for generating
  paths, not for matching (`true` by default).

- `accept()` -- Sets a list of content types that the route responds to. Note that this is *not* content negotiation per se, only a pro-forma check to make sure the route can eventually provide the content types specified by the request.

        accept(array(
            'application/json',
            'application/xml',
            'text/csv',
        ));

- `host()`

- `extras()`

Here is a full extended route specification named `Blog\Edit`:

```php
<?php
$map->post('Blog\Edit', '/blog/{id}')
    ->methods(['PUT', 'PATCH'])
    ->tokens(['id' => '\d+'])
    ->defaults(array(
        'id' => 1,
        'format' => '.html',
    ))
    ->secure(false)
    ->routable(true)
?>
```

### Default Route Specifications

You can set the default route specifications with the following _Map_
methods; the values will apply to all routes added thereafter.

```php
<?php
// add to the default 'tokens' expressions; tokens() is also available
$map->tokens(array(
    'id' => '\d+',
));

// add to the default attribute values; setDefaults() is also available
$map->defaults(array(
    'format' => null,
));

// set the default 'secure' value
$map->secure(true);

// set the default wildcard attribute name
$map->wildcard('other');

// set the default 'routable' flag
$map->routable(false);

// accept
// host
// extras
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
slashes for the path attributes. Thus, the above simple route is equivalent to the
following extended route:

```php
<?php
$map->route('archive', '/archive/{year}/{month}/{day}')
    ->handler('archive')
    ->tokens(array(
        'year'  => '[^/]+',
        'month' => '[^/]+',
        'day'   => '[^/]+',
    ));
?>
```

### Optional Attributes

Sometimes it is useful to have a route with optional attributes. None, some,
or all of the optional attributes may be present, and the route will still match.

To specify optional attributes, use the notation `{/attribute1,attribute2,attribute3}` in the
path. For example:

```php
<?php
$map->route('archive', '/archive{/year,month,day}')
    ->tokens(array(
        'year'  => '\d{4}',
        'month' => '\d{2}',
        'day'   => '\d{2}'
    ));
?>
```

> N.b.: The leading slash separator is inside the attributes token, not outside.

With that, the following routes will all match the 'archive' route, and will
set the appropriate values:

    /archive
    /archive/1979
    /archive/1979/11
    /archive/1979/11/07

Optional attributes are *sequentially* optional. This means that, in the above
example, you cannot have a "day" without a "month", and you cannot have a
"month" without a "year".

Only one set of optional attributes per path is recognized by the _Map_.

Optional attributes belong at the end of a route path; placing them elsewhere may
result in unexpected behavior.

If you `generate()` a link with optional attributes, the attributes will be filled in
if they are present in the data for the link. Remember, the optional attributes
are *sequentially* optional, so the attributes will not be filled in after the
first missing one:

```php
<?php
$map->route('archive', '/archive{/year,month,day}')
    ->tokens(array(
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

Similarly, optional attributes can be used as a generic catchall route:

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

That will match these paths, with these attribute values:

    /           : 'controller' => 'index', 'action' => 'browse', 'id' => null
    /foo        : 'controller' => 'foo',   'action' => 'browse', 'id' => null
    /foo/bar    : 'controller' => 'foo',   'action' => 'bar',    'id' => null
    /foo/bar/42 : 'controller' => 'foo',   'action' => 'bar',    'id' => '42'


### Wildcard Params

Sometimes it is useful to allow the trailing part of the path be anything at
all. To allow arbitrary trailing attributes on a route, extend the route
definition with `wildcard()` to specify attribute name under which the
arbitrary trailing attribute values will be stored.

```php
<?php
$map->route('wild_post', '/post/{id}')
    ->wildcard('other');

// this matches, with the following values
$route = $map->match('/post/88/foo/bar/baz', $_SERVER);
// $route->attributes['id'] = 88;
// $route->attributes['other'] = array('foo', 'bar', 'baz')

// this also matches, with the following values; note the trailing slash
$route = $map->match('/post/88/', $_SERVER);
// $route->attributes['id'] = 88;
// $route->attributes['other'] = array();

// this also matches, with the following values; note the missing slash
$route = $map->match('/post/88', $_SERVER);
// $route->attributes['id'] = 88;
// $route->attributes['other'] = array();
?>
```

If you `generate()` a link with wildcard attributes, the wildcard key in the data
will be used for the trailing arbitrary attribute values:

```php
<?php
$map->route('wild_post', '/post/{id}')
    ->wildcard('other');

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
        ->tokens(array(
            'format' => '(\.json|\.atom|\.html)?'
        ))
        ->defaults(array(
            'format' => '.html',
        ));

    $map->route('read', '/{id}{format}')
        ->tokens(array(
            'id'     => '\d+',
            'format' => '(\.json|\.atom|\.html)?'
        ))
        ->defaults(array(
            'format' => '.html',
        ));

    $map->route('edit', '/{id}/edit{format}')
        ->tokens(array(
            'id' => '\d+',
            'format' => '(\.json|\.atom|\.html)?'
        ))
        ->defaults(array(
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

    $map->tokens(array(
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
    $map->tokens(array(
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
possible by assigning a `callable` as a default attribute value, usually `action`,
then calling that attribute to dispatch it.

```php
<?php
$map->route('read', '/blog/read/{id}{format}')
    ->tokens(array(
        'id' => '\d+',
        'format' => '(\.[^/]+)?',
    ))
    ->defaults(array(
        'action' => function ($attributes) {
            if ($attributes['format'] == '.json') {
                $id = (int) $attributes['id'];
                header('Content-Type: application/json');
                echo json_encode(['id' => $id]);
            } else {
                $id = (int) $attributes['id'];
                header('Content-Type: text/plain');
                echo "Reading blog ID {$id}";
            }
        },
        'format' => '.html',
    ));
?>
```
Alternatively, and perhaps more easily, you may specify a third argument to the `add()` method; this will be used as the `action` value in the attributes. The following is identical to the above:

```php
<?php
$map->route(
    'read',
    '/blog/read/{id}{format}',
    function ($attributes) {
        if ($attributes['format'] == '.json') {
            $id = (int) $attributes['id'];
            header('Content-Type: application/json');
            echo json_encode(['id' => $id]);
        } else {
            $id = (int) $attributes['id'];
            header('Content-Type: text/plain');
            echo "Reading blog ID {$id}";
        }
    })
    ->tokens(array(
        'id' => '\d+',
        'format' => '(\.[^/]+)?',
    ))
    ->defaults(array(
        'format' => '.html',
    ));
?>
```

A naive micro-framework dispatcher might then work like this:

```php
<?php
// get the route attributes
$attributes = $route->attributes;

// extract the action callable from the attributes
$action = $attributes['action'];
unset($attributes['action']);

// invoke the callable
$action($attributes);
?>
```

With the above example action, the URL `/blog/read/1.json` will send JSON
ouput, but for `/blog/read/1` it will send plain text output.
