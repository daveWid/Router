<?php

namespace Alloy\Route;

/**
 * A helper class for route parsing.
 *
 * @package Alloy
 * @link http://alloyframework.com/
 * @license http://www.opensource.org/licenses/bsd-license.php
 */
class Parser
{
	/**
	 * Regular Expression for parameters
	 */
	const REGEX_ROUTE_PARAM = "#\<([\:|\*|\#])([^\>]+)\>#";

	/**
	 * Regular Expression for optional parameters
	 */
	//const REGEX_OPTIONAL_ROUTE_PARAM = "\(([^\<]*)\<([\:|\*|\#])([^\>]+)\>[^\)]*\)+";
	const REGEX_OPTIONAL_ROUTE_PARAM = "#\([^()]++\)#";

	/**
	 * Regular Expression for replacing named keys 
	 */
	const REGEX_KEYS = "[a-zA-Z0-9\_\-\+\%\s]+";

	/**
	 * Regualar Expression for numbers 
	 */
	const REGEX_NUMERIC = "[0-9]+";

	/**
	 * Regular Expression for a wildcard match
	 */
	const REGEX_WILDCARD = ".*";

	/**
	 * @var array  Any named params found during the last parse.
	 */
	public static $namedParams = array();

	/**
	 * Parses a url that has regular expressions into one that can be matched.
	 *
	 * @param  string $route  The user supplied route
	 * @return string         The parsed regular expression
	 */
	public static function parse($route)
	{
		self::$namedParams = array();

		$regex = $route;

		// Wrap optional parameters
		if (strpos($regex, "(") !== false)
		{
			$regex = str_replace(array("(", ")"), array("(?:", ")?"), $regex);
		}

		// Extract named parameters from route
		$regexMatches = array();
		preg_match_all(self::REGEX_ROUTE_PARAM, $regex, $regexMatches, PREG_SET_ORDER);

		foreach ($regexMatches as $match)
		{
			list($token, $type, $name) = $match;
			$regex = str_replace($token, self::getRegex($type, $name), $regex);
		}

		return '/^'.str_replace('/', '\/', $regex).'$/';
	}

	/**
	 * Parses a static route into a regex
	 *
	 * @return string
	 */
	public static function parseStatic($route)
	{
		self::$namedParams = array();
		return '/^'.str_replace('/', '\/', $route).'$/';
	}

	/**
	 * Gets the regex string for the given type
	 *
	 * @param  string  $type      The type of regex
	 * @param  string  $name      The name of the token
	 * @param  string  $prefix    Any route prefix (/ or something similar...)
	 * @return string             The regex string
	 */
	private static function getRegex($type, $name, $prefix = "")
	{
		$group = "";

		switch ($type)
		{
			case "#":
				$group = self::REGEX_NUMERIC;
			break;
			case "*":
				$group = self::REGEX_WILDCARD;
			break;
			default:
				if (strpos($name, '|') !== false)
				{
					list($name, $group) = explode("|", $name);
				}
				else
				{
					$group = self::REGEX_KEYS;
				}
		}

		$regex = "(?P<{$name}>{$prefix}{$group})";

		self::$namedParams[] = $name;
		return $regex;
	}

}
