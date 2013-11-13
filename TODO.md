- addResource() and setResourceCallable() method

- make generate() use ArrayObject in the callable, like is_match, and not
  return a replacement $data array?

* * *

Resource notes.

```php
<?php
    
class Router
{
    public function setResourceCallable($resource)
    {
        $this->resource = $resource;
    }
    
    public function addResource($name, $path)
    {
        // save current values
        
        call_user_func($this->resource, $this, $name, $path);
        
        // restore previous values
    }
}


// mimics Rails 3 as described at
// http://guides.rubyonrails.org/routing.html#crud-verbs-and-actions
$router->setResourceCallable(function ($router, $name, $path) {
    
    $router->setDefault(array(
        'controller' => $name,
    ));
    
    $router->setRequire(array(
        'format' => '(\.[^/]+)?',
    ));
    
    $path = rtrim($path, '/');
    
    $router->setNameParam('action');
    
    // browse the resources, optionally in a format.
    // can double for search when a query string is passed.
    $router->addGet('browse', '{format}');
    
    // get a single resource by ID, optionally in a format
    $router->addGet('read', '/{id}{format}');
    
    // add a new resource and get back its location
    $router->addPost('add', '');
    
    // get the form for an existing resource by ID, optionally in a format
    $router->addGet('edit', '/{id}/edit{format}');
    
    // delete a resource by ID
    $router->addDelete('delete', '/{id}');
    
    // get the form for a new resource
    $router->addGet('new', '/new');
    
    // update an existing resource by ID
    $router->add('update', '/{id}', array(
        'require' => array(
            'REQUEST_METHOD' => 'PUT|PATCH'
        ),
    ));
});

$router->addResource('blogs', '/api/v1/blogs');
$router->addResource('blogs_comments', '/api/v1/blogs/{blog_id}/comments');
?>
```
