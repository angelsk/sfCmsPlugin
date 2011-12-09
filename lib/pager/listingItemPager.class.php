<?php
/**
 * This pager is used in the admin app to manage the items in the list
 */
class listingItemPager extends sfDoctrineSuperPager 
{
	protected $listing;

	/**
	 * @param listing $listing
	 */
	public function __construct($listing) 
	{
		$manager = listingManager::getInstance();
		$itemClass = $manager->getListItemClass($listing->type);
		$filterClass = "{$itemClass}FormFilter";
		$form = new $filterClass(array(), array('type'=>$listing->type, 'listing_id'=>$listing->id));

		$cols = array(array('name' => 'Title'));
		
		if ($listing->use_custom_order) 
		{
			$cols[] = array('name' => 'Move');
		}
		
		$cols[] = array('name' => 'Is live?');
		$cols[] = array('name' => 'Created');
		$cols[] = array('name' => 'Last updated');
		$cols[] = array('name' => 'Actions');

		parent::__construct($itemClass, $form, null, $cols);

		// Set up translation
		$culture = sfContext::getInstance()->getUser()->getCulture();
		$query = $this->getQuery();
		$query->from("$itemClass i");
		
		// Get all translations - just in case
    	$query->leftJoin('i.Translation t ON (i.id = t.id) INDEXBY t.lang');

    	$this->listing = $listing;
	}

	/**
	 * Render the row
	 *
	 * @param Doctrine_Record $item
	 * @return array
	 */
	public function renderRow($item) 
	{
		$listId = $this->listing->id;
		
		// Display English/first entered version of title if no translation so don't have blank items
		$title = $item->title;
		
		if (empty($title) && !empty($item->Translation['en_GB']->title)) $title = $item->Translation['en_GB']->title . ' [en_GB]';
		
		if (empty($title)) 
		{
		  	foreach ($item->Translation as $culture => $Translation) 
			{
				if (!empty($Translation->title) && empty($title)) $title = $Translation->title . ' [' . $culture . ']';
			}
		}
		
		$out = array(array(esc_entities($title)));
		
		if ($this->listing->use_custom_order) 
		{
			// include the ordering buttons
			$directions = array('up'=>'desc', 'down'=>'asc');
			$bit = '';
			
			foreach ($directions as $direction => $image) 
			{
				$bit .= link_to(
					image_tag('/sfDoctrinePlugin/images/'.$image.'.png'),
					'listingAdmin/moveItem?listId=' . $listId . '&direction=' . $direction . '&id=' . $item->id,
					array('title' => 'move ' . $direction)
				);
			}
			
			$out[] = array($bit, array('class'=>'col1'));
		}
		
		$out[] = array(($item->is_active ? '<span class="ui-icon ui-icon-check"></span>' : '&nbsp;'));
		$out[] = array(format_datetime($item->created_at, 'f') . '<br /> by ' . $item->CreatedBy->username);
		$out[] = array(format_datetime($item->updated_at, 'f') . '<br /> by ' . $item->UpdatedBy->username);
		
		$editOut = '<ul class="sf_admin_td_actions">';
		$editOut .= '<li class="sf_admin_action_edit" style="display:block;">' . link_to(
			"Edit",
			'listingAdmin/editItem?listId=' . $listId . '&id=' . $item->id,
			array('class' => 'btn_edit')
		) . '</li>';
		
		if (!$item->is_active) 
		{
			$editOut .= '<li class="sf_admin_action_new" style="display:block;">' . link_to(
				"Publish",
				'listingAdmin/publishItem?listId=' . $listId . '&id=' . $item->id,
				array('class' => 'btn_publish')
			) . '</li>';
		}
		
		$editOut .= '<li class="sf_admin_action_delete" style="display:block;">' . link_to(
				"Delete",
				'listingAdmin/deleteItem?listId=' . $listId . '&id=' . $item->id,
				array('class' => 'btn_remove')
			) . '</li>';
		$editOut .= "</ul>";
		
		$out[] = array($editOut);

		return $out;
	}

  	public function addFilterValuesToQuery($request) 
	{
    	$form = $this->getFilterForm();
    	$filter = $request->getParameter('filter');
    	$form->bind($filter);

    	if ($form->isValid()) 
		{
        	$this->setQuery($form->addFiltersToQuery($this->getQuery()));
    	}
  	}
}