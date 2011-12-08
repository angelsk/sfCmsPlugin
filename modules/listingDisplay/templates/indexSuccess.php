<?php
$sitetree = $sf_data->getRaw('sitetree');
$listing = $sf_data->getRaw('listing');
$contentGroup = $listing->ContentGroup;
$pager = $sf_data->getRaw('pager');
$templateFileLocation = $sf_data->getRaw('templateFileLocation');
$contentManager = listingManager::getInstance();

// Taken from config for template
// Included here because use_stylesheet and use_javascript don't work when template cached
/*
 * Example config
        item_cacheable:       true
        listing_cacheable:    true

        stylesheets:    [forms]
        javascripts:
          - jqplugins/jquery.imagetool-1.1.min.js
          - jqplugins/jquery.ezpz_tooltip.min.js
        item_stylesheets:    [forms]
        item_javascripts:
          - jqplugins/jquery.imagetool-1.1.min.js
          - jqplugins/jquery.ezpz_tooltip.min.js
 */
if ($stylesheets = $contentManager->getTypeDefinitionParameter($listing->type, 'stylesheets')) {
  foreach ($stylesheets as $stylesheet) use_stylesheet($stylesheet);
}

if ($javascripts = $contentManager->getTypeDefinitionParameter($listing->type, 'javascripts')) {
  foreach ($javascripts as $javascript) use_javascript($javascript);
}

// Template
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