<?php
namespace Patchboard\Test;
use Patchboard\Router;
use Patchboard\Context;

class RouterTest extends \PHPUnit_Framework_TestCase {
  /** @dataProvider provideRoutes */
  public function testSimpleRoutes($method) {
    static $i = 0;
    $r = new Router;

    $r->$method('/', function(Context $ctx) use ($i) {
      $this->assertEquals([], $ctx->getPathParams());
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
    $h = function(Context $ctx) use (&$i) { $i += 1; };

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

    $r->get('/{foo}/{bar}', function(Context $ctx) {
      return $ctx;
    });

    $ctx = $r->dispatch('GET', '/asdf/qwer');
    $this->assertInstanceOf(Context::class, $ctx);
    $this->assertEquals([
      'foo' => 'asdf',
      'bar' => 'qwer'
    ], $ctx->getPathParams());
  }

  public function testCustomPattern() {
    $r = new Router;
    $r->setPattern('as', '/a+/');
    $r->setPattern('bs', 'b+');

    $r->get('/{as}/{bs}', function(Context $ctx) {
      return $ctx;
    });

    $r->get('/{one:bs}/{two:as}', function(Context $ctx) {
      return $ctx;
    });

    $d1 = $r->dispatch('GET', '/aaaa/bb');
    $this->assertInstanceOf(Context::class, $d1);
    $this->assertEquals(['as' => 'aaaa', 'bs' => 'bb'], $d1->getPathParams());

    $d2 = $r->dispatch('GET', '/bbbb/aaa');
    $this->assertInstanceOf(Context::class, $d2);
    $this->assertEquals(['one' => 'bbbb', 'two' => 'aaa'], $d2->getPathParams());
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


  public function testNestedHandlers() {
    $r = new Router;
    $r->get('/', function(Context $ctx) {
      return 'a' . $ctx->handleNext() . 'e';
    }, function(Context $ctx) {
      return 'b' . $ctx->handleNext() . 'd';
    }, function(Context $ctx) {
      return 'c';
    }, function(Context $ctx) {
      return 'f';
    });

    $result = $r->dispatch('GET', '/');
    // last handler is not executed because those before it *did* return a value
    $this->assertEquals('abcde', $result);
  }
}