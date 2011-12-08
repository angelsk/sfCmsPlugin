<?php

/**
 * A "super pager" for handling search results.
 *
 */
class sfDoctrineSuperPager extends sfDoctrinePager 
{
	/**
	 * The helper function to use for rendering a row for the result set
	 *
	 * @var mixed
	 */
	protected $rowRenderer;
	
	
	/**
	 * Current ordering col
	 *
	 * @var int
	 */
	protected $orderCol = null;
	
	
	/**
	 * Ordering ascending?  (false means descending)
	 *
	 * @var boolean
	 */
	protected $orderAsc;
	
	
	/**
	 * Form used for filtering
	 *
	 * @var sfForm
	 */
	protected $filterForm;
	
	
	/**
	 * The columns of this pager.  An array of the form:
	 *
	 * array(
	 *     array(
	 *         'name' => 'Col1 name',
	 *         'order' => 'DQL for ordering'
	 *     )
	 *
	 * )
	 *
	 * @var array
	 */
	protected $columnDefinitions = null;
	
	
	/**
	 * Make a new super pager
	 *
	 *
	 * The $filterForm is used for filtering the form and is optional.
	 *
	 *
	 *
	 * The $rowRenderer is a helper function which will be called to render a row
	 * of the result.  It must return an array of the following format:
	 *
	 * array(
	 *     array('Col1 as HTML'), // col1 stuff
	 *     array('Col2 as HTML'), // col2 stuff
	 *     array('Col3 as HTML'), // col3 stuff
	 * )
	 *
	 * You can also optionally add in extra attributes to the return value which will
	 * be added to the td element when rendered:
	 *
	 * array(
	 *     array('Col1 as HTML', array('class' => 'myClass')), // col1 stuff
	 *     array('Col2 as HTML'), // col2 stuff
	 *     array('Col3 as HTML'), // col3 stuff
	 * )
	 *
	 *
	 *
	 * The column definitions should be of the form:
	 *
	 * array(
	 *     array(
	 *         'name' => 'Col1 name',
	 *         'order' => 'DQL for col1 ordering'
	 *     ),
	 *     array(
	 *         'name' => 'Col2 name',
	 *         'order' => 'DQL for col2 ordering'
	 *     ),
	 * )
	 *
	 * If you don't provide an order value, or it's blank, the col will not be orderable.
	 *
	 *
	 * @param String $class The class we're searching on.  Is also used as an "id" for the various forms etc.
	 * @param sfForm $filterForm The sfForm used for the pager's filtering.  Can be null if none is required.
	 * @param string $rowRenderer The row renderer. Provide a function name as a string and it will be passed a result row to render.
	 * @param array $columnDefinitions The column definitions for the pager.
	 */
	public function __construct($class, $filterForm = null, $rowRenderer = null, $columnDefinitions=null) 
	{
		parent::__construct($class);
		
		$this->rowRenderer = $rowRenderer;
		$this->filterForm = $filterForm;
		$this->columnDefinitions = $columnDefinitions;
	}
		
	
	/**
	 * Get the id for this pager.
	 *
	 * @return string
	 */
	public function getId() 
	{
		return $this->class;
	}
	
	
	/**
	 * Get the id used for the results table in html
	 *
	 * @return string
	 */
	public function getResultsTableId() 
	{
		return $this->class . "Table";
	}
	
	
	/**
	 * Render a row of the results.
	 *
	 * @param mixed $row
	 * @return array
	 */
	public function renderRow($row) 
	{
		$function = $this->rowRenderer;
		return $function($row);
	}
	
	
	/**
	 * Get the pager data for javascript
	 *
	 * @return array
	 */
	public function getDataForClient() 
	{
		$data = array(
			'rows' => array(),
			'ok' => true,
			'pager' => array(
				'numItems' => $this->getNbResults(),
				'currentPage' => $this->getPage(),
				'itemsPerPage' => $this->getMaxPerPage()
			),
			'columnDefinitions' => $this->columnDefinitions,
			'orderCol' => $this->orderCol,
			'orderAsc' => $this->orderAsc
		);
		
		foreach ($this->getResults() as $row) 
		{
			$data['rows'][] = $this->renderRow($row);
		}
		
		return $data;
	}
	
	
	/**
	 * Set ordering for this pager.  Requires a column key and whether or not
	 * we are ascending.
	 *
	 * @param mixed $col
	 * @param boolean $asc
	 */
	public function setOrder($col, $asc) 
	{
		if (isset($this->columnDefinitions[$col]['order'])) 
		{
			// this is a column we can order
			$this->orderCol = $col;
			$this->orderAsc = !!$asc;
			
			$orderBy = $this->columnDefinitions[$col]['order'];
			if (!$asc) 
			{
				$orderBy .= " DESC";
			}
			$this->getQuery()->orderBy($orderBy);
		}
	}
	
	
	public function getOrderCol() 
	{
	   return $this->orderCol;
	}
  
  
	public function getOrderAsc() 
	{
	   return $this->orderAsc;
 	}
	
	
	/**
	 * Get the filter form - remember it can be null.
	 *
	 * @return sfForm
	 */
	public function getFilterForm() 
	{
		return $this->filterForm;
	}
	
	
	/**
	 * Initialise this pager from the given request - uses default settings.
	 * You can use init() still if you want to.
	 *
	 * @param sfRequest $request
	 */
	public function initFromRequest($request)
	{
		$this->setPage($request->getParameter('page', 1));
		$this->setOrder(
			$request->getParameter('orderCol', 0),
			$request->getParameter('orderAsc', 1)
		);
		
		$this->addFilterValuesToQuery($request);
				
		parent::init();
	}
	
	
	/**
	 * This should be over-ridden.  It is the place in which you would
	 * add things into the query based on the filter form values.
	 *
	 * You can see an example in the pagerExamples module in the plugin.
	 *
	 * @param sfRequest $request
	 */
	public function addFilterValuesToQuery($request) 
	{
	}
	
	
	/**
	 * Set the mappings of columns (as integers) to order strings.
	 *
	 * See the constructor phpdoc for details of the format to use.
	 *
	 * @param array $cols
	 */
	public function setColumnDefinitions($cols) 
	{
		$this->columnDefinitions = $cols;
	}
	
	
	/**
	 * Get the column definitions (if there are any)
	 *
	 * See the constructor phpdoc for details of the format to use.
	 *
	 * @return array
	 */
	public function getColumnDefinitions() 
	{
		return $this->columnDefinitions;
	}
}