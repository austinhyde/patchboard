<?php
namespace Patchboard;

interface DispatcherFactory {
  public function getDispatcher($data);
}