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
		$regexMatches = self::parseParams($regex);

		foreach ($regexMatches as $match)
		{
			$regex = str_replace($match['token'], self::getRegex($match), $regex);
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
	 * Parse a regex string into an array of normalized matches.
	 *
	 * @param  string $regex  The regular expression string
	 * @return array          An array of arrays with token, type, name and regex params
	 */
	public static function parseParams($regex)
	{
		$matches = array();
		preg_match_all(self::REGEX_ROUTE_PARAM, $regex, $matches, PREG_SET_ORDER);

		$found = array();
		foreach ($matches as $match)
		{
			$add = array(
				'token' => $match[0],
				'type' => $match[1],
				'name' => $match[2],
				'regex' => ""
			);

			if (strpos($add['name'], "|") !== false)
			{
				list($add['name'], $add['regex']) = explode("|", $add['name']);
			}

			$found[] = $add;
		}

		return $found;
	}

	/**
	 * Builds the regex string for the match
	 *
	 * @param  array  $match   The match array
	 * @return string          The regex string
	 */
	private static function getRegex(array $match)
	{
		switch ($match['type'])
		{
			case "#":
				$match['regex'] = self::REGEX_NUMERIC;
			break;
			case "*":
				$match['regex'] = self::REGEX_WILDCARD;
			break;
			default:
				if ($match['regex'] === "")
				{
					$match['regex'] = self::REGEX_KEYS;
				}
		}

		$regex = "(?P<{$match['name']}>{$match['regex']})";

		self::$namedParams[] = $match['name'];
		return $regex;
	}

}
