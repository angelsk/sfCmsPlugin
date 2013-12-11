<?php
function _exec()
{
  global $filesystem;
  $args = func_get_args();
  $command = array_shift($args);
  return $filesystem->execute(vsprintf($command, array_map('escapeshellarg', $args)));
}

global $filesystem;
$filesystem = $this->getFilesystem();

$properties   = parse_ini_file(sfConfig::get('sf_config_dir').'/properties.ini', true);
$isSubversion = file_exists('.svn');

$plugins = array(
  'sfCmsPlugin'                   => 'https://github.com/HollerLondon/sfCmsPlugin.git/trunk',
  'sfDoctrineGuardPlugin'         => 'http://svn.symfony-project.com/plugins/sfDoctrineGuardPlugin/trunk/',
  'sfImagePoolPlugin'             => 'https://github.com/HollerLondon/sfImagePoolPlugin.git/trunk',
  'sfThumbnailPlugin'             => 'http://svn.symfony-project.com/plugins/sfThumbnailPlugin/branches/1.3/',
  'sfDoctrineActAsTaggablePlugin' => 'http://svn.symfony-project.com/plugins/sfDoctrineActAsTaggablePlugin/branches/1.2/',
  'sfMooToolsFormExtraPlugin'     => 'https://github.com/HollerLondon/sfMooToolsFormExtraPlugin.git/trunk',
  'sfFeed2Plugin'                 => 'http://svn.symfony-project.com/plugins/sfFeed2Plugin/trunk/',
  //'ysfDimensionsPlugin'          => 'http://svn.symfony-project.com/plugins/ysfDimensionsPlugin/branches/1.4/' // Not enabled by default
);                                 
                                   
$extensions = array(               
  'Orderable'                     => 'https://github.com/HollerLondon/Doctrine-Orderable.git/trunk',
  'Blamable'                      => 'http://svn.doctrine-project.org/extensions/Blameable/branches/1.2-1.0/'
);                                 
                                   
$vendors = array(                  
  'symfony'                       => 'http://svn.symfony-project.org/branches/1.4',
  'Datepicker'                    => 'https://github.com/angelsk/mootools-datepicker.git/trunk',
  'MooEditable'                   => 'https://github.com/angelsk/mooeditable.git/trunk',
  'mooRainbow'                    => 'https://github.com/angelsk/mooRainbow.git/trunk',
  'Autocompleter'                 => 'https://github.com/angelsk/mootools-autocompleter.git/trunk'
);

$this->logSection('install', 'Creating applications');
$this->runTask('generate:app', 'frontend');
$this->runTask('generate:app', 'backend');

// Removing files before installing so we can just overwrite
$filesystem->remove(sfConfig::get('sf_upload_dir').'/assets');
$filesystem->remove(sfConfig::get('sf_config_dir').'/ProjectConfiguration.class.php');
$filesystem->remove(sfConfig::get('sf_apps_dir').'/backend/config/security.yml');
$filesystem->remove(sfConfig::get('sf_apps_dir').'/backend/config/filters.yml');
$filesystem->remove(sfConfig::get('sf_apps_dir').'/backend/lib/myUser.class.php');
$filesystem->remove(sfConfig::get('sf_apps_dir').'/frontend/config/app.yml');

foreach (array('view.yml', 'settings.yml', 'factories.yml') as $file)
{
  $filesystem->remove(sfConfig::get('sf_apps_dir').'/frontend/config/'.$file);
  $filesystem->remove(sfConfig::get('sf_apps_dir').'/backend/config/'.$file);
}

// Replace with skeleton
$this->logSection('install', 'Setting up CMS project');
$this->installDir(dirname(__FILE__).'/skeleton');
_exec('mkdir -p lib/doctrine_extensions');

// Externals
if ($isSubversion)
{
  $this->logSection('install', 'Installing plugins, extensions and vendors');
  
  _exec('svn add lib/doctrine_extensions');
    
  // install plugins as svn externals
  $externals = '';
  foreach ($plugins as $name => $path)
  {
    $externals .= $name.' '.$path.PHP_EOL;
  }
  _exec('svn ps svn:externals %s %s', trim($externals), sfConfig::get('sf_plugins_dir'));

  // install vendors as svn externals
  $externals = '';
  foreach ($vendors as $name => $path)
  {
    $externals .= $name.' '.$path.PHP_EOL;
  }
  _exec('svn ps svn:externals %s %s', trim($externals), sfConfig::get('sf_lib_dir').'/vendor');
  
  // install doctrine extensions as svn externals
  $externals = '';
  foreach ($extensions as $name => $path)
  {
    $externals .= $name.' '.$path.PHP_EOL;
  }
  _exec('svn ps svn:externals %s %s', trim($externals), sfConfig::get('sf_lib_dir').'/doctrine_extensions');
  
  $this->logSection('info', 'If you are using multiple sites, you will need to install the dimension plugin manually');
}
else 
{
  $this->logSection('info', 'As you are not using SVN you will have to manually add the plugins, vendors and doctrine_extensions listed in the README');
}


$this->logSection('install', 'Reloading');
$this->reloadTasks();
$this->reloadAutoload();

$this->logSection('install', 'Name the project');
$project_name = $this->ask('What is the site name?');
$this->replaceTokens(array(sfConfig::get('sf_config_dir')), array('PROJECTFNAME' => $project_name));

// Project name
foreach (array(
  sfConfig::get('sf_apps_dir').'/backend/config/factories.yml',
  sfConfig::get('sf_apps_dir').'/backend/config/view.yml',
  sfConfig::get('sf_apps_dir').'/frontend/config/factories.yml',
  sfConfig::get('sf_apps_dir').'/frontend/config/view.yml',
  sfConfig::get('sf_apps_dir').'/frontend/config/app.yml',
  sfConfig::get('sf_apps_dir').'/frontend/config/unavailable.php',
  sfConfig::get('sf_apps_dir').'/frontend/config/error/error.html'
) as $file)
{
  $filesystem->replaceTokens($file, '##', '##', array('PROJECTNAME' => str_replace(' ', '_', strtolower($properties['symfony']['name']))));
  $filesystem->replaceTokens($file, '##', '##', array('PROJECTFNAME' => $project_name));
}

$site_identifier = $this->ask('What is the identifier for this site (max 3 characters)');
$site_identifier = substr($site_identifier, 0, 3);
$this->replaceTokens(array(sfConfig::get('sf_config_dir')), array('SITEIDENTIFIER' => $site_identifier));

$this->logSection('install', 'Finish up');
$this->runTask('project:permissions');
$this->runTask('cc');

$this->logSection('info', 'FINISHED: Please update your project, build your models, install plugin assets and run mootools:install-assets');
