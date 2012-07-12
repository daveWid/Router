<?php

/**
 * Alloy Route testing
 */
class RouteTest extends \PHPUnit_Framework_TestCase
{
	public $route;

	public function setUp()
	{
		parent::setUp();
		$this->route = new \Alloy\Route('contact', "/contact");
	}

	public function testStaticCheck()
	{
		$regex = new \Alloy\Route('regex', '/<:action>');

		$this->assertTrue($this->route->isStatic());
		$this->assertFalse($regex->isStatic());
	}

	public function testDefaultsWithConstructor()
	{
		$data = array('constructor' => "static", 'action' => 'testing');

		$route = new \Alloy\Route('testing', '/testing', $data);
		$this->assertSame($data, $route->defaults());
	}

	public function testDefaults()
	{
		$data = array('constructor' => "static", 'action' => 'testing');

		$this->route->defaults($data);
		$this->assertSame($data, $this->route->defaults());
	}

	public function testGetName()
	{
		$this->assertSame('contact', $this->route->name());
	}

	public function testSetName()
	{
		$this->route->name('changed');
		$this->assertSame('changed', $this->route->name());
	}

	public function testRoute()
	{
		$this->assertSame("/contact", $this->route->route());
	}

	/**
	 * @dataProvider httpVerbs 
	 */
	public function testMethodDefaults($method, $params)
	{
		$this->route->methodDefaults($method, $params);
		$this->assertSame($params, $this->route->methodDefaults($method));
	}

	/**
	 * @dataProvider httpVerbs 
	 */
	public function testMethodsConvienceLinks($method, $params)
	{
		$func = strtolower($method);
		$this->route->{$func}($params);

		$this->assertSame($params, $this->route->methodDefaults($method));
	}

	public function httpVerbs()
	{
		return array(
			array("GET", array('action' => "view")),
			array("PUT", array('action' => "insert")),
			array("POST", array('action' => "update")),
			array("DELETE", array('action' => "remove"))
		);
	}

	public function testCondition()
	{
		$callback = function(){
			return true;
		};

		$this->route->condition($callback);
		$this->assertSame($callback, $this->route->condition());
	}

	/**
	 * @expectedException \InvalidArgumentException 
	 */
	public function testInvalidCondition()
	{
		$this->route->condition('notacallback');
	}

	public function testAfterMatch()
	{
		$callback = function(){
			return true;
		};

		$this->route->afterMatch($callback);
		$this->assertSame($callback, $this->route->afterMatch());
	}

	/**
	 * @expectedException \InvalidArgumentException 
	 */
	public function testInvalidAfterMatch()
	{
		$this->route->afterMatch('notacallback');
	}

}
