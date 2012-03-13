<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="notice"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>

<?php if (0 < count($currentCategories)) : ?>
  <h2>Current categories</h2>
  <table>
    <thead>
      <tr>
        <th>Title</th>
        <th># items</th>
        <th>Move</th>
        <th>Is active</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($currentCategories as $category) :
        $count = $category->getItemCount();
        $total = $count['active'] + $count['inactive']; ?>
        <tr>
          <td><?php echo $category->getTitle(); ?></td>
          <td>
            <?php echo $count['active']; ?> active / <?php echo $count['inactive']; ?> inactive
            (<?php echo $count['hidden']?> hidden)
          </td>
          <td>
            <span><a href="?upCategory=<?php echo $category->id . '#'.$formTarget; ?>" title="Move up"><?php echo image_tag('/sfCmsPlugin/images/up.png'); ?></a></span>
            <span><a href="?downCategory=<?php echo $category->id . '#'.$formTarget; ?>" title="Move down"><?php echo image_tag('/sfCmsPlugin/images/down.png'); ?></a></span>
          </td>
          <td>&nbsp;<?php if ($category->is_active) : ?><img alt="Checked" title="Checked" src="/sfCmsPlugin/images/tick.png"><?php endif; ?></td>
          <td>
            <span style="background: url(/sfDoctrinePlugin/images/edit.png) no-repeat 0px 0px; padding-left: 20px;"><a href="?editCategory=<?php echo $category->id . '#'.$formTarget; ?>">Edit</a></span>
            <?php if (0 == $total) : ?><span style="background: url(/sfDoctrinePlugin/images/delete.png) no-repeat 0px 0px; padding-left: 20px;"><a href="?deleteCategory=<?php echo $category->id . '#'.$formTarget; ?>" class="delete_cat">Delete</a></span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  
  <p><em>*</em> Only categories with no items can be deleted / Only active categories can be assigned to items (and only ones with items are available on the frontend).</p>
  
  <?php $content = get_slot('cms_js');  ?>
  <?php slot('cms_js');
    if (sfConfig::get('app_site_use_slots', false)) echo $content; // If using slot, combine them ?>
    
    <script type="text/javascript">
      $(document).addEvent('domready', function () 
      {
        $$('.delete_cat').each(function (el) 
        {
          el.addEvent('click', function () 
          {
            return confirm('Are you sure you want to delete this category - it cannot be undone');
          });
        });  
      });
    </script>
  <?php end_slot(); ?>
  <?php if (!sfConfig::get('app_site_use_slots', false)) include_slot('cms_js'); ?>
  
<?php else : ?>
   <p>No categories set</p>
<?php endif; ?>

<br />

<h2><?php echo ($editCategoryName) ? "Edit category '{$editCategoryName}'" : 'Add new category'; ?></h2>

<?php if ($form->hasErrors()): ?>
  <div class="error">Please correct the following errors</div>
<?php endif; ?>


<?php echo $form->renderFormTag(($editCategoryName ? '?editCategory='.$sf_request->getParameter('editCategory') : '') . '#'.$formTarget); ?>
  <?php 
  echo $form->renderGlobalErrors(); 
  echo $form->renderHiddenFields();
  ?>

  <fieldset id="sf_fieldset_none">
    <?php if ($editCategoryName) : ?>
      <?php $translations = array(); ?>
       <?php foreach ($form->getObject()->Translation as $culture => $Translation) : ?>
         <?php if (!empty($Translation->title)) $translations[$culture] = $Translation->title; ?>
      <?php endforeach; ?>
      <?php if (!empty($translations)) : ?>
        <div class="sf_admin_form_row">
          <label>Category translations</label>
          <div class="content">
            <?php foreach ($translations as $culture => $Translation) : ?>
               <?php echo $culture . ' - ' . $Translation; ?><br />
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  
    <?php foreach ($form as $idx => $widget):
      if (!$widget->isHidden()) : ?>
      
        <div class="sf_admin_form_row <?php if ($widget->hasError()) echo 'errors'; ?>">
          <?php echo $widget->renderError(); ?>
          <div>
            <?php echo $widget->renderLabel(); ?>
            <div class="content"><?php echo $widget->render(); ?></div>
            <?php if ($help = $widget->renderHelp()) : ?><div class="help"><?php echo str_replace('<br />', '', $help); ?></div><?php endif; ?>
          </div>
        </div>
          
      <?php endif; 
    endforeach; ?>
  </fieldset>
  
  <ul class="sf_admin_actions">
    <li class="sf_admin_action_save"><input type="submit" value="<?php echo ($editCategoryName ? 'Update' : 'Add')?>" /></li>
    <?php if ($editCategoryName) : ?>
      <li class="sf_admin_action_new"><a href="<?php echo url_for('listingAdmin/edit?id='.$listing->id); ?>#<?php echo $formTarget; ?>">Add new category</a></p>
    <?php endif; ?>
  </ul>
</form>
<br class="clear" />