<?php
namespace Patchboard\HandlerInvoker;
use Patchboard\HandlerInvoker;

class NamespacedHandlerInvoker implements HandlerInvoker {
  private $nsPrefix;
  private $delegate;

  public function __construct($nsPrefix, HandlerInvoker $delegate) {
    $this->nsPrefix = trim('\\', $nsPrefix) . '\\';
    $this->delegate = $delgate;
  }

  public function invoke($handler, $data) {
    if (is_string($handler)) {
      $handler = $this->nsPrefix . $handler;
    } elseif (is_array($handler) && is_string($handler[0])) {
      $handler[0] = $this->nsPrefix . $handler[0];
    }
    return $this->delegate->invoke($handler, $data);
  }
}