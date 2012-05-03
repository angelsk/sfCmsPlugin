<?php
/**
 * This pager is used in the admin app to manage the items in the list
 */
class listingItemPager extends sfDoctrineSuperPager
{
  protected $listing;
  protected $useCategories;
  protected $canAdmin;
  protected $canPublish;
  protected $approvals;

  /**
   * @param listing $listing
   */
  public function __construct($listing)
  {
    // Permissions
    $user             = sfContext::getInstance()->getUser();
    $this->canAdmin   = $user->hasCredential('site.admin');
    $this->canPublish = ($this->canAdmin || $user->hasCredential('site.publish'));
    
    $manager          = listingManager::getInstance();
    $itemClass        = $manager->getListItemClass($listing->template);
    $filterClass      = "{$itemClass}FormFilter";
    $form             = new $filterClass(array(), array('template'=>$listing->template, 'listing_id'=>$listing->id));

    // get any approvals
    $this->approvals = SiteApprovalTable::getInstance()->getOrderedApprovalsForItem($listing->sitetree_id, $itemClass);
    
    $cols             = array(array('name' => 'Title'));

    if ($listing->use_custom_order)
    {
      $cols[] = array('name' => 'Move');
    }

    $this->useCategories = $manager->getTemplateDefinitionParameter($listing->template, 'use_categories', true);

    if ($this->useCategories)
    {
      $cols[] = array('name' => 'Category');
    }

    $cols[] = array('name' => 'Item date');
    $cols[] = array('name' => 'Is live?');
    $cols[] = array('name' => 'Created');
    $cols[] = array('name' => 'Last updated');
    $cols[] = array('name' => 'Actions');

    parent::__construct($itemClass, $form, null, $cols);

    // Set up translation
    $culture = sfContext::getInstance()->getUser()->getCulture();
    $query   = $this->getQuery();
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
    
    if (isset($this->approvals[$item->id]) && $this->canPublish) $title .= ' *';

    $out = array(array(esc_entities($title)));

    if ($this->listing->use_custom_order)
    {
      // include the ordering buttons
      $directions = array('up', 'down');
      $bit = '';

      foreach ($directions as $direction)
      {
        $bit .= link_to(
        image_tag('/sfCmsPlugin/images/'.$direction.'.png'),
          'listingAdmin/moveItem?listId=' . $listId . '&direction=' . $direction . '&id=' . $item->id,
        array('title' => 'move ' . $direction)
        );
      }

      $out[] = array($bit, array('class'=>'col1'));
    }

    if ($this->useCategories)
    {
      $out[] = array($item->ListingCategory->title);
    }

    $out[] = array(($item->item_date ? $item->getDateTimeObject('item_date')->format('d M Y') : ''));
    $out[] = array(($item->is_active ? '<img src="/sfCmsPlugin/images/tick.png" />' : '&nbsp;'));
    $out[] = array($item->getDateTimeObject('created_at')->format('d M Y H:i') . ' by ' . $item->CreatedBy->username);
    $out[] = array($item->getDateTimeObject('updated_at')->format('d M Y H:i') . ' by ' . $item->UpdatedBy->username);

    $editOut = '<ul class="sf_admin_td_actions">';
    $editOut .= '<li class="sf_admin_action_edit" style="display:block;">' . link_to(
      "Edit",
      'listingAdmin/editItem?listId=' . $listId . '&id=' . $item->id, array('class' => 'btn_edit')
      ) . '</li>';

    if (!$item->is_active && $this->canPublish)
    {
      $editOut .= '<li class="sf_admin_action_publish" style="display:block;">' . link_to(
        "Publish",
        'listingAdmin/publishItem?listId=' . $listId . '&id=' . $item->id, array('class' => 'btn_publish')
        ) . '</li>';
    }

    if ($this->canAdmin)
    {
      $editOut .= '<li class="sf_admin_action_delete" style="display:block;">' . link_to(
          "Delete",
          'listingAdmin/deleteItem?listId=' . $listId . '&id=' . $item->id, array('class' => 'btn_remove')
        ) . '</li>';
    }
    
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