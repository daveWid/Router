<?php

namespace Alloy;

/**
 * Router
 *
 * Maps URL to named parameters for use in application
 *
 * @package Alloy
 * @link    http://alloyframework.com/
 * @license http://www.opensource.org/licenses/bsd-license.php BSD
 */
class Router
{
	/**
	 * @var array  Stored routes
	 */
	private $routes = array();

	/**
	 * @var \Alloy\Route  The matched route.
	 */
	private $matchedRoute = null;

	/**
	 * @var string  The name of the matched route
	 */
	private $matchedRouteName = null;

	/**
	 * Connect route
	 *
	 * @param  string $name      Name of the route
	 * @param  string $route     Route to match
	 * @param  array  $defaults  Array of key => value parameters to supply as defaults
	 * @return \Alloy\Route      The newly created route
	 */
	public function route($name, $route, array $defaults = array())
	{
		$route = new Route($route, $defaults);
		$this->routes[$name] = $route;
		return $route;
	}

	/**
	 * Get set routes
	 *
	 * @return array
	 */
	public function routes()
	{
		return $this->routes;
	}

	/**
	 * Match given URL string
	 *
	 * @throws \OutOfBoundsException  Exception thrown when no routes are defined.
	 *
	 * @param  string $method  HTTP Method to match for
	 * @param  string $url     Request URL to match for
	 * @return array  $params  Parameters with values that were matched
	 */
	public function match($method, $url)
	{
		if (empty($this->routes))
		{
			throw new \OutOfBoundsException("There must be at least one route defined to match for.");
		}

		$params = array();
		foreach ($this->routes as $name => $route)
		{
			$matches = array();
			preg_match($route->regexp(), $url, $matches);

			if ( ! empty($matches))
			{
				$matches = $this->normalizeMatches($matches);
				$params = $route->getParams($matches, $method);

				$callback = $route->condition();
				if ($callback !== null)
				{
					$passed = call_user_func($callback, $params, $method, $url);

					if ($passed === false)
					{
						$params = array();
						continue;
					}
				}

				$this->matchedRoute = $route;
				$this->matchedRouteName = $name;
				break;
			}
		}


		if ( ! empty($params) AND $route->afterMatch() !== null)
		{
			$params = call_user_func($route->afterMatch(), $params, $method, $url);
		}

		return $params;
	}

	/**
	 * Normalizes the matches array returned from a preg_match call.
	 *
	 * This will pull out just the named sections and remove the number indexed matches.
	 *
	 * @param  array  $matches  The matched param list
	 * @return array
	 */
	private function normalizeMatches(array $matches)
	{
		$params = array();

		foreach ($matches as $key => $value)
		{
			if (is_string($key))
			{
				$params[$key] = $value;
			}
		}

		return $params;
	}

	/**
	 * Alias of getMatchedRouteName()
	 *
	 * @return string
	 */
	public function matchedRoute()
	{
		return $this->getMatchedRoute();
	}

	/**
	 * Alias of getMatchedRouteName()
	 *
	 * @return string
	 */
	public function matchedRouteName()
	{
		return $this->getMatchedRouteName();
	}

	/**
	 * Return the last matched route
	 *
	 * @throws \LogicException  If no route is matched yet.
	 *
	 * @return \Alloy\Route
	 */
	public function getMatchedRoute()
	{
		if ($this->matchedRoute === null)
		{
			throw new \LogicException("Unable to return last route matched - No route has been matched yet.");
		}

		return $this->matchedRoute;
	}

	/**
	 * Return the name of the last matched route
	 *
	 * @throws \LogicException  If no route is matched yet.
	 *
	 * @return string
	 */
	public function getMatchedRouteName()
	{
		if ($this->matchedRouteName === null)
		{
			throw new \LogicException("Unable to return last route matched - No route has been matched yet.");
		}

		return $this->matchedRouteName;
	}

	/**
	 * Put a URL together by matching route name and params
	 *
	 * @throws \UnexpectedValueException  When the named route doesn't exist
	 * @throws \UnexpectedValueException  Params that don't match given route name (Unable to create URL string)
	 *
	 * @param  string $name     The name of the route
	 * @param  array  $params   Array of key => value params to fill in for given route
	 * @param  string $methods  The request method
	 * @return string Full matched URL as string with given values put in place of named parameters
	 */
	public function url($name, array $params = array(), $method = "GET")
	{
		if ( ! array_key_exists($name, $this->routes))
		{
			throw new \UnexpectedValueException("Error creating URL: Route name {$name} not found in defined routes.");
		}

		$route = $this->routes[$name];

		return $route->url($params, $method);
	}

	/**
	 * Clear existing routes to start over
	 */
	public function reset()
	{
		$this->routes = array();
		$this->matchedRoute = null;
		$this->matchedRouteName = null;
	}

}
