<?php
namespace Patchboard;

interface HandlerInvoker {
  public function invoke($handler, $data);
}