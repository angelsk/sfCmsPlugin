<?php

/**
 * iframe actions.
 *
 * @package    site_cms
 * @subpackage iframeDisplay
 * @author     Jo Carter
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class iframeDisplayActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfRequest $request A request object
  */
  public function executeIndex(sfWebRequest $request)
  {
    $sitetree = siteManager::getInstance()->initCurrentSitetreeNode();
    $iframe   = IframeTable::getInstance()->findOneBySitetreeId($sitetree->id);
    
    $this->forward404Unless($iframe, 'No iframe info found');
    $this->forward404Unless((!empty($iframe->url) || !empty($iframe->file_name)), 'No target set for iframe');
    
    $this->iframeTarget = $iframe->url;
    
    if (empty($this->iframeTarget))
    {
      $this->iframeTarget = $this->generateUrl(str_replace('@','',siteManager::getInstance()->getRoutingProxy()->generateInternalUrl($sitetree, 'render')));
    }
    
    $this->setLayout($iframe->layout);
    
    $this->setVar('sitetree', $sitetree, true);
  }
  
  /**
   * Render file contents into a page for iframe
   * 
   * @param sfWebRequest $request
   */
  public function executeRender(sfWebRequest $request)
  {
    $sitetree = siteManager::getInstance()->initCurrentSitetreeNode();
    $iframe   = IframeTable::getInstance()->findOneBySitetreeId($sitetree->id);
    
    $this->forward404Unless($iframe, 'No iframe found');
    
    $config   = sfConfig::get('app_site_iframe', array());
    $location = $config['folder'];
    $fileName = $location . DIRECTORY_SEPARATOR . $iframe->file_name;
    
    $this->forward404Unless(is_file($fileName), 'File no longer exists');
    
    $content = file_get_contents($fileName);
    
    // Check whether in iframe - need to inject some javascript
    $sitetreeUrl = $this->generateUrl($sitetree->route_name, array(), true);
    $js          = <<<EOF
    <script type="text/javascript">
      if (parent.location.href === window.location.href) window.location.href = '$sitetreeUrl';
    </script>
EOF;
    $content = str_ireplace('<body>', "<body>\n".$js."\n", $content);
    
    sfConfig::set('sf_web_debug', false);
    
    return $this->renderText($content);
  }
}
