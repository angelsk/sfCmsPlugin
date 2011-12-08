<?php
/**
 * site Routing Proxy
 * 
 * This is used to map between symfony routes and sitetree module routes.
 * 
 * @author Jo Carter <work@jocarter.co.uk>
 */
interface siteRoutingProxy 
{
	/**
	 * Sets the sfPatternRouting to add routes to.
	 * 
	 * This is always called by the siteManager before "addRoute()" below is
	 * called.
	 *
	 * @param sfPatternRouting $router
	 */
	public function setRouter($router);
	
	
	/**
	 * Add the given route into the symfony routing
	 * 
	 * The "name" is the name used to identify this route for the module when 
	 * it is adding more then one route for each Sitetree node.  For example,
	 * in the content listing module the name is "item" for the route which
	 * displays the details page for an item.  Every node must provide a route
	 * with a blank name, which is the route which matches the url for the 
	 * Sitetree node.
	 * 
	 * The $defaultParams and $requirements are the same as for sfPatternRouting
	 * 
	 * @param Sitetree $sitetree
	 * @param string $name
	 * @param string $url
	 * @param array $defaultParams
	 * @param array $requirements
	 */
	public function addRoute($sitetree, $name, $url, $defaultParams = array(), $requirements = array());
	
	
	/**
	 * Generate an internal symfony url for a $sitetree and a given $name.
	 * 
	 * generateInternalUrl($contentPageSitetree);
	 * 
	 * returns something like "@sitetree_route_name+"
	 * 
	 * More complicated route, eg. for an item of a content listing page
	 * 
	 * generateInternalUrl($contentListingSitetree, 'item', array('slug' => 'mySlug'))
	 * 
	 * returns something like "@sitetree_route_name+item?slug=mySlug"
	 *
	 * @param Sitetree $sitetree
	 * @param string $name
	 * @param array $params
	 */
	public function generateInternalUrl($sitetree, $name, $params = array());
	
	
	/**
	 * Get the Sitetree which created this route
	 * 
	 * This takes a symfony route name, and returns the $sitetree node which
	 * created it.  It can do this because we always include the route_name 
	 * of the $sitetree in the name of the symfony route.
	 *
	 * @param string $symfonyRouteName
	 * @param string $site
	 */
	public function getSitetreeFromSymfonyRoute($symfonyRouteName, $site);
}