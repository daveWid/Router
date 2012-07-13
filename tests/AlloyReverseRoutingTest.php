<?php
/**
 * Alloy Router generic URL tests
 */
class AlloyReverseRoutingTest extends \PHPUnit_Framework_TestCase
{
	public $router;

    public function setUp()
    {
        parent::setUp();
		$this->router = new \Alloy\Router;
    }
    
    public function testUrlMVCAction()
    {
        $router = $this->router;
        $router->route('mvc', '<:controller>/<:action>.<:format>');
        $router->route('mvc_item', '<:controller>/<:action>/<#id>.<:format>');
        $router->route('blog_post', '<:dir>/<#year>/<#month>/<:slug>');
        
        $url = $router->url('mvc', array('controller' => 'user', 'action' => 'profile', 'format' => 'html'));
        
        $this->assertEquals("user/profile.html", $url);
    }
    
    public function testUrlMVCItem()
    {
        $router = $this->router;
        $router->route('mvc', '<:controller>/<:action>.<:format>');
        $router->route('mvc_item', '<:controller>/<:action>/<#id>.<:format>');
        $router->route('blog_post', '<:dir>/<#year>/<#month>/<:slug>');
        
        $url = $router->url('mvc_item', array('controller' => 'blog', 'action' => 'show', 'id' => 55, 'format' => 'json'));
        
        $this->assertEquals("blog/show/55.json", $url);
    }
    
    public function testUrlBlogPost()
    {
        $router = $this->router;
        $router->route('mvc', '<:controller>/<:action>.<:format>');
        $router->route('mvc_item', '<:controller>/<:action>/<#id>.<:format>');
        $router->route('blog_post', '<:dir>/<#year>/<#month>/<:slug>');
        
        $url = $router->url('blog_post', array('dir' => 'blog', 'year' => 2009, 'month' => '10', 'slug' => 'blog-post-title'));
        
        $this->assertEquals("blog/2009/10/blog-post-title", $url);
    }
    
    public function testUrlBlogPostDefaults()
    {
        $router = $this->router;
        $router->route('blog_post_x', '<:dir>/<#year>/<#month>/<:slug>')
                ->defaults(array('dir' => 'blog'));
        
        // Do not supply 'dir', expect the defined default 'dir' => 'blog' in the route definition to fill it in
        $url = $router->url('blog_post_x', array('year' => 2009, 'month' => '10', 'slug' => 'blog-post-title'));
        
        $this->assertEquals("blog/2009/10/blog-post-title", $url);
    }
    
    public function testUrlBlogPostException()
    {
        $router = $this->router;
        $router->route('blog_post', '<:dir>/<#year>/<#month>/<:slug>');
        
        try {
            // Do not supply 'dir' or 'slug', expect exception to be raised
            $url = $router->url('blog_post', array('year' => 2009, 'month' => '10'));
        } catch(Exception $e) {
            return;
        }
        
        $this->fail("Expected exception, none raised.");
    }
    
    public function testUrlOptionalParamsNotInUrlWhenValueNotSet()
    {
        // Route with escape character before the dot '.'
        $this->router->route('test', '<:controller>(.<:format>)')
                ->defaults(array('format' => 'html'));
        
        // Use default format (URL should not have '.html', because it is not set and it is default)
        $url = $this->router->url('test', array('controller' => 'events'));
        $this->assertEquals("events", $url);
        
        // Use default format (URL SHOULD have '.html', because it is set)
        $url = $this->router->url('test', array('controller' => 'events', 'format' => 'html'));
        $this->assertEquals("events.html", $url);
        
        // Use custom format (URL SHOULD have '.xml' because it IS set and it IS NOT default)
        $url = $this->router->url('test', array('controller' => 'events', 'format' => 'xml'));
        $this->assertEquals("events.xml", $url);
    }
    
    
    /**
     * Static route - no matched parameters
     */
    public function testUrlStatic()
    {
        $this->router->route('login', '/user/login');
        
        // Get static URL with no parameters
        $url = $this->router->url('login');
        $this->assertEquals("user/login", $url);
    }
    
    
    public function testUrlPlusSignIsNotEncoded()
    {
        $router = $this->router;
        $router->route('match', '<:match>');
        
        $url = $router->url('match', array('match' => 'blog post'));
        
        $this->assertEquals("blog+post", $url);
    }
}