<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="ui-widget">
	 <div class="ui-state-highlight ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
		<p><span class="ui-icon ui-icon-info left"></span>
		<?php echo $sf_user->getFlash('notice'); ?></p>
	 </div>
  </div>
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
						<span><a href="?upCategory=<?php echo $category->id . '#'.$formTarget; ?>" title="Move up"><?php echo image_tag('/sfDoctrinePlugin/images/desc.png'); ?></a></span>
						<span><a href="?downCategory=<?php echo $category->id . '#'.$formTarget; ?>" title="Move down"><?php echo image_tag('/sfDoctrinePlugin/images/asc.png'); ?></a></span>
					</td>
					<td>&nbsp;<?php if ($category->is_active) : ?><span class="ui-icon ui-icon-check left"></span><?php endif; ?></td>
					<td>
						<span style="background: url(/sfDoctrinePlugin/images/edit.png) no-repeat 0px 0px; padding-left: 20px;"><a href="?editCategory=<?php echo $category->id . '#'.$formTarget; ?>">Edit</a></span>
						<?php if (0 == $total) : ?><span style="background: url(/sfDoctrinePlugin/images/delete.png) no-repeat 0px 0px; padding-left: 20px;"><a href="?deleteCategory=<?php echo $category->id . '#'.$formTarget; ?>" class="delete_cat">Delete</a></span><?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	
	<p><em>*</em> Only categories with no items can be deleted / Only active categories can be assigned to items (and only ones with items are available on the frontend).</p>
	
	<script type="text/javascript">
    	$(document).ready( function() {
    	    $('.delete_cat').click( function() {
	    	    return confirm('Are you sure you want to delete this category - it cannot be undone');
	    	} );	
    	});
    </script>
<?php else : ?>
 	<p>No categories set</p>
<?php endif; ?>

<br />

<h2><?php echo ($editCategoryName) ? "Edit category '{$editCategoryName}'" : 'Add new category'; ?></h2>

<?php if ($form->hasErrors()): ?>
  <div class="ui-widget">
	<div class="ui-state-error ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
		<p><span class="ui-icon ui-icon-alert left"></span> 
		Please correct the following errors</p>
	</div>
  </div>
<?php endif; ?>


<?php echo $form->renderFormTag(($editCategoryName ? '?editCategory='.$sf_request->getParameter('editCategory') : '') . '#'.$formTarget); ?>
	<table>
		<?php echo $form; ?>
	</table>
	
	<input type="submit" value="<?php echo ($editCategoryName ? 'Update' : 'Add')?>" />
</form>
<br class="clear" /><br />

<?php if ($editCategoryName) : ?>
	<p style="background: url(/sfDoctrinePlugin/images/new.png) no-repeat 0px 0px; padding-left: 20px;"><a href="<?php echo url_for('listingAdmin/edit?id='.$listing->id); ?>#<?php echo $formTarget; ?>">Add new category</a></p>
<?php endif; ?>