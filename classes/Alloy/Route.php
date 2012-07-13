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
	 * @var string  The name of the route
	 */
	private $name = null;

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
	 * @param  string $name      The name of the route
	 * @param  string $route     The user supplied route
	 * @param  array  $defaults  Any default parameters
	 */
	public function __construct($name, $route, $defaults = array())
	{
		$this->name = $name;
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
	 * Unique route name
	 *
	 * @param  string $name Unique route name object
	 * @return mixed        The name of the route or \Alloy\Route
	 */
	public function name($name = null)
	{
		if ($name === null)
		{
			return $this->name;
		}

		$this->name = $name;
		return $this;
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
		return array_merge($this->namedParams, $this->defaultParams, $this->methodDefaults($method), $matches);
	}

}
