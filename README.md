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

 * [http://svn.doctrine-project.org/extensions/Blameable/branches/1.2-1.0/ Blamable] (external in lib/doctrine_extensions)
 * [http://www.symfony-project.org/plugins/sfDoctrineGuardPlugin sfDoctrineGuardUserPlugin] (for the Blamable extension)
 * [http://www.symfony-project.org/plugins/ysfDimensionsPlugin ysfDimensionsPlugin] (for multiple sites)

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
		  root_module:        default
      	
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

@TODO: Figure out best way to set dimensions.... filter? based on domain?

And set the default dimension in `ProjectConfiguration::setup()`.  It is advisable if using multiple sites with different domains to 
set `SITE_ENV` (apache) or fastcgi_param (nginx) `site` to the site dimension you require in the vhost.  Otherwise it'll be part of the routing. 

    // setup dimensions before calling parent::setup();
    $this->setDimension(array('site' => isset($_REQUEST['site']) ? $_REQUEST['site'] : '##SITENAME##')); // no config available in this method

You'll need to delete the default `@homepage` route in the frontend app; and in the backend app you'll need to enable the sitemap module.

Custom modules
--------------

To make use of a dynamic sitetree, but have your own modules you need to add a `module.yml` config file to the module config folder, and enable the module
in the `app.yml` under `available_modules` (this will enable you to select it in the module dropdown on the sitetree edit/add form).

	# Site configuration all:
	site: 
	  .....
	  available_modules: 
	    - sitemap
	    - newModule

The module can handle itʼs own events - including what happens on sitetree deletion and with sitetree routing. If the new module only requires one route 
based on the URL entered, then leave the routing as default.

	all: 
	  site:
	   module_definition: 
	    name: "New module" 
	    # This is a required field if you want the event handler to be called 
	    # If you have no admin module, but need to handle events - use sitetree/index 
	    admin_url: moduleAdmin/editByRoute 
	    # If you need to set extra routes for the module 
	    use_custom_routing: true 
	    event_handler: [newModuleClass, siteEventHandler]

The event handler manages the sitetree events (as required).

	<?php
	class newModuleClass 
	{ 
	  /**
	   * Handle the site events - e.g: routing 
	   * 
	   * @param siteEvent $event 
	   */
	  public static function siteEventHandler($event) 
	  { 
		if ($event->getName() == siteEvent::SITETREE_DELETE) 
		{
	      // node has been deleted
	      $sitetree = $event->getSubject();
     
	      // handle delete
	    } 
	    else if ($event->getName() == siteEvent::SITETREE_ROUTING) 
	    {
	      // handle the routing... i.e: register our routes.
	      $sitetree = $event->getSubject(); 
	      $params = $event->getParameters(); 
	      $routingProxy = $params['routingProxy']; 
	      $urlStack = $params['urlStack'];
	      $nodeUrl = sitetree::makeUrl($sitetree, $urlStack);

	      // add in index route
	      $routingProxy->addRoute( 
							$sitetree,
							'', 
							$nodeUrl, 
							array('module' => 'newModule', 'action' => 'index')
						);
		  // further routes
		}
	  }
	}


In the frontend module, you should initialise the sitetree node. This will both sort out the meta information, and provide you with the current sitetree node.

	$siteManager = siteManager::getInstance(); 
	$sitetreeNode = $siteManager->initCurrentSitetreeNode();

Then continue as required.