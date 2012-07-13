<?php

use \Alloy\Route\Parser as Parser;

/**
 * Alloy Route\Parser testing
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider getUrls 
	 */
	public function testParser($url, $output)
	{
		$this->assertSame($output, Parser::parse($url));
	}

	public function getUrls()
	{
		return array (
			array("/alphanumeric/<:alphanum>", "/^\/alphanumeric\/(?P<alphanum>[a-zA-Z0-9\_\-\+\%\s]+)$/"),
			array("/number/<#id>", "/^\/number\/(?P<id>[0-9]+)$/"),
			array("/wildcard/<*overflow>", "/^\/wildcard\/(?P<overflow>.*)$/"),
			array("/custom/<:custom|[\d{4]-\d{2}-\d{2}]>", "/^\/custom\/(?P<custom>\d{4]-\d{2}-\d{2})$/"),
			array("/<:controller>/<:action>", "/^\/(?P<controller>[a-zA-Z0-9\_\-\+\%\s]+)\/(?P<action>[a-zA-Z0-9\_\-\+\%\s]+)$/"),
			array("/optional(/<:action>)", '/^\/optional(?P<action>\/[a-zA-Z0-9\_\-\+\%\s]+)?$/'),
			array("/(<:controller>(/<:action>(/<#id>)))", '/^\/(?P<controller>[a-zA-Z0-9\_\-\+\%\s]+)?(?P<action>\/[a-zA-Z0-9\_\-\+\%\s]+)?(?P<id>\/[0-9]+)?$/'),
			array('/(<:type>)/feed.(<:format>)', '/^\/(?P<type>[a-zA-Z0-9\_\-\+\%\s]+)?\/feed.(?P<format>[a-zA-Z0-9\_\-\+\%\s]+)?$/'),
		);
	}

	public function testNamedParams()
	{
		Parser::parse("/(<:controller>(/<:action>(/<#id>)))");

		$expected = array('controller','action','id');
		$this->assertSame($expected, Parser::$namedParams);
	}

}
