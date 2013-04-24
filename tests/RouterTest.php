<?php

/**
 * Alloy Router tests
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
	public $router;

	public function setUp()
	{
		parent::setUp();
		$this->router = new \Alloy\Router;

		// Add a static and regex route
		$this->router->route('home', '/welcome')->defaults(array(
			'controller' => 'welcome',
			'action' => 'home'
		));

		$this->router->route('mvc', '/<:controller>(/<:action>(/<#id>))')->defaults(array(
			'action' => 'index',
			'id' => null
		));
	}

	public function testAddingRouteReturnsRoute()
	{
		$route = $this->router->route('testing', '/testing');
		$this->assertInstanceOf("\Alloy\Route", $route);
	}

	public function testGetAllRoutes()
	{
		$routes = $this->router->routes();

		$this->assertInternalType('array', $routes);
		$this->assertSame(2, count($routes));
	}

	/**
	 * @expectedException \OutOfBoundsException
	 */
	public function testMatchWithNoRoutesThrowsException()
	{
		$route = new \Alloy\Router;
		$route->match('GET', "/failing/match");
	}

	public function testMatchStatic()
	{
		$expected = array(
			'controller' => 'welcome',
			'action' => 'home'
		);

		$params = $this->router->match("GET", "/welcome");

		// Have to to equals because the keys aren't in the same order
		$this->assertEquals($expected, $params);
	}

	public function testMatchRegex()
	{
		$expected = array(
			'controller' => 'testing',
			'action' => 'index',
			'id' => null
		);

		$params = $this->router->match("GET", '/testing');

		// Have to to equals because the keys aren't in the same order
		$this->assertEquals($expected, $params);
	}

	public function testAdvancedRegex()
	{
		$router = new \Alloy\Router;
		$router->route('regex', '/api/<:controller|first|second|third|fourth>(/<#id>)');

		$this->assertNotEmpty($router->match("GET", '/api/first'));
		$this->assertNotEmpty($router->match("GET", '/api/fourth/2'));
		$this->assertEmpty($router->match("GET", '/fifth'));
	}

	public function testEmptyParamsWithNoMatchingRoutes()
	{
		$params = $this->router->match("GET", "/");
		$this->assertEmpty($params);
	}

	public function testReset()
	{
		$this->router->reset();
		$this->assertSame(0, count($this->router->routes()));
	}

}
