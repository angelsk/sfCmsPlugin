<?php
/**
 * Helper functions for the super pager
 *
 * You can overide any of these functions in your own code by defining them before
 * this helper is included as function_exists() is used before all declarations.
 */

if (!function_exists('super_pager_render')) {
	/**
	 * Render a pager
	 *
	 * You can call the individual rendering functions for each part seperately.
	 *
	 * @param sfDoctrineSuperPager $pager
	 * @param string $pageUrl The url of the page we're currently on, used for non-javascript pagination
	 * @param string $sourceUrl The source url which will be used to access the json data source for AJAX pagination
	 * @return string
	 */
	function super_pager_render($pager, $pageUrl, $sourceUrl) {
		$out = '';

		if ($pager->getFilterForm() && 0 < $pager->getNbResults()) {
			$out .= '<div id="sf_admin_bar"><div class="sf_admin_filter">';
			$out .= super_pager_render_form(
			$pager,
			$pageUrl
			);
			$out .= "</div></div>";
		}

		$out .= '<div id="sf_admin_content"><div class="sf_admin_list">';

		if (0 < $pager->getNbResults()) {
			$out .= "<table id='" . $pager->getResultsTableId() . "' class='superPager'>\n";
			$out .= "<thead>" . super_pager_render_thead($pager) . "</thead>\n";
			$out .= "<tbody>" . super_pager_render_tbody($pager) . "</tbody>\n";
			$out .= "<tfoot>" . super_pager_pagination($pager, $pageUrl) . "</tfoot>\n";
			$out .= "</table>\n";
				
			$out .= super_pager_javascript($pager, $sourceUrl);
		}
		else {
			$out .= "<p><strong>No results</strong></p>";
		}

		$out .= "</div></div>";

		return $out;
	}
}

if (!function_exists('super_pager_init_js')) {
	/**
	 * Initialise the javascript for the super pager
	 *
	 * This adds the required javascript and css into the current request
	 */
	function super_pager_init_js($includeAutocomplete=false) {
	}
}

if (!function_exists('super_pager_get_url')) {
	/**
	 * Get a sensible concatenated url
	 *
	 * @param sfDoctrineSuperPager $pager
	 * @param string $baseUrl
	 * @param bool $includeOrder
	 * @param bool $includePage
	 */
	function super_pager_get_url($pager, $url, $includeOrder=false, $includePage=false) {
		$form = $pager->getFilterForm();
		$mustAddAmp = true;
		if ($form) {
			// add in the form fields
			if (!strstr($url, "?")) {
				$url .= "?";
				$mustAddAmp = false;
			} else {
				$mustAddAmp = true;
			}

			foreach ($form->getValues() as $name => $value) {

				if (is_array($value))
				{
					foreach ($value as $v)
					{
						if ($mustAddAmp) {
							$url .= "&amp;";
						}
						$url .= $form->getWidgetSchema()->generateName($name) . "=" . urlencode($v);
						$mustAddAmp = true;
					}
				}
				else
				{
					if (strlen($value) > 0)
					{
						if ($mustAddAmp) {
							$url .= "&amp;";
						}
						$url .= $form->getWidgetSchema()->generateName($name) . "=" . urlencode($value);
						$mustAddAmp = true;
					}
				}
			}
		}

		// add in ordering information
		if ($includeOrder)
		{
			if (!strstr($url, "?"))
			{
				$url .= "?";
				$mustAddAmp = false;
			}
			if ($mustAddAmp)
			{
				$url .= "&amp;";
			}

			$url .= 'orderCol' . $pager->getOrderCol();
			$url .= '&amp;orderAsc' . $pager->getOrderAsc();
		}

		if ($includePage && 1 < $pager->getPage())
		{
			if (!strstr($url, "?"))
			{
				$url .= "?";
				$mustAddAmp = false;
			}
			if ($mustAddAmp)
			{
				$url .= "&amp;";
			}

			$url .= 'page='.$pager->getPage();
		}

		return $url;
	}
}

if (!function_exists('super_pager_pagination')) {
	/**
	 * Render the pagination part of a superpager :)
	 *
	 * @param sfDoctrineSuperPager $pager
	 * @param string $url
	 * @return string
	 */
	function super_pager_pagination($pager, $url) {
		$colspan = count($pager->getColumnDefinitions());
		$out = "<tr><th colspan='{$colspan}'>";

		$out .= '<div class="superPagerPagination" id="' . $pager->getId() . 'Pagination">';

		// we do this because the symfony routing breaks with variables in the array format,
		// eg. filter[name].
		// This is a problem because this is also the format used by sfForm by default.
		$url = url_for($url, true);

		$form = $pager->getFilterForm();
		$filters = ($form ? $form->getValues() : array());
		foreach ($filters as $name => $value) {
			if (empty($value)) unset($filters[$name]);
		}

		if ($form && !empty($filters)) {
			// add in the form fields
			if (!strstr($url, "?")) {
				$url .= "?";
				$mustAddAmp = false;
			}
			else {
				$mustAddAmp = true;
			}
				
			foreach ($form->getValues() as $name => $value) {
				if (strlen($value) > 0) {
					if ($mustAddAmp) {
						$url .= "&";
					}
						
					$url .= $form->getWidgetSchema()->generateName($name) . "=" . urlencode($value);
					$mustAddAmp = true;
				}
			}
		}

		$out .= super_pager_navigation($pager, $url);
		$out .= "</div></th></tr>";

		return $out;
	}
}


if (!function_exists('super_pager_navigation')) {
	/**
	 * Pager navigation
	 *
	 * Modified version of the old favourite at: http://www.symfony-project.org/snippets/snippet/4
	 *
	 * @param sfDoctrineSuperPager $pager
	 * @param string $uri This is an external url, not an internal symfony url
	 * @param string $pageVarName
	 * @return string
	 */
	function super_pager_navigation($pager, $uri, $pageVarName="page") {

		$navigation = $pager->getNbResults() . " result";
		if ($pager->getNbResults() != 1) {
			$navigation .= 's';
		}

		if ($pager->haveToPaginate()) {
			$baseUri = $uri;
			$uri .= (preg_match('/\?/', $baseUri) ? '&' : '?').$pageVarName.'=';
			$currentPage = $pager->getPage();

			// Previous page
			if ($currentPage != $pager->getPreviousPage()) {
				if (1 == $pager->getPreviousPage())  $navigation .= '<a href="' . $baseUri . '" class="previous">&lt; Previous</a>';
				else $navigation .= '<a href="' . $uri.$pager->getPreviousPage() . '" class="previous">&lt; Previous</a>';
			}

			// Pages one by one
			$links = array();
			foreach ($pager->getLinks() as $page) {
				$class = $page == $pager->getPage() ? 'sel' : '';
				if (1 == $page) $navigation .= "<a href='$baseUri' class='$class'>$page</a>";
				else $navigation .= "<a href='$uri$page' class='$class'>$page</a>";
			}
				
			// Next page
			if ($pager->getLastPage() != $currentPage) {
				$navigation .= '<a href="' . $uri.$pager->getNextPage() . '" class="next">Next &gt;</a>';
			}
		}

		return $navigation;
	}
}


if (!function_exists('super_pager_render_thead')) {
	/**
	 * Render the thead in html
	 *
	 * @param sfDoctrineSuperPager $pager
	 * @return string
	 */
	function super_pager_render_thead($pager) {
		$out = "<tr>\n";
		foreach ($pager->getColumnDefinitions() as $colDef) {
			$out .= "<th>\n";
			$out .= esc_entities($colDef['name']);
			$out .= "</th>\n";
		}
		$out .= "</tr>\n";
		return $out;
	}
}


if (!function_exists('super_pager_render_tbody')) {
	/**
	 * Render the tbody in html
	 *
	 * @param sfDoctrineSuperPager $pager
	 * @return string
	 */
	function super_pager_render_tbody($pager) {
		$out = "";
		foreach ($pager->getResults() as $row) {
			$out .= "<tr>\n";
			foreach ($pager->renderRow($row) as $cell) {
				$out .= content_tag("td", $cell[0], isset($cell[1]) ? $cell[1] : array());
			}
			$out .= "</tr>\n";
		}
		return $out;
	}
}


if (!function_exists('super_pager_javascript')) {
	/**
	 * Render the javascript to initialise a superpager.
	 *
	 * If this is not called, your pager will not use javascript at all.
	 *
	 * @param unknown_type $pager The pager we want to render
	 * @param unknown_type $ajaxUrl The (internal) url for the ajax pager results
	 * @return String
	 */
	function super_pager_javascript($pager, $ajaxUrl) {
		super_pager_init_js();

		$out = '';
		/*$out .= "Event.observe(window, 'load', function () {\n";
		 $out .= "	new sfDoctrineSuperPager(\n";
		 $out .= "		'{$pager->getId()}',\n";
		 $out .= "		'" . url_for($ajaxUrl) . "',\n";
		 $out .= "		" . json_encode($pager->getDataForClient()) . "\n";
		 $out .= "	);\n;";
		 $out .= "});\n";*/

		//return javascript_tag($out);
		return $out;
	}
}


if (!function_exists('super_pager_render_form')) {
	/**
	 * Render the filter form
	 *
	 * @param sfDoctrineSuperPager $pager
	 */
	function super_pager_render_form($pager, $url) {
		if (!$form = $pager->getFilterForm()) {
			// no form to render!
			return '';
		}

		$url = url_for($url);

		$out = "<form method='get' action='$url' id='{$pager->getId()}FilterForm'>";
		$out .= "<table>";
		$out .= $form->__toString();
		$out .= "<tr><td colspan='2'><input type='submit' name='submit' value='Filter results' /></td></tr>";
		$out .= "</table>";
		$out .= "</form>";
		return $out;
	}
}


if (!function_exists('super_pager_autocomplete')) {
	/**
	 * Initialise an autocomplete field
	 *
	 * @param string $idInputId The id of the (usually) hidden field we're using to store the id of the selected item
	 * @param string $descriptionInputId The id of the input field we're using for the search text/description
	 * @param string $url The internal url of the AJAX source for the pager
	 * @return unknown
	 */
	function super_pager_autocomplete($idInputId, $descriptionInputId, $url, $statusElId) {
		super_pager_init_js(true);

		$options = array(
			'statusEl' => $statusElId
		);

		return javascript_tag("
			new sfDoctrineSuperPager.Autocomplete(
				$('{$descriptionInputId}'), //inputEl,
				$('{$idInputId}'), //valueEl,
				'" . url_for($url) . "', //url,
				" . json_encode($options) . "
			)
		");
	}
}