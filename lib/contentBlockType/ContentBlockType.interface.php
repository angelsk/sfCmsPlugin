<?php

/**
 * Interface specifying the methods to be implemented for each Content block
 * 
 * Classes should implement this and extend the base class
 * 
 * @author Jo
 *
 */
interface ContentBlockTypeInterface 
{
	/**
     * Render this Content block.  
     * 
     * It should return the HTML to be used on the frontend app.
     *
     * @return string
     */
	public function render();
	
	/**
	 * Render this Content block from the value in the request.
	 * 
	 * This is used to implement previews of this Content block without needing to save.
	 *
	 * @param sfWebRequest $request
	 */
	public function renderFromRequest(sfWebRequest $request);
	
	/**
	 * Render editing HTML - from ContentBlockTypeForm implementation
	 * 
	 * Must return HTML for a form control to edit this Content block.  It can make 
	 * use of getFormName().
	 *
	 * @param sfWebRequest $request
	 */
	public function editRender(sfWebRequest $request);	
		
	/**
	 * Is the edit valid?
	 * 
	 * Must check that the value in $request for this Content block is valid.  If it is,
	 * it returns true.  If it is wrong, it must return an array of errors which 
	 * will be displayed to the user.
	 *
	 * @param sfWebRequest $request
	 */
	public function editIsValid(sfWebRequest $request);
	
	/**
	 * Has this Content block been changed?
	 * 
	 * Must check the value in $request and see if it is different from the current
	 * value for the Content block.
	 *
	 * @param sfWebRequest $request
	 */
	public function editIsChanged(sfWebRequest $request);
	
	/**
	 * Duplicate and save
	 * 
	 * Save the value of this Content block from the request onto the new ContentBlockVersion
	 * given.  
	 * 
	 * It must not change the value of the current Content block as that would break versioning.
	 *
	 * @param ContentBlockVersion $newContentBlock
	 * @param sfWebRequest $request
	 */
	public function editDuplicateAndSave(ContentBlockVersion $newContentBlock, sfWebRequest $request);
	
	/**
	 * Return javascript which needs to be called after the html for the editing
	 * has been put into the page.
	 * 
	 * If this is done on the initial editor page load, it will be executed on 
	 * window/load.  If it has been done on an AJAX request, it will be given
	 * to the client to execute immediately.
	 *
	 * @param sfWebRequest $request
	 */
	public function editRenderJavascript(sfWebRequest $request);
} 