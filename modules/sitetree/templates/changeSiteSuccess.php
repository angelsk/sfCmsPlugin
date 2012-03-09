<div class="sitetreeInfo">
  <p>Pick a site to edit:</p>
  
  <ul>
    <?php foreach ($sites as $code => $site) : ?>
      <li><?php echo link_to($site, 'sitetree/changeSite?site='.$code, array('class' => $code, 'title' => $site)); ?></li>
    <?php endforeach; ?>
  </ul>
</div>