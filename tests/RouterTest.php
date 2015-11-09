<?php
namespace Patchboard\Test;
use Patchboard\Router;

class RouterTest extends \PHPUnit_Framework_TestCase {
  /** @dataProvider provideRoutes */
  public function testSimpleRoutes($method) {
    static $i = 0;
    $r = new Router;

    $r->$method('/', function($data) use ($i) {
      $this->assertEquals([], $data);
      return $i;
    });

    $result = $r->dispatch($method, '/');
    $this->assertEquals($i, $result);
    $i++;
  }
  public function provideRoutes() {
    return [
      ['GET'],
      ['PUT'],
      ['POST'],
      ['DELETE'],
      ['PATCH'],
      ['HEAD'],
    ];
  }

  public function testMultipleHandlers() {
    $r = new Router;
    $i = 0;
    $h = function($data) use (&$i) { $i += 1; };

    $r->get('/', $h, $h, $h);

    $result = $r->dispatch('GET', '/');
    $this->assertEquals(null, $result);
    $this->assertEquals(3, $i);
  }

  public function testGrouped() {
    $r = new Router;
    $i = 0;
    $h = function($data) use (&$i) { $i += 1; };

    $r->group('/foo', function($r) use ($h) {
      $r->get('/bar', $h);
    }, $h, $h);

    $r->dispatch('GET', '/foo/bar');
    $this->assertEquals(3, $i);
  }

  public function testWildcards() {
    $r = new Router;

    $r->get('/{foo}/{bar}', function($data) {
      return $data;
    });

    $data = $r->dispatch('GET', '/asdf/qwer');
    $this->assertEquals([
      'foo' => 'asdf',
      'bar' => 'qwer'
    ], $data);
  }

  public function testCustomPattern() {
    $r = new Router;
    $r->setPattern('as', '/a+/');
    $r->setPattern('bs', 'b+');

    $r->get('/{as}/{bs}', function($data) {
      return $data;
    });

    $r->get('/{one:bs}/{two:as}', function($data) {
      return $data;
    });

    $d1 = $r->dispatch('GET', '/aaaa/bb');
    $this->assertEquals(['as' => 'aaaa', 'bs' => 'bb'], $d1);

    $d2 = $r->dispatch('GET', '/bbbb/aaa');
    $this->assertEquals(['one' => 'bbbb', 'two' => 'aaa'], $d2);
  }

  public function testNotFound() {
    $r = new Router;
    $r->get('/foo');

    try {
      $r->dispatch('GET', '/bar');
      $this->fail("Expected a Patchboard\RouteNotFoundException to be thrown");
    } catch (\Patchboard\RouteNotFoundException $ex) {
      $this->assertEquals('GET', $ex->getMethod());
      $this->assertEquals('/bar', $ex->getPath());
      return;
    }
  }

  public function testMethodNotAllowed() {
    $r = new Router;
    $r->get('/foo');

    try {
      $r->dispatch('POST', '/foo');
      $this->fail("Expected a Patchboard\MethodNotAllowedException to be thrown");
    } catch (\Patchboard\MethodNotAllowedException $ex) {
      $this->assertEquals('POST', $ex->getMethod());
      $this->assertEquals('/foo', $ex->getPath());
      $this->assertEquals(['GET'], $ex->getAllowed());
      return;
    }
  }

  public function testCustomHandlerInvoker() {
    $r = new Router(new MockHandlerInvoker);

    $r->get('/{one}/{two}', function($a, $b) {
      return [$a, $b];
    });

    $this->assertEquals(['foo','bar'], $r->dispatch('GET', '/foo/bar'));
  }
}

class MockHandlerInvoker implements \Patchboard\HandlerInvoker {
  public function invoke($handler, $data) {
    return call_user_func_array($handler, array_values($data));
  }
}