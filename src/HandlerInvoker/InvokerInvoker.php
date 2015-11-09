<?php
namespace Patchboard\HandlerInvoker;
use Patchboard\HandlerInvoker;
use Invoker\Invoker;

class InvokerInvoker implements HandlerInvoker {
  private $invoker;

  public function __construct(Invoker $invoker) {
    $this->invoker = $invoker;
  }

  public function invoke($handler, $data) {
    return $this->invoker->call($handler, $data);
  }
}