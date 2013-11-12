need better data structure for routes. at least we need to make the route
object modifiable before it's called for isMatch() to support micro-framework
idioms. probably reduce the constructor size and replace with setter methods.
lock it after matched?

add accept header (and other headers) to router? Do we capture the values or
do we leave that to the controller?

capture domain information?

drop callables for custom matching and generation?

split the route adding/attaching from the matching?

can we make "require" a callable?

get rid of $attach from the Router constructor? still want to be able to
attach route defs from packages/modules/etc, and not just have them call the
router directly.

hierarchical routing, or route grouping? can we do this by attaching a second
router object?

for the main router object, define defaults? that would go along with "second
router object" being attached.


* * *

making resource routes:

```php
<?php
    $router->set('api.v1.blog', '/api/v1/blog', array(
        'resource' => true,
    ));
?>
```

Is the equivalent of:

```php
<?php
    // browse/index/home/etc
    $router->set('api.v1.blog', '/api/v1/blog', array(
        'method' => 'GET',
        'default' => array(
            'controller' => 'api.v1.blog',
            'action' => 'browse',
        ),
    ));
    
    // read a resource
    $router->set('api.v1.blog.read', '/api/v1/blog/{id}', array(
        'method' => 'GET',
        'default' => array(
            'controller' => 'api.v1.blog',
            'action' => 'read',
        ]
    ));
    
    // edit an existing resource
    $router->set('api.v1.blog.edit', '/api/v1/blog/{id}', array(
        'method' => array('PUT', 'PATCH'),
        'default' => array(
            'controller' => 'api.v1.blog',
            'action' => 'edit',
        ]
    ));
    
    // add a new resource
    $router->set('api.v1.blog.add', '/api/v1/blog', array(
        'method' => 'POST',
        'default' => array(
            'controller' => 'api.v1.blog',
            'action' => 'add',
        ]
    ));
    
    // delete an existing resource
    $router->set('api.v1.blog.delete', '/api/v1/blog/{id}', array(
        'method' => 'DELETE',
        'default' => array(
            'controller' => 'api.v1.blog',
            'action' => 'delete',
        ]
    ));

    // search a resource
    $router->set('api.v1.blog.search', '/api/v1/blog/search', array(
        'method' => 'GET',
        'default' => array(
            'controller' => 'api.v1.blog',
            'action' => 'search',
        ]
    ));

    // blank form or template for a new resource
    $router->set('api.v1.blog.form', '/api/v1/blog/form', array(
        'method' => 'GET',
        'default' => array(
            'controller' => 'api.v1.blog',
            'action' => 'form',
        ]
    ));
?>
```

If no ID is specified, use `id`.

If no controller is specified, use the route name.

Overrides:

```php
<?php
    $router->set('api.v1.blog', '/api/v1/blog', array(
        'resource' => array(
            // use blog_id instead
            'id'        => 'blog_id',
            
            // use these action names instead
            'browse'    => 'listing',
            'read'      => 'retrieve',
            'edit'      => 'modify',
            'add'       => 'create',
            'delete'    => 'destroy',
            'search'    => 'find',
            'form'      => false, // do not generate a route for this
        ),
        'default' => array(
            'controller' => 'Vendor\Package\Api\Blog',
        ),
    ));
?>
```
