<?php

/**
 * listing admin components.
 *
 * @package    site_cms
 * @subpackage listingAdmin
 * @author     Jo Carter
 * @version    SVN: $Id: components.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class listingAdminComponents extends sfComponents 
{
	public function executeCategoryEditor(sfWebRequest $request) 
	{
		$listing = $this->getVar('listing');
		
		// Delete
		if ($request->hasParameter('deleteCategory')) 
		{
			$catId = $request->getParameter('deleteCategory');
			$category = listingCategoryTable::getInstance()->findOneById($catId);
			$category->delete();
			
			$this->getUser()->setFlash('notice', 'Category deleted');
		}
		
		// Edit existing
		if ($request->hasParameter('editCategory')) 
		{
			$catId = $request->getParameter('editCategory');
			$category = listingCategoryTable::getInstance()->findOneById($catId);
			$form = new listingCategoryForm($category);
			
			if ($request->hasParameter('listing_category')) 
			{
				$form->bind($request->getParameter('listing_category'));
				
				if ($form->isValid()) 
				{
					$form->save();
					$this->getUser()->setFlash('notice', 'Category updated');
				}
			}
			
			$this->setVar('editCategoryName', $category->getTitle());
		}
		// Add new
		else 
		{
			$form = new listingCategoryForm();
			$form->setDefault('listing_id', $listing->id);
			
			if ($request->hasMethod(sfWebRequest::POST) && $request->hasParameter('listing_category')) 
			{
				$form->bind($request->getParameter('listing_category'));
				
				if ($form->isValid()) 
				{
					$form->save();
					$form = new listingCategoryForm();
					$this->getUser()->setFlash('notice', 'Category added');
				}
			}
			
			$this->setVar('editCategoryName', false);
		}
		
		// Or move up and down
		if ($request->hasParameter('upCategory')) 
		{
			$catId = $request->getParameter('upCategory');
			$category = listingCategoryTable::getInstance()->findOneById($catId);
			$category->moveUp();
		}
		else if ($request->hasParameter('downCategory')) 
		{
			$catId = $request->getParameter('downCategory');
			$category = listingCategoryTable::getInstance()->findOneById($catId);
			$category->moveDown();
		}
		
		$currentCategories = listingCategoryTable::getInstance()->findByListingId($listing->id);
		
		$this->setVar('currentCategories', $currentCategories);
		$this->setVar('form', $form);
	}
}