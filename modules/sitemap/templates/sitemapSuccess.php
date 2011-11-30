<?php
sfConfig::set('sf_web_debug', false);
$records = $sf_data->getRaw('entireSitetree');
$today = date('Y-m-d');
?>
<urlset
  xmlns="http://www.google.com/schemas/sitemap/0.84"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84
                      http://www.google.com/schemas/sitemap/0.84/sitemap.xsd">
 
<?php if(isset($records) && !empty($records) && count($records) > 0)
{
  foreach($records as $record)
  {
    if ($record->is_deleted) continue;
    if ($record->is_hidden || !$record->is_active) continue;
    
    $level = $record->level + 1;
    ?>
      <url>
        <loc><?php echo url_for(internal_url_for_sitetree($record), true); ?></loc>
        <lastmod><?php echo $today; ?></lastmod>
        <priority><?php echo round((1 / $level),2); ?></priority>
        <changefreq>daily</changefreq>
      </url>
  <?php }
} ?>
 
</urlset>