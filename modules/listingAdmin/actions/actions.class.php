<?php

/**
 * listing admin actions.
 *
 * @package    site_cms
 * @subpackage listingAdmin
 * @author     Jo Carter
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class listingAdminActions extends sfActions
{
  public function preExecute()
  {
    $this->getResponse()->addJavascript('/sfCmsPlugin/js/SimpleTabs.js', 'last');
  }
  
  /**
   * Edit a listing by a route_name
   *
   * This will redirect to the usual edit page if a listing item exists,
   * and will show a create form if not.
   */
  public function executeEditByRoute(sfWebRequest $request)
  {
    $this->forward404Unless($this->hasRequestParameter('routeName'));
    $this->forward404Unless($this->hasRequestParameter('site'));

    $routeName = $this->getRequestParameter('routeName');
    $site      = $this->getRequestParameter('site');

    $sitetree = SitetreeTable::getInstance()->retrieveByRoutename($site, $routeName);
    $listing = ListingTable::getInstance()->findOneBySitetreeId($sitetree->id);

    if ($listing)
    {
      // a listing has already been created for this sitetree
      $this->redirect("listingAdmin/edit?id=$listing->id");
    }

    // display form for creating the listing
    $listing = Listing::createFromSitetree($sitetree);
    $form = new ListingForm($listing);

    if ($request->isMethod(sfWebRequest::POST) && $request->hasParameter('listing'))
    {
      $form->bind($request->getParameter('listing'));

      if ($form->isValid())
      {
        $form->save();

        // create the content group for this
        $listing = $form->getObject();
        $listing->updateNew();

        // go to the edit page
        $this->redirect("listingAdmin/edit?id=" . $listing->id);
      }
    }

    $this->routeName  = $routeName;
    $this->sitetree   = $sitetree;
    $this->form       = $form;
    $this->site       = $site;
  }

  /**
   * Edit a listing page
   */
  public function executeEdit(sfWebRequest $request)
  {
    $this->forward404Unless($this->hasRequestParameter('id'));

    $listingId = $request->getParameter('id');
    $listing = ListingTable::getInstance()->findOneById($listingId);

    $this->forward404Unless($listing);

    $sitetree = SitetreeTable::getInstance()->findOneById($listing->sitetree_id);

    $contentGroup = $listing->ContentGroup;
    $contentGroup->setCurrentLang($this->getUser()->getCulture());

    $manager = listingManager::getInstance();
    $form = new ListingForm($listing);

    if ($request->isMethod(sfWebRequest::POST) && $request->hasParameter('listing'))
    {
      $form->bind($request->getParameter('listing'));

      if ($form->isValid())
      {
        $form->save();
        $this->listingHasChanged($listing);

        $this->getUser()->setFlash('edit_notice', 'Your listing property changes have been saved');
      }
    }

    // get a pager for the items in this content listing:
    $pager = $this->getPager($listing);
    $pager->initFromRequest($request);

    $this->listing = $listing;
    $this->contentGroup = $contentGroup;
    $this->form = $form;
    $this->pager = $pager;
    $this->sitetree = $sitetree;
  }

  /**
   * Create a new item for our listing
   *
   * @param sfWebRequest $request
   */
  public function executeCreateItem(sfWebRequest $request)
  {
    $manager = listingManager::getInstance();

    $this->forward404Unless($this->hasRequestParameter('id'));

    $listingId = $request->getParameter('id');
    $listing = ListingTable::getInstance()->findOneById($listingId);

    $this->forward404Unless($listing);

    $sitetree = SitetreeTable::getInstance()->findOneById($listing->sitetree_id);
    $template = $listing->template;

    // create the new item
    $itemClass = $manager->getListItemClass($template);
    $item = call_user_func($itemClass.'::createFromListing', $listing);

    // make a form for editing the non-content block fields
    $formClass = $manager->getListItemFormClass($template);
    $formRequestVar = sfInflector::underscore($itemClass);
    $this->form = new $formClass($item);

    if ($request->isMethod('post') && $request->hasParameter($formRequestVar))
    {
      $this->bindItemForm($this->form, $formRequestVar);

      if ($this->form->isValid())
      {
        $this->form->save();

        $item = $this->form->getObject();
        $item->updateNew();
        $this->itemHasChanged($item);
        $this->listingHasChanged($listing);

        // redirect to item editing page where we'll have content blocks
        $this->redirect('listingAdmin/editItem?listId=' . $listing->id . '&id='.$item->get('id'));
      }
    }

    $this->sitetree = $sitetree;
    $this->setTemplate('editItem');
  }

  /**
   * Edit a list item
   */
  public function executeEditItem(sfWebRequest $request)
  {
    $manager = listingManager::getInstance();

    // We need to load up the list first because we don't know the model class
    // of the list items yet
    $this->forward404Unless($this->hasRequestParameter('listId'));
    $this->forward404Unless($this->hasRequestParameter('id'));

    $listingId = $request->getParameter('listId');
    $listing = ListingTable::getInstance()->findOneById($listingId);

    $this->forward404Unless($listing);

    $sitetree = SitetreeTable::getInstance()->findOneById($listing->sitetree_id);

    // load up the item we want to edit
    $template = $listing->template;
    $itemClass = $manager->getListItemClass($template);
    $item = Doctrine_Core::getTable($itemClass)->findOneById($request->getParameter('id'));
    $this->forward404Unless($item, 'No item with that id');

    $contentGroup = $item->ContentGroup;

    if ($contentGroup->id == null)
    {
      // set new template as ListingItem to allow for imported model tables
      $item->updateNew();
      $contentGroup = $item->ContentGroup;
    }

    $contentGroup->setCurrentLang($this->getUser()->getCulture());
    $contentGroup->getContentGroupType()->setListingItem($item);
    $this->forward404Unless($contentGroup, 'Item is missing content group');

    // get the form we're using to edit this item
    $formClass = $manager->getListItemFormClass($template);
    $formRequestVar = sfInflector::underscore($itemClass);
    $this->form = new $formClass($item);

    // process the form
    if ($request->isMethod(sfWebRequest::POST) && $this->hasRequestParameter('publish'))
    {
      $actionP = ($this->getRequestParameter('publish') === '1') ? 'publish' : 'unPublish';
      $item->$actionP();
      $this->itemHasChanged($item);
      $this->listingHasChanged($listing);

      // Message saying published/unpublished
      $this->getUser()->setFlash('notice', 'Item has been ' . ($item->is_active ? 'published' : 'unpublished'));
    }
    else if ($request->isMethod('post') && $request->hasParameter($formRequestVar))
    {
      // usual item editing form
      $this->bindItemForm($this->form, $formRequestVar);

      if ($this->form->isValid())
      {
        $this->form->save();

        $this->itemHasChanged($item);
        $this->listingHasChanged($listing);

        // Message saying changes saved
        $this->getUser()->setFlash('notice','Your changes have been saved');
      }
    }

    $this->sitetree = $sitetree;
    $this->contentGroup = $contentGroup;
  }

  /**
   * Move an item up or down in the list
   */
  public function executeMoveItem(sfWebRequest $request)
  {
    $manager = listingManager::getInstance();

    $listing = ListingTable::getInstance()->findOneById($request->getParameter('listId'));
    $this->forward404Unless($listing);

    $sitetree = SitetreeTable::getInstance()->findOneById($listing->sitetree_id);

    // load up the item we want to edit
    $template = $listing->template;
    $itemClass = $manager->getListItemClass($template);
    $item = Doctrine_Core::getTable($itemClass)->findOneById($request->getParameter('id'));
    $this->forward404Unless($item, 'No item with that id');

    // Reset the ordering for the listing, based on current order then creation date
    // Useful for legacy listings and those switched in the midst of items being added
    // @see Doctrine_Template_Orderable
    $item->resetOrder();
    $item = Doctrine_Core::getTable($itemClass)->findOneById($request->getParameter('id')); // Get new object, so ordr correct

    $direction = $request->getParameter('direction');
    $directions = array('top', 'up', 'down', 'bottom'); // Currently just using up/down
    $this->forward404Unless(in_array($direction, $directions));

    $methodName = "move" . ucfirst($direction);
    $item->$methodName(); // do the move
    $this->itemHasChanged($item);

    $this->redirect("listingAdmin/edit?id=$item->listing_id");
  }

  /**
   * Publish the given item
   */
  public function executePublishItem(sfWebRequest $request)
  {
    $manager = listingManager::getInstance();

    $listing = ListingTable::getInstance()->findOneById($request->getParameter('listId'));
    $this->forward404Unless($listing);

    $sitetree = SitetreeTable::getInstance()->findOneById($listing->sitetree_id);

    // load up the item we want to edit
    $template = $listing->template;
    $itemClass = $manager->getListItemClass($template);
    $item = Doctrine_Core::getTable($itemClass)->findOneById($request->getParameter('id'));
    $this->forward404Unless($item, 'No item with that id');

    $item->publish();
    $this->itemHasChanged($item);
    $this->listingHasChanged($listing);

    $this->getUser()->setFlash('listing_notice', 'Item has been published');

    $this->redirect("listingAdmin/edit?id=$item->listing_id");
  }

  /**
   * Delete the given item
   */
  public function executeDeleteItem(sfWebRequest $request)
  {
    $manager = listingManager::getInstance();

    $listing = ListingTable::getInstance()->findOneById($request->getParameter('listId'));
    $this->forward404Unless($listing);

    $sitetree = SitetreeTable::getInstance()->findOneById($listing->sitetree_id);

    // load up the item we want to edit
    $template = $listing->template;
    $itemClass = $manager->getListItemClass($template);
    $item = Doctrine_Core::getTable($itemClass)->findOneById($request->getParameter('id'));
    $this->forward404Unless($item, 'No item with that id');

    $item->delete();
    $this->itemHasChanged($item);
    $this->listingHasChanged($listing);

    $this->getUser()->setFlash('listing_notice', 'Item has been deleted');

    $this->redirect("listingAdmin/edit?id=$item->listing_id");
  }

  /**
   * Get the pager used on the listing edit page which lists the items
   *
   * @param listing $listing
   * @return sfDoctrinePager
   */
  protected function getPager($listing)
  {
    $manager = listingManager::getInstance();
    $template = $listing->template;
    $pagerClass = $manager->getListItemPagerClass($template);

    $pager = new $pagerClass($listing);
    $pager->setMaxPerPage($listing->results_per_page);
    $pager->getQuery()->addWhere('listing_id = ?', array($listing->id));

    if ($listing->use_custom_order)
    {
      $pager->getQuery()->orderBy('ordr');
    }
    else
    {
      $pager->getQuery()->orderBy($manager->getListItemOrdering($template));
    }

    return $pager;
  }

  /**
   * @param sfForm $form
   * @param string $formRequestVar
   */
  protected function bindItemForm($form, $formRequestVar)
  {
    $request = $this->getRequest();

    if ($form->isMultipart())
    {
      $this->form->bind(
      $request->getParameter($formRequestVar),
      $request->getFiles($formRequestVar)
      );
    }
    else
    {
      $this->form->bind($request->getParameter($formRequestVar));
    }
  }

  /**
   * The live content of this listing has been changed
   *
   * @param listing $listing
   */
  protected function listingHasChanged($listing)
  {
    $listing->handleContentChanged();
  }

  /**
   * The live content of this item has been changed
   *
   * @param listingItem $item
   */
  protected function itemHasChanged($item)
  {
    $item->handleContentChanged();
  }
}
