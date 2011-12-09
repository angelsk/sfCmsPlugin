<?php
$page = $sf_data->getRaw('page');
$contentGroup = $sf_data->getRaw('contentGroup');
$templateFileLocation = $sf_data->getRaw('templateFileLocation');
$sitetree = $sf_data->getRaw('sitetree');
$contentManager = pageManager::getInstance();

// Taken from config for template
// Included here because use_stylesheet and use_javascript don't work when template cached
/*
 * Example config
        cacheable:      true
        stylesheets:    [forms]
        javascripts:
          - jqplugins/jquery.imagetool-1.1.min.js
          - jqplugins/jquery.ezpz_tooltip.min.js
 */

if ($stylesheets = $contentManager->getTemplateDefinitionAttribute($page->template, 'stylesheets'))
{
  foreach ($stylesheets as $stylesheet) use_stylesheet($stylesheet);
}

if ($javascripts = $contentManager->getTemplateDefinitionAttribute($page->template, 'javascripts'))
{
  foreach ($javascripts as $javascript) use_javascript($javascript);
}

if ($useCache) {
	if (!site_cache($cacheName)) {
        // load all content block versions efficiently in one db query
	    $contentGroup->loadAllContentBlocksForRender();
	    
	    // include our template. 
        require($templateFileLocation);
		site_cache_save();
	}
} 
else {
    // load all content block versions efficiently in one db query
	$contentGroup->loadAllContentBlocksForRender();
	
    // include our template. 
    require($templateFileLocation);
}