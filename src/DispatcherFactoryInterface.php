<?php
namespace Patchboard;

interface DispatcherFactoryInterface {
  public function getDispatcher($data);
}