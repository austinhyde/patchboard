<?php
namespace Patchboard\Handler;
use Patchboard\HandlerInterface;
use Patchboard\Context;

/**
 * Wraps an arbitrary callable
 */
class CallableHandler implements HandlerInterface {
  private $delegate;

  public function __construct(callable $delegate) {
    $this->delegate = $delegate;
  }

  public function handle(Context $context) {
    return call_user_func($this->delegate, $context);
  }
}