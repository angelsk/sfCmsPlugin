<?php
$sitetree = $sf_data->getRaw('sitetree');
$item = $sf_data->getRaw('item');
$contentGroup = $item->ContentGroup;
$listing = $sf_data->getRaw('listing');
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
if ($stylesheets = $contentManager->getTypeDefinitionParameter($listing->type, 'item_stylesheets')) {
  foreach ($stylesheets as $stylesheet) use_stylesheet($stylesheet);
}

if ($javascripts = $contentManager->getTypeDefinitionParameter($listing->type, 'item_javascripts')) {
  foreach ($javascripts as $javascript) use_javascript($javascript);
}

// Template
if ($useCache) {
	if (!site_cache($cacheName)) {
        // load all content versions efficiently in one db query
	    $contentGroup->loadAllContentBlocksForRender();
	    
	    // include our template.
        require($templateFileLocation);
		site_cache_save();
	}
} else {
    // load all content versions efficiently in one db query
	$contentGroup->loadAllContentBlocksForRender();
	
    // include our template.
    require($templateFileLocation);
}