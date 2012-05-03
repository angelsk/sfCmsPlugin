<?php

class cmsPopulatesitesTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'backend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'doctrine'),
    ));

    $this->namespace        = 'cms';
    $this->name             = 'populate-sites';
    $this->briefDescription = 'Populates the site table for sfGuardGroup site associations';
    $this->detailedDescription = <<<EOF
The [cms:populate-sites|INFO] task populates the site table for sfGuardGroup site associations; so you can have site specific permissions.
It will also parse [active_sites|INFO] and add any new ones each time a site is added to [active_sites|INFO] config.
Uses the default site config if [active_sites|INFO] not set.
Call it with:

  [php symfony cms:populate-sites|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    // get active sites
    $activeSites = sfConfig::get('app_site_active_sites', array());
    
    if (empty($activeSites))
    {
      // Get default site
      $defn        = sfConfig::get('app_site_definition');
      $activeSites = array(sfConfig::get('app_site_identifier') => $defn['name']);
    }
    
    // get current sites
    $currentSites = SiteTable::getInstance()->findAll();
    
    if (0 < $currentSites->count())
    {
      foreach ($currentSites as $site)
      {
        if (isset($activeSites[$site->site])) unset($activeSites[$site->site]);
      }
    }
    
    // Add new ones
    if (!empty($activeSites))
    {
      foreach ($activeSites as $id => $name)
      {
        $this->logSection('site', sprintf('Adding new site "%s" with identifier: %s', $name, $id));
        $site = new Site();
        $site->setSite($id);
        $site->setName($name);
        $site->save();
      }
    }
    else $this->logSection('info', 'No new sites to add');
  }
}
