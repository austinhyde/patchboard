<?php
namespace Patchboard;

class Context {
  private $method;
  private $path;
  private $pathParams;
  private $handlers;
  private $attributes;

  private $handlerIndex;

  public function __construct($method, $path, $pathParams, $handlers) {
    $this->method = $method;
    $this->path = $path;
    $this->pathParams = $pathParams;
    $this->attributes = array();

    $this->handlers = $handlers;
    $this->handlerIndex = 0;
  }

  /**
   * The HTTP method of the matched route
   * @return string
   */
  public function getMethod() {
    return $this->method;
  }

  /**
   * The matched path
   * @return string
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Path variables extracted from the matched path
   * @return string
   */
  public function getPathParams() {
    return $this->pathParams;
  }

  /**
   * Get a specific path parameter, or a default value if it doesn't exist
   * @param  string $name
   * @param  mixed  $def
   * @return string
   */
  public function getPathParam($name, $def = null) {
    return array_key_exists($name, $this->pathParams) ? $this->pathParams[$name] : $def;
  }

  /**
   * Sets an arbitrary attribute on the context
   * @param string $name
   * @param mixed  $value
   */
  public function setAttribute($name, $value) {
    $this->attributes[$name] = $value;
    return $this;
  }

  /**
   * Gets an attribute by its name, or a default if it doesn't exist
   * @param  string $name
   * @param  mixed  $def
   * @return mixed
   */
  public function getAttribute($name, $def = null) {
    return $this->hasAttribute($name) ? $this->attributes[$name] : $def;
  }

  /**
   * Checks if the given attribute exists
   * @param  string  $name
   * @return boolean
   */
  public function hasAttribute($name) {
    return array_key_exists($name, $this->attributes);
  }

  /**
   * Gets all registered attributes as an associative array
   * @return array
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * Checks if there's another handler to execute
   * @return boolean
   */
  public function hasNextHandler() {
    return $this->handlerIndex <= count($this->handlers) - 1;
  }

  /**
   * Calls the next handler and returns its result
   * @return mixed
   */
  public function handleNext() {
    $r = null;

    while ($this->hasNextHandler() && $r === null) {
      $index = $this->handlerIndex++;
      $r = $this->handlers[$index]->handle($this);
    }

    return $r;
  }
}