<?php
namespace Patchboard;

interface HandlerInterface {
  /**
   * Handle a request
   * @param  Context  Metadata about the currently routed request
   * @return mixed
   */
  public function handle(Context $context);
}