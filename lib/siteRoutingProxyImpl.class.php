<?php
/**
 * Default implementation of routing proxy
 * 
 * @author Jo Carter <work@jocarter.co.uk>
 */
class siteRoutingProxyImpl implements siteRoutingProxy 
{
	/**
	 * sfPatternRouting
	 *
	 * @var sfPatternRouting
	 */
	protected $router;
	
	public function setRouter($router) 
	{
		$this->router = $router;
	}
	
	/**
	 * @TODO Add site in route?
	 */
	public function addRoute($sitetree, $name, $url, $defaultParams = array(), $requirements = array(), $options = array()) 
	{
		$routeName = $this->getRouteName($sitetree, $name);
		$includeCulture = sfConfig::get('app_site_include_culture_in_routes', false);
		
		if ($includeCulture) 
		{
		  $siteDefn = siteManager::getInstance()->getSite();
		  $defaultCulture = (isset($siteDefn['default_culture'])) ? $siteDefn['default_culture'] : 'en_GB';
		  if ('/' == $url) $url = '';
		  $url = '/:sf_culture' . $url;
      $defaultParams['sf_culture'] = $defaultCulture;
		}

		$route = new sfRoute($url, $defaultParams, $requirements);

		$this->router->prependRoute(
			$routeName,
			$route
		);
	}
	
	public function getRouteName($sitetree, $name, $extras='') 
	{
		$routeName = $sitetree['route_name'];

		if ($name)
		{
		  $routeName .= siteManager::getInstance()->getRouteJunkChar() . $name;
		}
		
		if ($extras) 
		{
			$routeName .= siteManager::getInstance()->getRouteJunkChar() . $extras;
		}
		
		return $routeName;
	}
	
	public function generateInternalUrl($sitetree, $name, $params = array()) 
	{
		$url = '@' . $this->getRouteName($sitetree, $name);
		
		if (count($params)) 
		{
			$bits = array();
			
			foreach ($params as $key => $value) 
			{
				$bits[] = $key . '=' . $value;
			}
			
			$url .= '?' . implode('&', $bits);
		}

		return $url;
	}
	
	public function getSitetreeFromSymfonyRoute($symfonyRouteName, $site) 
	{
		$bits = explode(siteManager::getInstance()->getRouteJunkChar(), $symfonyRouteName, 2);
		
		$routeName = $bits[0];
		
		return SitetreeTable::getInstance()->retrieveByRoutename($site, $routeName);
	}
}