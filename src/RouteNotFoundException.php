<?php
namespace Patchboard;

class RouteNotFoundException extends \Exception {
  private $method;
  private $path;

  public function __construct($method, $path) {
    $this->method = $method;
    $this->path = $path;
    parent::__construct("Route was not found for $method $path");
  }

  public function getMethod() {
    return $this->method;
  }
  public function getPath() {
    return $this->path;
  }
}