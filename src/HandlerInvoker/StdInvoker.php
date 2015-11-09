<?php
namespace Patchboard\HandlerInvoker;
use Patchboard\HandlerInvoker;

class StdInvoker implements HandlerInvoker {
  public function invoke($handler, $data) {
    if (!is_callable($handler)) {
      throw new \InvalidArgumentException("Handler invoked by StdInvoker was not callable");
    }
    return call_user_func($handler, $data);
  }
}