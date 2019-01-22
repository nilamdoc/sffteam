<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2016, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\template\helper;

use lithium\net\http\Router;
use lithium\template\helper\Html;
use lithium\action\Request;
use lithium\action\Response;
use lithium\tests\mocks\template\MockRenderer;

class HtmlTest extends \lithium\test\Unit {

	/**
	 * Test object instance
	 *
	 * @var object
	 */
	public $html = null;

	protected $_routes = array();

	/**
	 * Initialize test by creating a new object instance with a default context.
	 */
	public function setUp() {
		$this->_routes = Router::get();
		Router::reset();
		Router::connect('/{:controller}/{:action}/{:id}.{:type}');
		Router::connect('/{:controller}/{:action}.{:type}');

		$this->context = new MockRenderer(array(
			'request' => new Request(array(
				'base' => '', 'env' => array('HTTP_HOST' => 'foo.local')
			)),
			'response' => new Response()
		));
		$this->html = new Html(array('context' => &$this->context));
	}

	/**
	 * Clean up after the test.
	 */
	public function tearDown() {
		Router::reset();
		foreach ($this->_routes as $scope => $routes) {
			Router::scope($scope, function() use ($routes) {
				foreach ($routes as $route) {
					Router::connect($route);
				}
			});
		}
		unset($this->html);
	}

	/**
	 * Tests that character set declarations render the
	 * correct character set and short meta tag.
	 */
	public function testCharset() {
		$result = $this->html->charset();
		$this->assertTags($result, array('meta' => array(
			'charset' => 'UTF-8'
		)));

		$result = $this->html->charset('utf-8');
		$this->assertTags($result, array('meta' => array(
			'charset' => 'utf-8'
		)));

		$result = $this->html->charset('UTF-7');
		$this->assertTags($result, array('meta' => array(
			'charset' => 'UTF-7'
		)));
	}

	/**
	 * Tests meta linking.
	 */
	public function testMetaLink() {
		$result = $this->html->link(
			'RSS Feed',
			array('controller' => 'posts', 'type' => 'rss'),
			array('type' => 'rss')
		);
		$this->assertTags($result, array('link' => array(
			'href' => 'regex:/.*\/posts\/index\.rss/',
			'type' => 'application/rss+xml',
			'rel' => 'alternate',
			'title' => 'RSS Feed'
		)));

		$result = $this->html->link(
			'Atom Feed', array('controller' => 'posts', 'type' => 'xml'), array('type' => 'atom')
		);
		$this->assertTags($result, array('link' => array(
			'href' => 'regex:/.*\/posts\/index\.xml/',
			'type' => 'application/atom+xml',
			'title' => 'Atom Feed',
			'rel' => 'alternate'
		)));

		$result = $this->html->link('No-existy', '/posts.xmp', array('type' => 'rong'));
		$this->assertTags($result, array('link' => array(
			'href' => 'regex:/.*\/posts\.xmp/',
			'title' => 'No-existy'
		)));

		$result = $this->html->link('No-existy', '/posts.xpp', array('type' => 'atom'));
		$this->assertTags($result, array('link' => array(
			'href' => 'regex:/.*\/posts\.xpp/',
			'type' => 'application/atom+xml',
			'title' => 'No-existy',
			'rel' => 'alternate'
		)));

		$result = $this->html->link('Favicon', array(), array('type' => 'icon'));
		$expected = array(
			'link' => array(
				'href' => 'regex:/.*favicon\.ico/',
				'type' => 'image/x-icon',
				'rel' => 'icon',
				'title' => 'Favicon'
			),
			array('link' => array(
				'href' => 'regex:/.*favicon\.ico/',
				'type' => 'image/x-icon',
				'rel' => 'shortcut icon',
				'title' => 'Favicon'
			))
		);
		$this->assertTags($result, $expected);
	}

	/**
	 * Tests <a /> elements generated by `HtmlHelper::link()`
	 */
	public function testLink() {
		$result = $this->html->link('/home');
		$expected = array('a' => array('href' => '/home'), 'regex:/\/home/', '/a');
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#');
		$expected = array('a' => array('href' => '#'), 'Next &gt;', '/a');
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#', array('escape' => true));
		$expected = array(
			'a' => array('href' => '#'),
			'Next &gt;',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#', array('escape' => 'utf-8'));
		$expected = array(
			'a' => array('href' => '#'),
			'Next &gt;',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#', array('escape' => false));
		$expected = array('a' => array('href' => '#'), 'Next >', '/a');
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#', array(
			'title' => 'to escape &#8230; or not escape?',
			'escape' => false
		));
		$expected = array(
			'a' => array('href' => '#', 'title' => 'to escape &#8230; or not escape?'),
			'Next >',
			'/a'
		);
		$this->assertTags($result, $expected);

		$result = $this->html->link('Next >', '#', array(
			'title' => 'to escape &#8230; or not escape?', 'escape' => true
		));
		$expected = array(
			'a' => array('href' => '#', 'title' => 'to escape &amp;#8230; or not escape?'),
			'Next &gt;',
			'/a'
		);
		$this->assertTags($result, $expected);
	}

	/**
	 * Tests basic JavaScript linking using the <script /> tag
	 */
	public function testScriptLinking() {
		$result = $this->html->script('script.js');
		$expected = '<script type="text/javascript" src="/js/script.js"></script>';
		$this->assertEqual($expected, $result);

		$result = $this->html->script('script');
		$expected = '<script type="text/javascript" src="/js/script.js"></script>';
		$this->assertEqual($expected, $result);

		$result = $this->html->script('scriptaculous.js?load=effects');
		$expected = '<script type="text/javascript"';
		$expected .= ' src="/js/scriptaculous.js?load=effects"></script>';
		$this->assertEqual($expected, $result);

		$result = $this->html->script('jquery-1.1.2');
		$expected = '<script type="text/javascript" src="/js/jquery-1.1.2.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('jquery-1.1.2');
		$expected = '<script type="text/javascript" src="/js/jquery-1.1.2.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('/plugin/js/jquery-1.1.2');
		$expected = '<script type="text/javascript" src="/plugin/js/jquery-1.1.2.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('/some_other_path/myfile.1.2.2.min.js');
		$expected = '<script type="text/javascript"';
		$expected .= ' src="/some_other_path/myfile.1.2.2.min.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('some_other_path/myfile.1.2.2.min.js');
		$expected = '<script type="text/javascript"';
		$expected .= ' src="/js/some_other_path/myfile.1.2.2.min.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('some_other_path/myfile.1.2.2.min');
		$expected = '<script type="text/javascript"';
		$expected .= ' src="/js/some_other_path/myfile.1.2.2.min.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('http://example.com/jquery.js');
		$expected = '<script type="text/javascript" src="http://example.com/jquery.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script('//example.com/jquery.js');
		$expected = '<script type="text/javascript" src="//example.com/jquery.js"></script>';
		$this->assertEqual($result, $expected);

		$result = $this->html->script(array('prototype', 'scriptaculous'));
		$this->assertPattern(
			'/^\s*<script\s+type="text\/javascript"\s+src=".*js\/prototype\.js"[^<>]*><\/script>/',
			$result
		);
		$this->assertPattern('/<\/script>\s*<script[^<>]+>/', $result);
		$this->assertPattern(
			'/<script\s+type="text\/javascript"\s+src=".*js\/scriptaculous\.js"[^<>]*>' .
			'<\/script>\s*$/',
			$result
		);

		$result = $this->html->script("foo", array(
			'async' => true, 'defer' => true, 'onload' => 'init()'
		));

		$this->assertTags($result, array('script' => array(
			'type' => 'text/javascript',
			'src' => '/js/foo.js',
			'async' => 'async',
			'defer' => 'defer',
			'onload' => 'init()'
		)));
	}

	/**
	 * Tests generating image tags
	 */
	public function testImage() {
		$result = $this->html->image('test.gif');
		$this->assertTags($result, array('img' => array('src' => '/img/test.gif', 'alt' => '')));

		$result = $this->html->image('http://example.com/logo.gif');
		$this->assertTags($result, array('img' => array(
			'src' => 'http://example.com/logo.gif', 'alt' => ''
		)));

		$result = $this->html->image(array(
			'controller' => 'test', 'action' => 'view', 'id' => '1', 'type' => 'gif'
		));
		$this->assertTags($result, array('img' => array('src' => '/test/view/1.gif', 'alt' => '')));

		$result = $this->html->image('/test/view/1.gif');
		$this->assertTags($result, array('img' => array('src' => '/test/view/1.gif', 'alt' => '')));
	}

	/**
	 * Tests inline style linking with <link /> tags
	 */
	public function testStyleLink() {
		$result = $this->html->style('screen');
		$expected = array('link' => array(
			'rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'regex:/.*css\/screen\.css/'
		));
		$this->assertTags($result, $expected);

		$result = $this->html->style('screen.css');
		$this->assertTags($result, $expected);

		$result = $this->html->style('screen.css?1234');
		$expected['link']['href'] = 'regex:/.*css\/screen\.css\?1234/';
		$this->assertTags($result, $expected);

		$result = $this->html->style('http://whatever.com/screen.css?1234');
		$expected['link']['href'] = 'regex:/http:\/\/.*\/screen\.css\?1234/';
		$this->assertTags($result, $expected);
	}
	/**
	 * Tests generating random tags for the <head> section
	 */
	public function testHead() {
		$result = $this->html->head('meta', array('options' => array('author' => 'foo')));
		$expected = array('meta' => array('author' => 'foo'));
		$this->assertTags($result, $expected);

		$result = $this->html->head('unexisting-name', array(
			'options' => array('author' => 'foo')
		));
		$this->assertNull($result);
	}

	/**
	 * Tests generating multiple <link /> or <style /> tags in a single call with an array
	 */
	public function testStyleMulti() {
		$result = $this->html->style(array('base', 'layout'));
		$expected = array(
			'link' => array(
				'rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'regex:/.*css\/base\.css/'
			),
			array(
				'link' => array(
					'rel' => 'stylesheet', 'type' => 'text/css',
					'href' => 'regex:/.*css\/layout\.css/'
				)
			)
		);
		$this->assertTags($result, $expected);
	}

	/**
	 * Tests that script and style tags with `'inline'` set to `false` are written to the rendering
	 * context instead of being returned directly.
	 */
	public function testNonInlineScriptsAndStyles() {
		$result = trim($this->context->scripts());
		$this->assertEmpty($result);

		$result = $this->html->script('application', array('inline' => false));
		$this->assertEmpty($result);

		$result = $this->context->scripts();
		$this->assertTags($result, array('script' => array(
			'type' => 'text/javascript', 'src' => 'regex:/.*js\/application\.js/'
		)));

		$result = trim($this->context->styles());
		$this->assertEmpty($result);

		$result = $this->html->style('base', array('inline' => false));
		$this->assertEmpty($result);

		$result = $this->context->styles();
		$this->assertTags($result, array('link' => array(
			'rel' => 'stylesheet', 'type' => 'text/css', 'href' => 'regex:/.*css\/base\.css/'
		)));
	}

	/**
	 * Tests that scripts and styles are correctly written to the rendering context even when
	 * passing multiple scripts or styles to a single method call.
	 */
	public function testMultiNonInlineScriptsAndStyles() {
		$result = $this->html->script(array('foo', 'bar'));
		$expected = array(
			array('script' => array('type' => 'text/javascript', 'src' => 'regex:/.*\/foo\.js/')),
			'/script',
			array('script' => array('type' => 'text/javascript', 'src' => 'regex:/.*\/bar\.js/')),
			'/script'
		);
		$this->assertTags($result, $expected);

		$this->assertNull($this->html->script(array('foo', 'bar'), array('inline' => false)));
		$result = $this->context->scripts();
		$this->assertTags($result, $expected);
	}

	public function testScopeOption() {
		$result = array();

		$this->context = new MockRenderer(array(
			'request' => new Request(array(
				'base' => '', 'host' => 'foo.local'
			)),
			'response' => new Response(),
			'handlers' => array(
				'url' => function($url, $ref, array $options = array()) use (&$result) {
					$result = compact('options');
				},
				'path' => function($path, $ref, array $options = array()) use (&$result) {
					$result = compact('options');
				}
			)
		));
		$this->html = new Html(array('context' => &$this->context));

		$this->html->link('home', '/home');
		$this->assertFalse(isset($result['options']['scope']));
		$this->html->link('home', '/home', array('scope' => 'app'));
		$this->assertEqual('app', $result['options']['scope']);

		$this->html->link(
			'RSS Feed',
			array('controller' => 'posts', 'type' => 'rss'),
			array('type' => 'rss')
		);
		$this->assertFalse(isset($result['options']['scope']));
		$this->html->link(
			'RSS Feed',
			array('controller' => 'posts', 'type' => 'rss'),
			array('type' => 'rss', 'scope' => 'app')
		);
		$this->assertEqual('app', $result['options']['scope']);

		$this->html->script('script.js');
		$this->assertFalse(isset($result['options']['scope']));
		$this->html->script('script.js', array('scope' => 'app'));
		$this->assertEqual('app', $result['options']['scope']);

		$this->html->image('test.gif');
		$this->assertFalse(isset($result['options']['scope']));
		$this->html->image('test.gif', array('scope' => 'app'));
		$this->assertEqual('app', $result['options']['scope']);

		$this->html->style('screen');
		$this->assertFalse(isset($result['options']['scope']));
		$this->html->style('screen', array('scope' => 'app'));
		$this->assertEqual('app', $result['options']['scope']);

		$this->html->link('home', '/home');
		$this->assertFalse(isset($result['options']['scope']));

		$expected = array('app' => array('domain' => 'bob'));
		$this->html->link('home', '/home', array('scope' => $expected));
		$this->assertEqual($expected, $result['options']['scope']);
	}
}

?>