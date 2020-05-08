# The Way - WordPress additional Router
## Description
The Way router is a very simple additional router for WordPress.
It permits to add rewrite rules easily for WordPress.


This WordPress plugin adds a regular expression based router on top of the WordPress standard routing system. Any route declared through this router will take priority over standard WordPress URLs.

## Installation
Download zip and install it like other WordPress plugins and activate it.
Or include enterpoint file into your projects.

## Usage
Include all files or use composer to add autoload to start.

### Initialization & Configuration
In your theme’s `functions.php` file or somewhere in your plugin:
```php
// Include all files or import the router via composer autoload
use TheWay\Router\WpRouter;
  
// Router config
$config = [];
  
// Create the router instance
$r = new \TheWay\Router\WpRouter($config);
$r->init();
```

Save the permalink in the WordPress admin.

#### Available router configuration properties:
* 'prefix' : the router add wordpress query vars, this property permit to change the prefix of the query vars added
* 'routes' : you can pass array of routes configuration (see add routes section)

#### Adding Routes
Two directions to adding routes : 
* with Route class
* with array

#### Basic route
##### Array config
In the router configuration before instance you can passed routes configuration.

* name : the name of the route
* regex : the regex for add_rewrite_rule function of WordPress
* path : the "clean" path of the route wich you can define custom route param
* action : the controller and action to fire if the router matched route (Controller@action)
* private : protect the route, only authentificate user can go to the route (otherwise it return 404)

There is no 'http method' key. It's in the controller action fired that you should check which HTTP method is used.

Example :
```php
$config = [
    'routes' => [
        [
            'name' => 'dasboard',
            'regex'     => '^.*dashboard/index$',
            'path'      => 'dashboard/index',
            'action'    => 'TheWay\Controller\DashboardController@index'
        ]
    ]
];
$r = new \TheWay\Router\WpRouter($config);
$r->init();
```

##### Using Route class
```php
$config = [
    'routes' => [
        new \TheWay\Router\Route('dashboard', '^.*dashboard/index$','dashboard/index', 'TheWay\Controller\DashboardController@index', false)
    ]
];
$r = new \TheWay\Router\WpRouter($config);
$r->init();
```

#### Dynamic route
You can add dynamic parameter to your route by adding () in regex parameter and {} in path parameter of a route
Example :
```php
$config = [
    'routes' => [
        [
            'name' => 'blog',
            'regex'     => '^.*blog/([a-z]*)$',
            'path'      => 'blog/{slug}',
            'action'    => 'TheWay\Controller\BlogController@index'
        ]
    ]
];
$r = new \TheWay\Router\WpRouter($config);
$r->init();
```
And in the controller class : 
```php
<?php
namespace TheWay\Controller;

class BlogController {
    public function index($slug) {
        // do something with $slug parameter
    }
}
```

#### Reverse routing
You can get a route path with the function getUrlForRoute.

Example : 
```php
$config = [
    'routes' => [
        [
            'name' => 'blog',
            'regex'     => '^.*blog/([a-z]*)$',
            'path'      => 'blog/{slug}',
            'action'    => 'TheWay\Controller\BlogController@index'
        ]
    ]
];
$r = new \TheWay\Router\WpRouter($config);
$r->init();
echo $r->getUrlForRoute('blog', array('slug' => 'a-slug'));
// https://yoursite/blog/a-slug
```

#### Redirection
You can redirect with the route name.
```php
$r->redirectToRoute('blog', array('slug' => 'a-slug'));
```

Or with an url
```php
$r->redirectUrl('https://yoursite/blog/a-slug');
```