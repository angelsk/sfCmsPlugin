<?php
$this->installDir(dirname(__FILE__).'/skeleton');

$this->logSection('install', 'Setting up CMS project');

$this->logSection('install', 'Creating applications');
$this->runTask('generate:app', 'frontend');
$this->runTask('generate:app', 'backend');

$this->logSection('install', 'Reloading');
$this->reloadTasks();
$this->reloadAutoload();

$this->logSection('install', 'Name the project');
$project_name = $this->ask('What is the site name?');
$this->replaceTokens(array(sfConfig::get('sf_config_dir')), array('PROJECTNAME' => $project_name));
$site_identifier = $this->ask('What is the identifier for this site (max 3 characters)');
$site_identifier = substr($site_identifier, 0, 3);
$this->replaceTokens(array(sfConfig::get('sf_config_dir')), array('SITEIDENTIFIER' => $site_identifier));

// @TODO: This doesn't seem to load the CMS plugins despite reloading config.
//$this->logSection('install', 'Publishing plugin assets');
//$this->runTask('plugin:publish-assets');

// @TODO: This doesn't seem to load the CMS plugins despite reloading config.
//$this->logSection('install', 'Create database and add data');
//$this->runTask('doctrine:build', '--all --and-load');

$this->logSection('install', 'Finish up');
$this->runTask('project:permissions');
$this->runTask('cc');
