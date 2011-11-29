sfCmsPlugin
===========

Introduction
------------

A simple CMS plugin for Symfony 1.4 that uses dynamic routing to create a (potentially multisite) sitetree - into which any module can be plugged.

Dependancies
------------

### PHP
 * PHP 5.2.4 or later
 * symfony 1.3/1.4

### Symfony

 * Blamable[http://svn.doctrine-project.org/extensions/Blameable/branches/1.2-1.0/] (external in lib/doctrine_extensions)
 * sfDoctrineGuardUserPlugin[http://www.symfony-project.org/plugins/sfDoctrineGuardPlugin] (for the Blamable extension)
 * ysfDimensionsPlugin[http://www.symfony-project.org/plugins/ysfDimensionsPlugin] (for multisite sites)

Setup
-----

If it's a fresh setup of a project, you can use the installer to create a skeleton project using the `sfCmsPlugin`.

    php path/to/symfony generate:project PROJECT_IDENTIFIER --installer=plugins/sfCmsPlugin/data/installer.php

Otherwise you will need to add the following configuration to your existing project in `config/app.yml` for the main site.  For
multiple sites, you will add a new config file per dimension (as specified in the `ysfDimensionsPlugin` README).

	# Site configuration
	all:
	  site:  # you'll want one of these for each dimension if you have multiple sites
		identifier:				##SITENAME##
        definition:
          name: 				##PROJECTNAME##
          cultures: 			[en_GB]
	      default_culture: 		en_GB
      	
	    # the default site (used in the admin area)
        default_site: 			##SITENAME##
     
        available_modules:
          - sitemap

###Further setup

Follow the instructions in `ysfDimensionsPlugin` if you have multiple sites to set up your configuration files.

In `config/dimensions.yml` define the allowed sites:

    allowed:
	  site:         [ ##SITENAME## ]
	default:        ##SITENAME##

@TODO: Figure out best way to set dimensions.... filter?

And set the default dimension in `ProjectConfiguration::setup()`.  It is advisable if using multiple sites with different domains to 
set the `SITE_ENV` variable `site` to the site dimension you require in the vhost.  Otherwise it'll be part of the routing. 

    // setup dimensions before calling parent::setup();
    $this->setDimension(array('site' => isset($_REQUEST['site']) ? $_REQUEST['site'] : '##SITENAME##')); // no config available in this method

You'll need to delete the default homepage route in the frontend app; and in the backend app you'll need to enable the sitemap module.