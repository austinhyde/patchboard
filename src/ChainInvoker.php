<?php
namespace Patchboard;

class ChainInvoker {
  public function __construct(HandlerInvoker $handlerInvoker = null) {
    $this->handlerInvoker = $handlerInvoker ?: new HandlerInvoker\StdInvoker;
  }

  public function getHandlerInvoker() {
    return $this->handlerInvoker;
  }
  public function setHandlerInvoker(HandlerInvoker $handlerInvoker) {
    $this->handlerInvoker = $handlerInvoker;
    return $this;
  }

  public function invokeChain(array $handlers, $data) {
    foreach ($handlers as $handler) {
      $r = $this->handlerInvoker->invoke($handler, $data);
      if ($r !== null) {
        return $r;
      }
    }
  }
}