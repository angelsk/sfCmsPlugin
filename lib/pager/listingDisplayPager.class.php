<?php
class listingDisplayPager extends sfDoctrineSuperPager 
{
	protected $listing;

	public function __construct($listing) 
	{
		$this->listing = $listing;

		$manager = listingManager::getInstance();
		$itemClass = $manager->getListItemClass($listing->type);

		$cols = array(array('name' => 'Title'),
					  array('name' => 'Move'),
					  array('name' => 'Actions'),
					);

		parent::__construct($itemClass, null, null, $cols);

		$this->getQuery()->addWhere('is_active = ?', true);

		// Set up translation
		$q = $this->getQuery();
		$q->from("$itemClass i");
		$culture = sfContext::getInstance()->getUser()->getCulture();
		$q->leftJoin('i.Translation t ON (i.id = t.id AND t.lang = ?) INDEXBY t.lang', array($culture));
	}
}