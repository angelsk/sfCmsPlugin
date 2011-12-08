<?php

/**
 * PluginSitetreeTranslation form.
 *
 * @package    sfCmsPlugin
 * @subpackage form
 * @author     Jo Carter <work@jocarter.co.uk>
 * @version    SVN: $Id: sfDoctrineFormPluginTemplate.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
abstract class PluginSitetreeTranslationForm extends BaseSitetreeTranslationForm
{
  public function setup()
  {
    parent::setup();
    
    $this->widgetSchema->setLabel('title', 'Title <em>*</em>');
    $this->widgetSchema->setLabel('html_title', 'Meta title');
    $this->widgetSchema->setLabel('html_keywords', 'Keywords');
    $this->widgetSchema->setLabel('html_description', 'Meta description');
    $this->widgetSchema->setLabel('link_title', 'Link title');
    
    $this->widgetSchema->setHelp('html_title', '(SEO) This is the title displayed in the browser toolbar');
    $this->widgetSchema->setHelp('link_title', '(SEO) Usually the title attribute of a link to this page will be the page name, this setting overrides that');
    
    if ($this->isNew())
    {
      $this->widgetSchema->setHelp('title', 'Start typing, and the form will automatically generate the URL and route name for the page');
    }
  }
}
