<?php

namespace Alloy;

/**
 * Router Route
 *
 * @package Alloy
 * @link http://alloyframework.com/
 * @license http://www.opensource.org/licenses/bsd-license.php
 */
class Route
{
	/**
	 * @var string  The route as entered
	 */
	private $route = null;

	/**
	 * @var boolean Does this route need regex?
	 */
	private $isStatic = false;

	/**
	 * @var string  The compiled regex pattern
	 */
	private $regex = null;

	/**
	 * @var array   Default parameters
	 */
	private $defaultParams = array();

	/**
	 * @var array   The defaults for all named sections of the route.
	 */
	private $namedParams = array();

	/**
	 * @var array   Additional parameters for the different HTTP verbs
	 */
	private $methodParams = array(
		'GET' => array(),
		'POST' => array(),
		'PUT' => array(),
		'DELETE' => array()
	);

	/**
	 * @var callable  A callback to make sure certian conditions are met
	 */
	private $condition = null;

	/**
	 * @var callable  An after hook for when a route is matched
	 */
	private $afterMatch = null;

	/**
	 * New router object
	 *
	 * @throws \DomainException  If the parser is not set
	 *
	 * @param  string $route     The user supplied route
	 * @param  array  $defaults  Any default parameters
	 */
	public function __construct($route, $defaults = array())
	{
		$this->route = $route;
		$this->defaultParams = $defaults;

		if (strpos($route, '<') === false)
		{
			$this->isStatic = true;
			$this->regex = Route\Parser::parseStatic($route);
		}
		else
		{
			$this->regex = Route\Parser::parse($route);
		}

		foreach (Route\Parser::$namedParams as $key)
		{
			$this->namedParams[$key] = null;
		}
	}

	/**
	 * Set or return default params for the route
	 *
	 * @param  array $params OPTIONAL Array of key => values to return with route match
	 * @return mixed Array or object instance
	 */
	public function defaults(array $params = null)
	{
		if ($params == null)
		{
			return $this->defaultParams;
		}

		$this->defaultParams = $params;
		return $this;
	}

	/**
	 * Is this route static?
	 *
	 * @return boolean
	 */
	public function isStatic()
	{
		return $this->isStatic;
	}

	/**
	 * User-entered route
	 *
	 * @return string Route as user entered it
	 */
	public function route()
	{
		return $this->route;
	}

	/**
	 * Compiled route regex
	 *
	 * @return string Regular expression representing route
	 */
	public function regexp()
	{
		return $this->regex;
	}

	/**
	 * Convenience functions for 'method'
	 */
	public function get(array $params)
	{
		return $this->methodDefaults('GET', $params);
	}

	public function post(array $params)
	{
		return $this->methodDefaults('POST', $params);
	}

	public function put(array $params)
	{
		return $this->methodDefaults('PUT', $params);
	}

	public function delete(array $params)
	{
		return $this->methodDefaults('DELETE', $params);
	}

	/**
	 * Set parameters based on request method
	 *
	 * @param  string $method Request method (GET, POST, PUT, DELETE, etc.)
	 * @param  array $params OPTIONAL Array of key => value parameters to set on route for given request method
	 * @return mixed  Array of parameters or \Alloy\Route
	 */
	public function methodDefaults($method, array $params = null)
	{
		if ($params === null)
		{
			return $this->methodParams[$method];
		}

		$method = strtoupper($method);
		if ( ! isset($this->methodParams[$method]))
		{
			$this->methodParams[$method] = $params;
		}
		else
		{
			$this->methodParams[$method] += $params;
		}

		return $this;
	}

	/**
	 * Condition callback
	 *
	 * @throws \InvalidArgumentException When supplied argument is not a valid callback
	 *
	 * @param  callback $callback  Callback function to be used when providing custom route match conditions
	 * @return callback
	 */
	public function condition($callback = null)
	{
		if ($callback !== null)
		{
			if ( ! is_callable($callback))
			{
				throw new \InvalidArgumentException("Condition provided is not a valid callback. Given (" . gettype($callback) . ")");
			}
			$this->condition = $callback;
			return $this;
		}

		return $this->condition;
	}

	/**
	 * After match callback
	 *
	 * @param callback $callback Callback function to be used to modify params after a successful match
	 * @throws \InvalidArgumentException When supplied argument is not a valid callback
	 * @return callback
	 */
	public function afterMatch($callback = null)
	{
		// Setter
		if ($callback !== null)
		{
			if ( ! is_callable($callback))
			{
				throw new \InvalidArgumentException("The after match callback provided is not valid. Given (" . gettype($callback) . ")");
			}
			$this->afterMatch = $callback;
			return $this;
		}

		return $this->afterMatch;
	}

	/**
	 * Based on the matching parameters, gets the parameters
	 * associated with this route.
	 *
	 * @param  array  $matches  Any matched parameters from the router
	 * @param  string $method   The http request method
	 * @return array
	 */
	public function getParams(array $matches, $method)
	{
		$params =  array_merge($this->namedParams, $this->defaultParams, $this->methodDefaults($method), $matches);
		return array_map('urldecode', $params);
	}

	/**
	 * Generate the url for this route. Useful in reverse routing.
	 *
	 * @param  array  $params  The params to substitue
	 * @param  type   $method  The request method
	 * @return string
	 */
	public function url(array $params, $method)
	{
		$url = $this->route;
		if ($this->isStatic())
		{
			return $url;
		}

		$params = $this->getParams($params, $method);

		$url = $this->replaceOptional($url, $params);
		return $this->replaceRequired($url, $params);
	}

	/**
	 * Replaces all of the optional parameters.
	 *
	 * @param  string $url     The current route string
	 * @param  array  $params  The parameters used for replacing
	 * @return string
	 */
	private function replaceOptional($url, array $params)
	{
		if (strpos($url, "(") === false)
		{
			return $url;
		}

		$match = array();

		// The pattern looks for all ( with a matching ) looking inside out.
		while(preg_match('#\([^()]++\)#', $url, $match))
		{
			$param = substr($match[0], 1, -1); // Take off the ( and )
			list($parsed) = Route\Parser::parseParams($param);

			$replace = "";
			if (array_key_exists($parsed['name'], $params) AND $params[$parsed['name']] !== null)
			{
				$replace = str_replace($parsed['token'], urlencode($params[$parsed['name']]), $param);
			}

			$url = str_replace($match[0], $replace, $url);
		}

		return $url;
	}

	/**
	 * Replaces required route parameters
	 *
	 * @throws \UnexpectedValueException
	 *
	 * @param  string $url     The url to check
	 * @param  array  $params  The found params
	 * @return string
	 */
	private function replaceRequired($url, array $params)
	{
		if (strpos($url, "<") === false)
		{
			return $url;
		}

		$matches = Route\Parser::parseParams($url);
		foreach ($matches as $match)
		{
			if ( ! array_key_exists($match['name'], $params) OR $params[$match['name']] === null)
			{
				throw new \UnexpectedValueException("Required route parameter {$match['name']} has not been supplied.");
			}

			$url = str_replace($match['token'], urlencode($params[$match['name']]), $url);
		}

		return $url;
	}

}
