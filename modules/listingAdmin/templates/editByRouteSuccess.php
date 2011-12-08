<?php
$culture = $sf_user->getCulture();
$sitetree = $sf_data->getRaw('sitetree');

slot('breadcrumbs', get_partial('sitetree/breadcrumbs', array(
  'sitetree' => $sitetree
)));
?>

<div class="item_control">

    <h2><?php echo $sitetree->getTitle(); ?></h2>
  
    <?php echo include_partial('sitetree/sitetreeInfo', array('sitetree'=>$sitetree)); ?>
    
  	<p>Please choose a listing type and options for your new page.  You will then be taken to a page where you can add items.</p>
	
	<?php if ($form->hasErrors()): ?>
	  <div class="ui-widget">
		<div class="ui-state-error ui-corner-all" style="margin: 10px; padding: 7px 0px 0px 7px;"> 
			<p><span class="ui-icon ui-icon-alert left"></span> 
			Please correct the following errors</p>
		</div>
	  </div>
	  <br />
	<?php endif; ?>
	
	<form method="post" action="">
	  <fieldset class="fld_float">
	      <table>
	          <?php echo $form ?>
	      </table>
	
	      <input type="submit" value="submit"  class="btn_create float_r frm_submit" />
	  </fieldset>
	</form>

</div>