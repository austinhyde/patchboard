<?php
namespace Patchboard\DispatcherFactory;
use Patchboard\DispatcherFactory;

class FRDispatcherFactory implements DispatcherFactory {
  private $className;

  public function __construct($className) {
    if (!class_exists($className)) {
      throw new \InvalidArgumentException("Cannot create a FRDispatcherFactory for non-existent class $className");
    }
    $this->className = $className;
  }

  public function getDispatcher($data) {
    $c = $this->className;
    return new $c($data);
  }
}