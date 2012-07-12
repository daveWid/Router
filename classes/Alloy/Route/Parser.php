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
	const REGEX_ROUTE_PARAM = "\<([\:|\*|\#])([^\>]+)\>";

	/**
	 * Regular Expression for optional parameters
	 */
	const REGEX_OPTIONAL_ROUTE_PARAM = "\(([^\<]*)\<([\:|\*|\#])([^\>]+)\>[^\)]*\)+";

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
	 * Parses a url that has regular expressions into one that can be matched.
	 *
	 * @param  string $route  The user supplied route
	 * @return string         The parsed regular expression
	 */
	public static function parse($route)
	{
		$routeRegex = $route;

		// Extract optional named parameters from route
		$regexOptionalMatches = self::match(self::REGEX_OPTIONAL_ROUTE_PARAM, $routeRegex);
		$routeRegex = self::getOptionalRegex($regexOptionalMatches, $routeRegex);

		// Extract named parameters from route
		$regexMatches = self::match(self::REGEX_ROUTE_PARAM, $routeRegex);

		if ( ! empty($regexMatches))
		{
			foreach ($regexMatches as $match)
			{
				list($token, $type, $name) = $match;
				$routeRegex = str_replace($token, self::getRegex($type, $name), $routeRegex);
			}
		}

		return self::parseStatic($routeRegex);
	}

	/**
	 * Parses a static route into a regex
	 *
	 * @return string
	 */
	public static function parseStatic($route)
	{
		return '/^'.str_replace('/', '\/', $route).'$/';
	}

	/**
	 * Runs a preg_match_all on the source string.
	 *
	 * @param  string $pattern  The RegEx pattern
	 * @param  string $source   The source string to run on
	 * @return array
	 */
	public function match($pattern, $source)
	{
		$matches = array();
		preg_match_all("@{$pattern}@", $source, $matches, PREG_SET_ORDER);
		return $matches;
	}

	/**
	 * Build the regex for an optional segment.
	 *
	 * @param  array  $matches   The list of matches
	 * @param  string $source    The source string used for replacement
	 * @return string
	 */
	private static function getOptionalRegex($matches, $source)
	{
		if (empty($matches))
		{
			return $source;
		}

		$regex = $source;

		foreach ($matches as $match)
		{
			$replace = "";

			list($token, $prefix, $type, $name) = $match;
			$normalized = ltrim(rtrim($token, ")"), "(");

			foreach (explode("(", $normalized) as $part)
			{	
				list($prefix, $rest) = preg_split("@\<@", $part);
				$type = substr($rest, 0, 1);
				$name = substr($rest, 1, -1);

				$replace .= self::getRegex($type, $name, $prefix, true);
			}

			$regex = str_replace($token, $replace, $regex);
		}

		return $regex;
	}

	/**
	 * Gets the regex string for the given type
	 *
	 * @param  string  $type      The type of regex
	 * @param  string  $name      The name of the token
	 * @param  string  $prefix    Any route prefix (/ or something similar...)
	 * @param  boolean $optional  Is this an optional route?
	 * @return string             The regex string
	 */
	private static function getRegex($type, $name, $prefix = "", $optional = false)
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
					list($name, $regex) = explode("|", $name);
					$group = substr($regex, 1, -1); // Pull off [ and ]
				}
				else
				{
					$group = self::REGEX_KEYS;
				}
		}

		$regex = "(?P<{$name}>{$prefix}{$group})";

		if ($optional)
		{
			$regex .= "?";
		}

		return $regex;
	}

}
