sfCmsPlugin
===========

Introduction
------------

A simple CMS plugin for Symfony 1.4 that uses dynamic routing to create a (potentially multisite) sitetree - into which any module can be plugged.

Default configuration is specified in the plugin's `config/app.yml`.

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

	# Basic site configuration
	all:
	  site:  # you'll want one of these for each dimension if you have multiple sites
		identifier:				##SITENAME##
        definition:
          name: 				##PROJECTNAME##
          cultures: 			[en_GB]
	      default_culture: 		en_GB
		  root_module:          default			# You will need to ensure you have a module.yml for whichever module this is - see below
      	
	    # the default site (used in the admin area)
        default_site: 			##SITENAME##
     
        available_modules:
          - sitemap

Finally, enable the sitetree module in your backend app's `settings.yml`.  

    enabled_modules:
      - sfGuardAuth
      - sfGuardUser
      - sitetree

The first visit to the admin sitetree module will set up the sitetree (including the root node - based on the config above).  You will 
need to publish the root node and delete the default `@homepage` route in the frontend app to use the dynamic routing.


Multiple Sites Setup
--------------------

Follow the instructions in `ysfDimensionsPlugin` if you have multiple sites to set up your configuration files.

In `config/dimensions.yml` define the allowed sites:

    allowed:
	  site:         [ ##SITENAME##, ##SITENAME2## ]
	default:        ##SITENAME##

With this plugin dimensions are used to control the site (and cultures are handled within these).  If your setup is one domain per site, you can set
the dimension on the frontend app based on the URL by using a filter.  You will need to set up the URL to dimensions relationship in your  `config/app.yml`

    # Site configuration
    all:
      site:
        identifier:         gb
        definition:
          name:             UK site
          cultures:         [en_GB]
          default_culture:  en_GB
          
        # the default site
        default_site:     gb
        
        ......
      
      # when the config has load this will determine which dimension is set based on the URL
      # it will always default to the default_site above if the domain doesn't match
      dimensions:
        'www.example.co.uk':     gb
        'www.example.fr':        fr

and add the following to your app's `filters.yml`

    # insert your own filters here
    dimension:
      class:      siteDimensionUrlFilter

Also set the default dimension in `ProjectConfiguration::setup()` - this is so the command line doesn't error out as the configuration is loaded after.

    // setup dimensions before calling parent::setup();
    $this->setDimension(array('site' => '##SITENAME##'); // no config available at this point

Custom modules
--------------

To make use of a dynamic sitetree with your own modules you need to add a `module.yml` config file to the module config folder, and enable the module
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

The event handler manages the sitetree events (as required).  This can either be a global class with a method per module (so the events are managed in one place),
or per module (maybe in a model file).

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
	      $site		= $sitetree->site;
     
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
						
		  // further route examples
		 $routingProxy->addRoute( 
           				    $sitetree,
				            'page', 
				            $nodeUrl . '/page/:page', 
				            array('module' => 'newModule', 'action' => 'index', 'page' => 1),
				            array('page' => '\d+')
				          );
				
		  $routingProxy->addRoute( 
							$sitetree,
							'item', 
							$nodeUrl . '/:slug, 
							array('module' => 'newModule', 'action' => 'item')
						);
		}
	  }
	}


In the frontend module, you should initialise the sitetree as this will both sort out the meta information, and provide you with the current tree node.

	$siteManager = siteManager::getInstance(); 
	$sitetreeNode = $siteManager->initCurrentSitetreeNode();

Then continue with the module's functionality.