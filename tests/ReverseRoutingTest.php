<?php

/**
 * Alloy Router generic URL tests
 */
class ReverseRoutingTest extends \PHPUnit_Framework_TestCase
{
	public $router;

	public function setUp()
	{
		parent::setUp();
		$this->router = new \Alloy\Router;

		$this->router->route('mvc', '<:controller>/<:action>.<:format>');
		$this->router->route('mvc_item', '<:controller>/<:action>/<#id>.<:format>');
		$this->router->route('blog_post', '<:dir>/<#year>/<#month>/<:slug>');
		$this->router->route('optional', '<:controller>(/<:action>(/<:id|\d+>))');
	}

	public function testUrlStatic()
	{
		$this->router->route('login', '/user/login');

		$url = $this->router->url('login');
		$this->assertEquals("/user/login", $url);
	}

	public function testUrlMVCAction()
	{
		$params = array(
			'controller' => 'user',
			'action' => 'profile',
			'format' => 'html'
		);

		$url = $this->router->url('mvc', $params);
		$this->assertEquals("user/profile.html", $url);
	}

	public function testUrlMVCItem()
	{
		$params = array(
			'controller' => 'blog',
			'action' => 'show',
			'id' => 55,
			'format' => 'json'
		);

		$url = $this->router->url('mvc_item', $params);
		$this->assertEquals("blog/show/55.json", $url);
	}

	public function testUrlBlogPost()
	{
		$params = array(
			'dir' => 'blog',
			'year' => 2009,
			'month' => '10',
			'slug' => 'blog-post-title'
		);

		$url = $this->router->url('blog_post', $params);
		$this->assertEquals("blog/2009/10/blog-post-title", $url);
	}

	public function testUrlBlogPostDefaults()
	{
		$router = $this->router;
		$router->route('blog_post_x', '<:dir>/<#year>/<#month>/<:slug>')
				->defaults(array('dir' => 'blog'));

		$params = array(
			'year' => 2009,
			'month' => '10',
			'slug' => 'blog-post-title'
		);

		// Do not supply 'dir', expect the defined default 'dir' => 'blog' in the route definition to fill it in
		$url = $router->url('blog_post_x', $params);
		$this->assertEquals("blog/2009/10/blog-post-title", $url);
	}

	public function testUrlRemoveEscapeCharacters()
	{
		// Route with escape character before the dot '.'
		$this->router->route('index_action', '<:action>.<:format>')
				->defaults(array('format' => 'html'));

		// Use default format
		$url = $this->router->url('index_action', array('action' => 'new'));
		$this->assertEquals("new.html", $url);

		// Use custom format
		$url = $this->router->url('index_action', array('action' => 'new', 'format' => 'xml'));
		$this->assertEquals("new.xml", $url);
	}

	public function testUrlOptionalParamsNotInUrlWhenValueNotSet()
	{
		// Route with escape character before the dot '.'
		$this->router->route('test', '<:controller>(.<:format>)')
				->defaults(array('format' => 'html'));

		// Use default format (URL should not have '.html', because it is not set and it is default)
		$url = $this->router->url('test', array('controller' => 'events'));
		$this->assertEquals("events.html", $url);

		// Use custom format (URL SHOULD have '.xml' because it IS set and it IS NOT default)
		$url = $this->router->url('test', array('controller' => 'events', 'format' => 'xml'));
		$this->assertEquals("events.xml", $url);
	}

	public function testUrlPlusSignIsNotEncoded()
	{
		$router = $this->router;
		$router->route('match', '<:match>');

		$url = $router->url('match', array('match' => 'blog post'));

		$this->assertEquals("blog+post", $url);
	}

	public function testOptionalParamsRoute()
	{
		$params = array(
			'controller' => "user",
			'action' => "view",
			'id' => 500,
		);

		$url = $this->router->url('optional', $params);
		$this->assertSame('user/view/500', $url);
	}

	/**
	 * @expectedException \UnexpectedValueException
	 */
	public function testDefaultParamMissingThrowsException()
	{
		$route = new \Alloy\Route("<:controller>");
		$route->url(array(), "GET");
	}

}
