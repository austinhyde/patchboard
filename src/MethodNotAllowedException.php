<?php
namespace Patchboard;

class MethodNotAllowedException extends \Exception {
  private $method;
  private $path;
  private $allowed;

  public function __construct($method, $path, $allowed) {
    $this->method = $method;
    $this->path = $path;
    $this->allowed = $allowed;
    parent::__construct("Method $method is not allowed for $path, valid methods are " . implode(', ', $allowed));
  }

  public function getMethod() {
    return $this->method;
  }
  public function getPath() {
    return $this->path;
  }
  public function getAllowed() {
    return $this->allowed;
  }
}