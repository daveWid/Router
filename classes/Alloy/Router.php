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
	private $matchedRoute;

	/**
	 * Connect route
	 *
	 * @param  string $name      Name of the route
	 * @param  string $route     Route to match
	 * @param  array  $defaults  Array of key => value parameters to supply as defaults
	 * @return \Alloy\Route      The newly created route
	 */
	public function route($name, $route, array $defaults = null)
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
	 * Return the last matched route
	 *
	 * @throws \LogicException  If no route is matched yet.
	 *
	 * @return \Alloy\Route
	 */
	public function getMatchedRoute()
	{
		if ($this->matchedRoute)
		{
			return $this->matchedRoute;
		}
		else
		{
			throw new \LogicException("Unable to return last route matched - No route has been matched yet.");
		}
	}

	/**
	 * Return the name of the last matched route
	 *
	 * @return string
	 */
	public function getMatchedRouteName()
	{
		$route = $this->getMatchedRoute();
		return $route->name();
	}

	/**
	 * Put a URL together by matching route name and params
	 *
	 * @param array $params Array of key => value params to fill in for given route
	 * @param string $routeName Name of the route previously defined
	 *
	 * @return string Full matched URL as string with given values put in place of named parameters
	 * @throws UnexpectedValueException For non-existent route name or params that don't match given route name (Unable to create URL string)
	 */
	public function url($params = array(), $routeName = null)
	{
		// If params is string, assume route name for static route
		if (null === $routeName && is_string($params))
		{
			$routeName = $params;
			$params = array();
		}

		if (!$routeName)
		{
			throw new \UnexpectedValueException("Error creating URL: Route name must be specified.");
		}

		if (!isset($this->routes[$routeName]))
		{
			throw new \UnexpectedValueException("Error creating URL: Route name '" . $routeName . "' not found in defined routes.");
		}

		$routeUrl = "";
		$route = $this->routes[$routeName];
		$routeUrl = $route->route();

		// Static routes - let's save some time here
		if ($route->isStatic())
		{
			return $routeUrl;
		}


		$routeDefaults = $route->defaults();
		$routeParams = array_merge($routeDefaults, $route->namedParams());
		$optionalParams = $route->optionalParams();

		// Match all params on route that do not have defaults
		$matchedParams = $routeDefaults; // Begin with defaults
		foreach (array_merge($matchedParams, $params) as $key => $value)
		{
			// Optional params
			if (isset($optionalParams[$key]))
			{
				// If no given value, or given value is the same as default, set value to empty
				if ((isset($routeDefaults[$key]) && !isset($params[$key])))
				{
					$matchedParams[$key] = '';
				}
				else
				{
					$matchedParams[$key] = $optionalParams[$key]['prefix'] . $value . $optionalParams[$key]['suffix'];
				}
				$routeParams[$key] = $optionalParams[$key]['routeSegment'];
				// Required/standard param
			}
			elseif (isset($routeParams[$key]))
			{
				$matchedParams[$key] = $value;
			}
		}

		//var_dump($matchedParams);
		// Ensure all params have been matched, exception if not
		if (count(array_diff_key($matchedParams, $routeParams)) > 0)
		{
			throw new \UnexpectedValueException("Error creating URL: Route '" . $routeName . "' has parameters that have not been matched.");
		}

		// Fill in values and put URL together
		foreach ($routeParams as $paramName => $paramPlaceholder)
		{
			if (!isset($matchedParams[$paramName]))
			{
				throw new \UnexpectedValueException("Error creating URL for route '" . $routeName . "': Required route parameter '" . $paramName . "' has not been supplied.");
			}
			$routeUrl = str_replace($paramPlaceholder, urlencode($matchedParams[$paramName]), $routeUrl);
		}

		// Remove all optional parameters with no supplied match or default value
		foreach ($optionalParams as $param)
		{
			$routeUrl = str_replace($param['routeSegment'], '', $routeUrl);
		}

		// Ensure escaping characters are removed
		$routeUrl = str_replace('\\', '', $routeUrl);

		return $routeUrl;
	}

	/**
	 * Clear existing routes to start over
	 */
	public function reset()
	{
		$this->routes = array();
	}

}
