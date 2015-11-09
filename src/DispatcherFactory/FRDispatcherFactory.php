<?php
namespace Patchboard\DispatcherFactory;
use Patchboard\DispatcherFactoryInterface;

/**
 * A factory for creating FastRoute-style Dispatchers - i.e. A constructor which takes an array of route data.
 */
class FRDispatcherFactory implements DispatcherFactoryInterface {
  private $className;

  public function __construct($className) {
    if (!class_exists($className)) {
      throw new \InvalidArgumentException("Cannot create a FRDispatcherFactory for non-existent class $className");
    }
    $this->className = $className;
  }

  /**
   * Creates a new dispatcher based on the given routeData
   * @param  array $routeData
   * @return FastRoute\Dispatcher
   */
  public function getDispatcher($data) {
    $c = $this->className;
    return new $c($data);
  }
}