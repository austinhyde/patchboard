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
  $r = $c->nextHandler();
  if ($r !== null && !($r instanceof Response)) {
    return new JSONResponse($r, 200);
  }
};

$r = new Patchboard\Router;
$r->group('/users', function($r) {
  $r->get('/', function() {
    return [['user_id' => 1, 'username' => 'foo']];
  });
  $r->get('/{id}', function(Context $ctx) {
    return ['user_id' => $ctx->getPathParam('id'), 'username' => 'foo'];
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
2. Create the "authorized" handler. This is just a function that returns a 403 if the user is not authorized.
3. Create the "json" handler. This is a function that converts the result of the next handler in the sequence to JSON.
4. Create a new Router object
5. Create a group of routes with the `/users` prefix. Note the trailing `, $authorized, $json`, which instructs the group to apply the `$authorized` and `$json` handler.
6. Create GET and POST routes for `/users`, and instruct the router to call the corresponding controller methods.
7. Dispatch the call based on HTTP method and path. Handle route-not-found and method-not-allowed errors specially.
8. Send the response back to the browser.

# Concepts

The driving principle behind Patchboard is to be as simple as possible, while being flexible enough to use in any application. Patchboard does this
by **only** being a route dispatcher. That's it. At it's core, it's a glorified `call_user_func` implementation, that pattern matches the function name.
The only assumption it makes is that you are routing on an HTTP method and request path.

The core unit of Patchboard is the *route*. Each route matches on an HTTP method and path, and has any number of *handlers* attached to it.

A handler is simply either a callable, or an implementation of the `Patchboard\HandlerInterface` interface. The callable (or `handle` method) receives the route *context*
as an argument. The handler then can do things like authenticate the user, process the HTTP request, transform outputs, etc.

The route context is an object containing information on the matched route and assigned handlers. Handlers can use the `hasNextHandler()` and `handleNext()` methods
to see if there's another handler to call, and to invoke the next handler, respectively. `handleNext()` returns the result of the next handler in the sequence. This
allows you to "wrap" inner handlers and manipulate their results.

For any list of handlers assigned to a route, handlers will be executed sequentially until a non-null result is returned, or there are no more handlers. Handlers
that invoke the next handler themselves advance the internal cursor to the next, so that no handlers are called twice.