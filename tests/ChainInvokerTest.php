<?php
namespace Patchboard\Test;
use Patchboard\ChainInvoker;

class ChainInvokerTest extends \PHPUnit_Framework_TestCase {
  public function testShortCircuitsOnReturn() {
    $ci = new ChainInvoker;
    $result = $ci->invokeChain([
      function() { return 1; },
      function() { return 2; }
    ], []);
    $this->assertEquals(1, $result);

    $result = $ci->invokeChain([
      function() { },
      function() { return 2; }
    ], []);
    $this->assertEquals(2, $result);
  }
}