# Patchboard: A simple and fast PHP routing library.

[![Build Status](https://travis-ci.org/austinhyde/patchboard.svg?branch=master)](https://travis-ci.org/austinhyde/patchboard)

Building on top of [nikic/fastroute](https://github.com/nikic/fastroute), and inspired by the routing
portion of the [Macaron](http://go-macaron.com/) web framework, this library is a simple, extensible,
non-opinionated, and fast routing library for PHP web applications and services.

**Important:** This is still alpha-quality, but I want to make it real. If you try it out and find any
problems, please open an issue!

## Install

```
composer require austinhyde/patchboard
```

# Example

They say an example is worth a thousand words. Or that might be something else.

```php
<?php

$req = Request::createFromGlobals();
$authorized = function() use ($req) {
  list($user, $pass) = explode(':', base64_decode(
    explode(' ', $req->headers->get('Authorization'))[1]
  ));
  if ($user != 'admin' && $pass != 'admin') {
    return new JSONResponse(['message'=>'Not Authorized'], 403);
  }
};

$json = function($c) use ($req) {
  $r = $c->next();
  if ($r !== null && !($r instanceof Response)) {
    return new JSONResponse($r, 200);
  }
};

$r = new Patchboard\Router;
$r->group('/users', function($r) {
  $r->get('/', function() {
    return [['user_id' => 1, 'username' => 'foo']];
  });
  $r->get('/{id}', function($data) {
    return ['user_id' => $data['id'], 'username' => 'foo'];
  });
}, $authorized, $json);

try {
  $response = $r->dispatch($req->getMethod(), $req->getPathInfo());
}
catch (Patchboard\RouteNotFoundException $ex) {
  $response = new JSONResponse(['message' => $ex->getMessage()], 404);
}
catch (Patchboard\MethodNotAllowedException $ex) {
  $response = new JSONResponse(['message' => $ex->getMessage()], 405,
    ['Allow' => $ex->getAllowed()]);
}

$response->send();
```

Put into words:

1. Create a Request object from global variables (from the [symfony/http-foundation](https://github.com/symfony/http-foundation) library)
2. Register the request with your DI container (can be any implementation, I'd recommend [php-di/php-di](https://github.com/php-di/php-di))
3. Create a new Invoker (from the [php-di/invoker](https://github.com/php-di/invoker) library), and instruct it to resolve parameters from your DI container
4. Create a new Router object, and use an `InvokerInvoker` to utilize the Invoker library and object
5. Create the "authorized" handler. This is just a function that returns a 403 if the user is not authorized.
6. Create a group of routes with the `/users` prefix. Note the trailing `, $authorized`, which instructs the group to apply the `$authorized` handler.
7. Create GET and POST routes for `/users`, and instruct the router to call the corresponding controller methods.
8. Dispatch the call based on HTTP method and path. Handle route-not-found and method-not-allowed errors specially.
9. Send the response back to the browser.

# Concepts

The driving principle behind Patchboard is to be as simple as possible, while being flexible enough to use in any application. Patchboard does this
by **only** being a route dispatcher. That's it. At it's core, it's a glorified `call_user_func` implementation, that pattern matches the function name.
The only assumption it makes is that you are routing on HTTP method and path.

The core unit of Patchboard is the *route*. Each route matches on an HTTP method and path, and has a *handler* attached to it.

A handler is simply either a callable, or an implementation of the `Patchboard\Handler` interface. The callable (or `handle` method), receives arguments
containing information about the matched route. The handler then can do things like authenticate the user, process the HTTP request, transform outputs, etc.