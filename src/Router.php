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

  private $pathStack = [];
  private $handlerStack = [];

  public function __construct(HandlerInvoker $handlerInvoker = null,
                              ChainInvoker $chainInvoker = null,
                              RouteCollector $routeCollector = null,
                              DispatcherFactory $dispatcherFactory = null) {
    $handlerInvoker = $handlerInvoker ?: new HandlerInvoker\StdInvoker;
    $this->chainInvoker = $chainInvoker ?: new ChainInvoker($handlerInvoker);
    $this->routeCollector = $routeCollector ?: new RouteCollector(new StdRouteParser, new GCBasedDataGenerator);
    $this->dispatcherFactory = $dispatcherFactory ?: new DispatcherFactory\FRDispatcherFactory(GCBasedDispatcher::class);
  }

  public function getChainInvoker() {
    return $this->chainInvoker;
  }
  public function setChainInvoker(ChainInvoker $chainInvoker) {
    $this->chainInvoker = $chainInvoker;
    return $this;
  }

  public function getRouteCollector() {
    return $this->routeCollector;
  }
  public function getDispatcherFactory() {
    return $this->dispatcherFactory;
  }
  public function setDispatcherFactory(DispatcherFactory $factory) {
    $this->dispatcherFactory = $factory;
    return $this;
  }
  public function getDispatcher() {
    return $this->dispatcherFactory->getDispatcher($this->routeCollector->getData());
  }

  public function setPattern($name, $pattern) {
    $this->patterns[$name] = $this->stripPatternDelimiters($pattern);
  }

  public function get($path, ...$handlers) {
    $this->addRoute('GET', $path, ...$handlers);
    return $this;
  }

  public function post($path, ...$handlers) {
    $this->addRoute('POST', $path, ...$handlers);
    return $this;
  }

  public function put($path, ...$handlers) {
    $this->addRoute('PUT', $path, ...$handlers);
    return $this;
  }

  public function delete($path, ...$handlers) {
    $this->addRoute('DELETE', $path, ...$handlers);
    return $this;
  }

  public function patch($path, ...$handlers) {
    $this->addRoute('PATCH', $path, ...$handlers);
    return $this;
  }

  public function head($path, ...$handlers) {
    $this->addRoute('HEAD', $path, ...$handlers);
    return $this;
  }

  public function group($path, $init, ...$handlers) {
    array_push($this->pathStack, $path);
    array_push($this->handlerStack, $handlers);
    call_user_func($init, $this);
    array_pop($this->handlerStack);
    array_pop($this->pathStack);
    return $this;
  }

  public function addRoute($method, $path, ...$handlers) {
    list($path, $handlers) = $this->ungroup($path, $handlers);
    $path = $this->expandPathPatterns($path);

    $this->getRouteCollector()->addRoute($method, $path, $handlers);
    return $this;
  }

  public function dispatch($method, $path) {
    $dispatcher = $this->getDispatcher();
    $info = $dispatcher->dispatch(strtoupper($method), $path);
    switch ($info[0]) {
      case Dispatcher::NOT_FOUND:
        throw new RouteNotFoundException($method, $path);
      case Dispatcher::METHOD_NOT_ALLOWED:
        throw new MethodNotAllowedException($method, $path, $info[1]);
    }

    $handlers = $info[1];
    $data = $info[2];

    return $this->getChainInvoker()->invokeChain($handlers, $data);
  }

  private function stripPatternDelimiters($pattern) {
    if (substr($pattern, 0, 1) === substr($pattern, -1, 1)) {
      return substr($pattern, 1, -1);
    }
    return $pattern;
  }
  private function ungroup($path, $handlers) {
    $paths = $this->pathStack;
    $paths[] = $path;
    return [
      $this->joinPaths($paths),
      array_merge(array_merge([], ...$this->handlerStack), $handlers)
    ];
  }
  private function joinPaths($paths) {
    return '/' . implode('/', array_map(function($p) {
      return trim($p, '/ ');
    }, $paths));
  }
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
      throw new \InvalidArgumentException("Path pattern {$m[2]} was not defined");
    }, $path);
    return $path;
  }
}