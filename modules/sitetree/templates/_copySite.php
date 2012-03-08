<?php $activeSites = siteManager::getInstance()->getActiveSites(); ?>

<div class='sitetreeInfo copySitetree'>
  <form action="<?php echo url_for('sitetree/copy'); ?>" method="get">
    <p class="left">
      <label for="site">Copy site structure (not including content) from:</label>
    
      <select name="site">
        <option value="">&nbsp;</option>
        <?php foreach ($sites as $site) : ?>
          <option value="<?php echo $site; ?>"><?php echo (isset($activeSites[$site]) ? $activeSites[$site] : $site); ?></option>
        <?php endforeach; ?>
      </select>
      
      &nbsp;
    </p>
    
    <input type="submit" name="submit" value="Copy structure" />
  </form>
</div>