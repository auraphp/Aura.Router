Introduction
============

Aura Router is a PHP 5.3+ library that implements web routing. Given a URI path and a copy of `$_SERVER`, it will extract controller, action, and parameter values for a specific application route.

Your application foundation or framework is expected to take the information provided by the matching route and dispatch to a controller on its own. As long as your system can provide a URI path string and a representative copy of `$_SERVER`, you can use Aura Router.

Aura Router is inspired by [Solar rewrite rules](http://solarphp.com/manual/dispatch-cycle.rewrite-rules) and <http://routes.groovie.org>.


Basic Usage
===========

Mapping A Route
---------------

To create a route for your application, instantiate a `Map` object from the `aura\router` package and call `add()`.

    <?php
    // create a route factory for the map object
    $route_factory = new \aura\router\RouteFactory;
    
    // create the map object
    $map = \aura\router\Map($route_factory);
    
    // add a short-form named route without params
    $map->add('home', '/');
    
    // add a short-form unnamed route with params
    $map->add(null, '/{:controller}/{:action}/{:id}');
    
    // add a long-form named route
    $map->add('read', array(
        'path' => '/blog/read/{:id}{:format}',
        'params' => array(
            'id'     => '(\d+)',
            'format' => '(\..+)?',
        ),
        'values' => array(
            'controller => 'blog',
            'action'    => 'read'
            'format'    => 'html',
        ),
    ));

You will need to place the `Map` object where you can get to it from your application; e.g., in a registry, a service locator, or a dependency injection container.  Describing such placement is beyond the scope of this document.


Matching A Route
----------------

To match a URI path against your route map, call `getRoute()` with a path string and the `$_SERVER` values.

    <?php
    // get the incoming request URI path
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // get the route based on the path and server
    $route = $map->getRoute($path, $_SERVER);

The `getRoute()` method does not parse the URI or use `$_SERVER` internally. This is becuase different systems may have different ways of representing that information; e.g., through a URI object or a context object.  As long as you can pass the string path and a server array, you can use Aura Router in your application foundation or framework.

The returned `$route` object will contain, among other things, a `$values` array with values for each of the parameters identified by the route path. For example, matching a route with the path `/{:controller}/{:action}/{:id}` will populate the `$route->values` array with `controller`, `action`, and `id` keys.


Dispatching A Route
-------------------

Now that you have route, you can dispatch it. The following is what a foundation or framework system might do with a route to invoke a page controller. 

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
        $controller = 'Default';
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

Again, note that Aura Router will not dispatch for you; the above is provided as a naive example only to show how to use route values.


Generating A Route Path
-----------------------

To generate a URI path from a route so that you can create links, call `getPath()` on the `Map` object and provide the route name.

    <?php
    // $path => "/blog/read/42.atom"
    $path = $map->getPath('read', array(
        'id' => 42,
        'format' => '.atom',
    ));
    
    $href = htmlspecialchars($path, 'UTF-8');
    echo '<a href="$href">Atom feed for this blog entry</a>';

Aura Router does not do dynamic matching of routes; a route must have a name to be able to generate a path from it.

The example shows that passing an array of data as the second parameter will cause that data to be interpolated into the route path. This data array is optional. If there are path params without matching data keys, those params will *not* be replaced, leaving the `{:param}` token in the path. If there are data keys without matching params, those values will not be added to the path.


Advanced Usage
==============

Long-Form Route Specification
-----------------------------

When you add a route, you define it as an array with one or more of the following recognized keys:

- `path` -- The path this route represents.  The path may contain param tokens in the form of `{:param}`.  To define the regular expression subpattern for a param, you can either place a key for it in the `params` part, or you can define it inline like so: `{:id:(\d+)}`.

- `params` -- The regular expression subpatterns for path params; inline params will override these settings. For example:
        
        'params' => array(
            'id' => '(\d+)',
        )

- `values` -- The default values for the route. These will be overwritten by matching params from the path.

        'values' => array(
            'controller' => 'blog',
            'action' => 'read',
            'id' => 1,
        )
        
- `method` -- The `$server['REQUEST_METHOD']` must match one of these values.

- `secure` -- When `true` the `$server['HTTPS']` value must be on, or the request must be on port 443; when `false`, neither of those must be in place.

- `is_match` -- A custom closure with the signature `function($server, &$matches)` that returns true on a match, or false if not. This allows developers to build any kind of matching logic for the route, and to change the `$matches` for param values from the path.

Here is a complete long-form route specification named `read` with all keys in place:

    <?php
    $map->add('read', array(
        'path' => '/blog/read/{:id}{:format}',
        'params' => array(
            'id' => '(\d+)',
            'format' => '(\..+)?',
        ),
        'values' => array(
            'controller' => 'blog',
            'action' => 'read',
            'id' => 1,
            'format' => '.html',
        ),
        'secure' => false,
        'method' => array('GET'),
        'is_match' => function($server, &$matches) {
                
            // disallow matching if referred from example.com
            if ($server['HTTP_REFERER'] == 'http://example.com') {
                return false;
            }
            
            // add the referer from $server to the match values
            $matches['referer'] = $server['HTTP_REFERER'];
            return true;
            
        },
    ));

Short-Form Route Specification
------------------------------

You don't need to specify a long-form route description array.  If you pass a string for the route instead of an array ...

    <?php
    $map->add('archive', '/archive/{:year}/{:month}/{:day}');

... then Aura Router will use a default subpattern that matches everything except slashes for the path params, and use the route name as the default value for `'action'`.  Thus, the above short-form route is equivalent to the following long-form route:

    <?php
    $map->add('archive', array(
        'path' => '/archive/{:year}/{:month}/{:day}',
        'params' => array(
            'year'  => '([^/]+)',
            'month' => '([^/]+)',
            'day'   => '([^/]+)',
        ),
        'values' => array(
            'action' => 'archive',
        ),
    ));


Attaching Route Groups
----------------------

You can add a series of routes all at once under a single "mount point" in your application.  For example, if you want all your blog-related routes to be mounted at `'/blog'` in your application, you can do this:

    <?php
    $map->attach('/blog', array(
        
        // the routes to attach
        'routes' => array(
            
            // a short-form route named 'browse'
            'browse' => '/',
            
            // a long-form route named 'read'
            'read' => array(
                'path' => '/{:id}{:format}',
                'params' => array(
                    'id'     => '(\d+)',
                    'format' => '(\.json|\.atom)?'
                ),
                'values' => array(
                    'format' => '.html',
                ),
            ),
            
            // a short-form route named 'edit'
            'edit' => '/{:id:(\d+)}/edit',
        ),
    ));
    
Each of the route paths will be prefixed with `/blog`, so the effective paths become:

- `browse: /blog/`
- `read:   /blog/{:id}{:format}`
- `edit:   /blog/{:id}/edit`

You can set other route specification keys as part of the attachment specification; these will be used as the defaults for each attached route, so you don't need to repeat common information:

    <?php
    $map->attach('/blog', array(
        
        // common params for the routes
        'params' => array(
            'id'     => '(\d+)',
            'format' => '(\.json|\.atom)?',
        ),
        
        // common values for the routes
        'values' => array(
            'controller' => 'blog',
            'format'     => '.html',
        ),
        
        // the routes to attach
        'routes' => array(
            'browse' => '/',
            'read'   => '/{:id}{:format}',
            'edit'   => '/{:id}/edit',
        ),
    ));


Constructor-Time Attachment
---------------------------

You can configure your routes in a single array of attachment groups, and then pass them to the `Map` constructor all at once. This allows you to separate configuration and construction of routes.

Note that you can specify a `name_prefix` as part of the common route information for each attached route group; the route names in that group will be prefixed with that value. This helps with deconfliction of routes with the same names in different groups.

    <?php
    $attach = array(
        // attach to /blog
        '/blog' => array(
            
            // prefix for route names
            'name_prefix' => 'projectname.blog.',
            
            // common params for the routes
            'params' => array(
                'id' => '(\d+)',
                'format' => '(\.json|\.atom)?',
            ),
        
            // common values for the routes
            'values' => array(
                'controller' => 'blog',
                'format' => '.html',
            ),
        
            // the routes to attach
            'routes' => array(
                'browse' => '/',
                'read' => 'path' => '/{:id}{:format}',
                'edit' => '/{:id}/edit',
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
    );

    // create the route factory
    $route_factory = new \aura\router\RouteFactory;
    
    // create a Map with attached route groups
    $map = new \aura\router\Map($route_factory, $attach);

This technique can be very effective with modular application packages. Each package can return an array for its own route group specification, and a system-specific configuration mechanism can collect each spec into a common array for the `Map`.  For example:

    <?php
    // get a routes array from each application packages
    $attach = array(
        '/blog'  => require 'package/blog/routes.php',
        '/forum' => require 'package/forum/routes.php',
        '/wiki'  => require 'package/wiki/routes.php',
    );
    
    // create the route factory
    $route_factory = new \aura\router\RouteFactory;
    
    // create a Map with attached route groups
    $map = new \aura\router\Map($route_factory, $attach);

* * *

