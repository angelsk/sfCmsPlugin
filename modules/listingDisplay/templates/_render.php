<?php
if ($useCache) 
{
  if (!site_cache($cacheName)) 
  {
    // load all content block versions efficiently in one db query
    $contentGroup->loadAllContentBlocksForRender();

    // include our template.
    require($templateFileLocation);
    site_cache_save();
  }
}
else 
{
  // load all content block versions efficiently in one db query
  $contentGroup->loadAllContentBlocksForRender();

  // include our template.
  require($templateFileLocation);
}