<?php
namespace Patchboard;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdRouteParser;
use FastRoute\DataGenerator\GroupCountBased as GCBasedDataGenerator;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased as GCBasedDispatcher;

class Router {
  private $chainInvoker;
  private $routeCollector;
  private $dispatcherFactory;

  private $patterns = [];

  private $pathStack = ['/'];
  private $handlerStack = [[]];

  /**
   * Creates a new Router instance with default settings
   */
  public function __construct() {
    $this->routeCollector = new RouteCollector(new StdRouteParser, new GCBasedDataGenerator);
    $this->dispatcherFactory = new DispatcherFactory\FRDispatcherFactory(GCBasedDispatcher::class);
  }

  /**
   * Sets the FastRoute\RouteCollector instance to use, including RouteParser and DataGenerator
   * @param RouteCollector $routeCollector
   * @return Router $this
   */
  public function setRouteCollector(RouteCollector $routeCollector) {
    $this->routeCollector = $routeCollector;
    return $this;
  }

  /**
   * Gets the current RouteCollector
   * @return RouteCollector
   */
  public function getRouteCollector() {
    return $this->routeCollector;
  }

  /**
   * Sets the factory used to create new dispatchers. Used primarily to facilitate using alternate RouteCollectors
   * @param DispatcherFactoryInterface $dispatcherFactory
   * @return Router $this
   */
  public function setDispatcherFactory(DispatcherFactoryInterface $factory) {
    $this->dispatcherFactory = $factory;
    return $this;
  }

  /**
   * Gets the current DispatcherFactoryInterface
   * @return DispatcherFactoryInterface
   */
  public function getDispatcherFactory() {
    return $this->dispatcherFactory;
  }

  /**
   * Sets the base path this router serves from
   * @param  string $path
   * @return Router $this
   */
  public function setBasePath($path) {
    $this->pathStack[0] = $path;
    return $this;
  }

  /**
   * Sets the base list of handlers to be applied to every request
   * @param  callable|HandlerInterface... $handlers
   * @return Router $this
   */
  public function useHandlers(...$handlers) {
    $this->handlerStack[0] = $this->wrapHandlers($handlers);
    return $this;
  }

  /**
   * Registers a named path pattern.
   *
   * For example, setPattern('id', '\d+') allows you to write
   * /users/{id} or /users/{userid:id} and have the pattern expand to
   * /users/{id:\d+} and /users/{userid:\d+} respectively.
   * 
   * @param string $name    The name of the pattern
   * @param string $pattern The regular expression
   */
  public function setPattern($name, $pattern) {
    $this->patterns[$name] = $this->stripPatternDelimiters($pattern);
  }

  /**
   * Adds a GET route
   * @param  string $path
   * @param  callable|HandlerInterface... $handlers
   * @return Router $this
   */
  public function get($path, ...$handlers) {
    $this->addRoute('GET', $path, ...$handlers);
    return $this;
  }

  /**
   * Adds a POST route
   * @param  string $path
   * @param  callable|HandlerInterface... $handlers
   * @return Router $this
   */
  public function post($path, ...$handlers) {
    $this->addRoute('POST', $path, ...$handlers);
    return $this;
  }

  /**
   * Adds a PUT route
   * @param  string $path
   * @param  callable|HandlerInterface... $handlers
   * @return Router $this
   */
  public function put($path, ...$handlers) {
    $this->addRoute('PUT', $path, ...$handlers);
    return $this;
  }

  /**
   * Adds a DELETE route
   * @param  string $path
   * @param  callable|HandlerInterface... $handlers
   * @return Router $this
   */
  public function delete($path, ...$handlers) {
    $this->addRoute('DELETE', $path, ...$handlers);
    return $this;
  }

  /**
   * Adds a PATCH route
   * @param  string $path
   * @param  callable|HandlerInterface... $handlers
   * @return Router $this
   */
  public function patch($path, ...$handlers) {
    $this->addRoute('PATCH', $path, ...$handlers);
    return $this;
  }

  /**
   * Adds a HEAD route
   * @param  string $path
   * @param  callable|HandlerInterface... $handlers
   * @return Router $this
   */
  public function head($path, ...$handlers) {
    $this->addRoute('HEAD', $path, ...$handlers);
    return $this;
  }

  /**
   * Adds an OPTIONS route
   * @param  string $path
   * @param  callable|HandlerInterface... $handlers
   * @return Router $this
   */
  public function options($path, ...$handlers) {
    $this->addRoute('HEAD', $path, ...$handlers);
    return $this;
  }

  /**
   * Groups a set of routes under a common path prefix and set of handlers.
   * 
   * @param  string $path
   * @param  callable $init Should be a callable(Router) which is used to register sub-routes.
   * @param  callable|HandlerInterface... $handlers
   * @return Router $this
   */
  public function group($path, callable $init, ...$handlers) {
    array_push($this->pathStack, $path);
    array_push($this->handlerStack, $handlers);
    call_user_func($init, $this);
    array_pop($this->handlerStack);
    array_pop($this->pathStack);
    return $this;
  }

  /**
   * Generically add a route
   * @param  string $method
   * @param  string $path
   * @param  callable|HandlerInterface... $handlers
   * @return Router $this
   */
  public function addRoute($method, $path, ...$handlers) {
    list($path, $handlers) = $this->ungroup($path, $handlers);
    $path = $this->expandPathPatterns($path);

    $this->getRouteCollector()->addRoute($method, $path, $this->wrapHandlers($handlers));
    return $this;
  }

  /**
   * Dispatch the correct set of handlers based on the matching HTTP method and request path
   * @param  string $method
   * @param  string $path
   * @return mixed
   */
  public function dispatch($method, $path) {
    $dispatcher = $this->getDispatcher();
    $info = $dispatcher->dispatch(strtoupper($method), $path);
    switch ($info[0]) {
      case Dispatcher::NOT_FOUND:
        throw new RouteNotFoundException($method, $path);
      case Dispatcher::METHOD_NOT_ALLOWED:
        throw new MethodNotAllowedException($method, $path, $info[1]);
    }

    list($_, $handlers, $data) = $info;

    $context = new Context($method, $path, $data, $handlers);
    return $context->handleNext();
  }


  /** Gets a Dispatcher to do route matching */
  private function getDispatcher() {
    return $this->dispatcherFactory->getDispatcher($this->routeCollector->getData());
  }

  /** Wrap a number of potentially non-Handler instances in a Handler instance if needed */
  private function wrapHandlers($handlers) {
    return array_map(function($h) {
      if ($h instanceof Handler) {
        return $h;
      }
      elseif (is_callable($h)) {
        return new Handler\CallableHandler($h);
      }
      else {
        throw new \InvalidArgumentException("Handler passed was not an instance of Patchboard\Handler or callable.");
      }
    }, (array)$handlers);
  }

  /** Normalize regex delimiters */
  private function stripPatternDelimiters($pattern) {
    if (substr($pattern, 0, 1) === substr($pattern, -1, 1)) {
      return substr($pattern, 1, -1);
    }
    return $pattern;
  }

  /** Get the current list of path parts and handlers associated with the current group scope */
  private function ungroup($path, $handlers) {
    $paths = $this->pathStack;
    $paths[] = $path;
    return [
      $this->joinPaths($paths),
      array_merge(array_merge([], ...$this->handlerStack), $handlers)
    ];
  }

  /** Join a list of path parts into a single path */
  private function joinPaths($paths) {
    return str_replace('//', '/', '/' . implode('/', array_map(function($p) {
      return trim($p, '/ ');
    }, $paths)));
  }

  /** Replace {x} and {x:y} patterns with {x:$x} and {x:$y} where $x and $y are named regex patterns */
  private function expandPathPatterns($path) {
    $path = preg_replace_callback('/\{(\w+?)\}/', function($m) {
      if (array_key_exists($m[1], $this->patterns)) {
        return '{' . $m[1] . ':' . $this->patterns[$m[1]] . '}';
      }
      return $m[0];
    }, $path);
    $path = preg_replace_callback('/\{(\w+?):(\w+?)\}/', function($m) {
      if (array_key_exists($m[2], $this->patterns)) {
        return '{' . $m[1] . ':' . $this->patterns[$m[2]] . '}';
      }
    }, $path);
    return $path;
  }
}