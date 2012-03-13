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

 * [sfDoctrineGuardUserPlugin](http://www.symfony-project.org/plugins/sfDoctrineGuardPlugin)
 * [sfImagePoolPlugin](https://github.com/HollerLondon/sfImagePoolPlugin)
 * [sfMooToolsFormExtraPlugin](https://github.com/HollerLondon/sfMooToolsFormExtraPlugin)
 * [Blamable]([http://svn.doctrine-project.org/extensions/Blameable/branches/1.2-1.0/) (external in lib/doctrine_extensions)
 * [Orderable](https://github.com/HollerLondon/Doctrine-Orderable) (external in lib/doctrine_extensions)
 * [ysfDimensionsPlugin](http://www.symfony-project.org/plugins/ysfDimensionsPlugin) (for multiple sites)

### MooTools 1.3.2

Setup
-----

If it's a fresh setup of a project, you can use the installer to create a skeleton project using the `sfCmsPlugin`.

    php path/to/symfony generate:project PROJECT_IDENTIFIER --installer=plugins/sfCmsPlugin/data/installer.php

Otherwise you will need to add the following configuration to your existing project in `config/app.yml` for the main site.  For
multiple sites, you will add a new config file per dimension (as specified in the `ysfDimensionsPlugin` README).

  # Basic site configuration
  all:
    # CMS configuration
	site:
	  identifier:         ##SITENAME##
	  definition:
	    name:             ##FRIENDLYNAME##
	    cultures:         [en]
	    default_culture:  en
	    url_prefix:       http://www.example.com   # optional url_prefix if cms has a different domain to the main site
      root_module:        default      # You will need to ensure you have a module.yml for whichever module this is - see below
        
      # the default site
      default_site:       ##SITENAME##
     
      available_modules:
        - sitemap
        - pageDisplay
        - listingDisplay

Finally, enable the admin modules in your backend app's `settings.yml`.  

    enabled_modules:
      - sfGuardAuth
      - sfGuardUser
      - sitetree
      - pageAdmin
      - listingAdmin
      - contentAdmin

The first visit to the admin sitetree module will set up the sitetree (including the root node - based on the config above).  You will 
need to publish the root node and delete the default `@homepage` route in the frontend app to use the dynamic routing.

Enable the page and listing display modules in the frontend `settings.yml`.

     enabled_modules:
      - pageDisplay
      - listingDisplay


Javascript in the CMS
=====================

If the site includes javascript at the bottom of the template in the CMS then you need to enable the slots setting in site config;
and include the js slot at the bottom of your layout template.

In the config:
     
     site:
       ....
       use_slots: true

In the backend layout:

     <?php include_slot('cms_js') ?>


Multiple Sites Setup
--------------------

Follow the instructions in `ysfDimensionsPlugin` if you have multiple sites to set up your configuration files.

In `config/dimensions.yml` define the allowed sites:

  allowed:
    site:         [ gb, fr ]
  default:        gb

With this plugin dimensions are used to control the site (and cultures are handled within these).  If your setup is one domain per site, you can set
the dimension on the frontend app based on the URL by using a filter.  You will need to set up the URL to dimensions relationship in your  `config/app.yml`

    # Site configuration
    all:
      site:
        identifier:         gb
        definition:
          name:             UK site
          cultures:         [en]
          default_culture:  en
          url_prefix:       'http://www.example.co.uk'
          
        # the default site
        default_site:     gb

		# if you have more than one site (through dimensions) - then list them here for the CMS site select
		active_sites:
	      gb:               'UK site'
	      fr:               'French site'
	
        ......
      
      # when the config has loaded this will determine which dimension is set based on the URL
      # it will always default to the default_site above if the domain doesn't match
      dimensions:
        'www.example.co.uk':     gb
        'www.example.fr':        fr

The dimension is set when the plugin's configuration is initialized so all you need to do is make sure the dimensions are defined in the main app.yml.

Also set the default dimension in `ProjectConfiguration::setup()` - this is so the command line doesn't error out as the configuration is loaded after.

    // setup dimensions before calling parent::setup(); for command line operations
	// Frontend handled by a filter
	if ('cli' == php_sapi_name()) $this->setDimension(array('site' => 'gb'));

When you add a new site, at the very least you'll need to add it to the `active_sites` config array; and create a folder in the main config folder for 
the site specific `app.yml`, e.g: `config/fr/app/yml`. 

This should contain the site configuration, so that the routing knows which routes to load, etc.

    # Site configuration
    all:
      site:
        identifier:         fr
        definition:
          name:             French site
          cultures:         [fr]
          default_culture:  fr
		  url_prefix:       'http://www.example.fr'

For the backend - you'll want to set your `homepage` route to point to `sitetree/changeSite` (or point to this action where you want to implement the 
change site functionality).


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
        $site    = $sitetree->site;
     
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


### Extending site classes

All classes retrieved via the siteManager, listingManager and pageManager (including the managers themselves) can be extended by creating a local copy, setting the class in the configuration and extending the functionality as required.


Pages and Listings
------------------

### CMS Templates

#### Configuration

The configuration for the templates can be added in separate configuration files, depending on how many templates there are.

If they are separate files, then ensure they are included in app.yml under the site: configuration

    site:

      .. # other config here

    <?php
    include(sfConfig::get('sf_root_dir') . "/config/templates.yml");
    ?>

Otherwise, if there are only a few templates - then just add them in config/app.yml

Various classes can be overwritten locally in a project by defining them in the configuration - check out the appropriate manager classes for more information.


### Content blocks

#### Basic configuration

Templates are made up of content blocks.

A content block is a logical separate item of content on a page - for example, an image or a piece of text. It could be something as small as one piece of text, or as large as the entire HTML content of a page.

Each page on the site, whether it's a content page, a listing page or a listing item, is made up of a collection of content blocks.

A content block has an identifier (for example: image_1 OR main_content) and a type (see below for the content block types) at the minimum. Certain content blocks may require additional configuration parameters.

An identifier must be:
 *  Unique (within the template) 
 *  Maximum length of 50 characters 
 *  Only contain letters, numbers or _ - advisably start with a letter to avoid confusion

The minimum declaration of a content block requires an identifier, a name and a type.

The name is the friendly name that is displayed to the user in the CMS, the identifier is used by the code to call the content block, and the type tells the code what the content block should do.

    content_block_1: 
      name: Content Block 1 
      type: ContentBlockType


Content blocks are versioned by default - the CMS saves each edit to a content block so they can be viewed or changed back (reverted) easily.

They can also have a different copy for each language. This is implemented by default, but you can specify it in the config as well, or turn it off. This advisable for images - especially if they don't have text.

      # Different for each language 
      use_lang: true


You can also add help text, which is displayed on the right hand side of the edit page in the CMS. This is useful for editing tips, requirements, dynamic variables, etc.

       help: This field is required, 500 character limit

       # Multiline - don't forget the | 
       help: |
         Lots of help text<br />
         <b>This can contain HTML</b>
         &lt;h3> - escape to show the HTML markup


#### Types - HTML

A piece of text that requires formatting or user controlled content.  Uses a rich text area (RTE) called tinyMCE in the CMS for editing.

The HTML content block only requires the minimum declaration to work, with a content block type: HTML.

    content_block_1: 
      name: Content Block 1 
      type: HTML


The HTML content block can be restricted in length (useful for styling restrictions), and also can be a required field. These are optional and validation is ignored if they are not set.  It is advisable to add a * to the name of required fields.

      character_limit: 500 # excluding HTML 
      required: true


    <?php echo $page->renderContent('content_block_1'); ?>


#### Types - Text

A single piece of format-less text.

The Text content block only requires the minimum declaration to work, with a content block type: Text.

    content_block_1:
      name: Content Block 1
      type: Text


The Text content block can be restricted in length (useful for styling restrictions), and also can be a required field. These are optional and validation is ignored if they are not set.  It is advisable to add a * to the name of required fields.

      character_limit: 50 # excluding HTML 
      required: true


    <?php echo $page->renderContent('content_block_1'); ?>


#### Types - Image

Requires: sfImagePoolPlugin

An image asset - the images can be managed in the Image Pool tab.  The image can either be selected from existing images, or uploaded on the page.

    content_block_1:
      name: Content Block 1
      type: Image
	  tag:  [icon]    # set if tag restriction required, as per sfImagePoolable (none set by default)
	  multiple: false # set if multiple images allowed, as per sfImagePoolable  (false by default)


The image pool rendering options are set in the template itself, as the content block returns an sfImagePoolCollection (if multiple), or an sfImagePoolImage (if not multiple) - so you can treat it like any other image poolable object.

    <?php echo pool_image_tag($page->renderContent('content_block_1'), '200'); ?>


#### Types - further

Further content block types can be added by extending the ContentBlockType class and form.


#### Custom configuration

Custom configuration can be set on each content block and retrieved in the template file.  This is useful on shared templates.  Example here if for a page, can also be used for listings.
  
    shared_1:
      template: shared

      blocks:
        content_block_1:
          custom_class: shared_1


    shared_2:
      template: shared

      blocks:
        content_block_1:
          custom_class: shared_2


In the template

    <?php
    $identifier = 'content';
    $defn = $contentGroup->getBlockDefinition($identifier);
    $custom_class = $defn['custom_class'];
    ?>

    <div class="<?php echo $custom_class’ ?>">
      <?php echo $page->renderContent($identifier);
    </div>



### Content pages

A content page should be used for any page where a single page of content should be displayed.  Example usage: Home page, Terms and conditions, About us, etc.


#### Configuration

To create templates, add configuration as above to config/app.yml

    site:

      ... # other config

      page_templates:

Then you need to add configuration for each template type - depending on what content you need on each page 

Restrictions on template identifiers:
 *  Unique 
 *  50 character or less 
 *  Only contain letters, numbers or _ - advisably start with a letter to avoid confusion


#### Basic configuration

The basic template configuration contains no content blocks - which is useful when pulling in content from other areas.


    blank:
      name: Blank template

      blocks:
        # none


#### Basic templates

Generally, though, templates contain dynamic content

    homepage:
      name: Homepage

      blocks:
        hero_image:
          name: Hero image
          type: AssetImage
          width: 1500
          height: Any
        introduction:
          name: Introduction
          type: HTML


You will then need to create a file called homepage.php in the templates/page folder for the frontend.

This would contain markup, plus the following to render the content.

    <?php echo $page->renderContent('hero_image'); ?>

    <?php echo $page->renderContent('introduction'); ?>


#### Additional configuration

Additional parameters can be set for the templates

      layout: layout


Template files can be shared between template - this can be used on conjunction with the custom configuration mentioned above.  This overrides the default IDENTIFIER.php template file

      template: shared


Turn caching on and off - this is important if a page has a form on it - forms should not be cached

       cacheable: false


If a different header/footer is required, a specific layout can be set for a template

      stylesheets: [page]
      javascripts: [page]

As with view.yml (same format), stylesheets and javascripts can be set for specific templates


#### Template restrictions

Templates can be restricted to a particular page (or pages), for example: for registration / homepage.  This is done by specifying a route name (the identifier for a sitetree node).

      route_name: homepage
      restricted: true    # if restricted, only this template is returned


Or only available for particular pages:

       route_name: [ offers, personal ]

Or only available for certain sites (if multi-site setup):

		site:	[ gb, fr ]

NOTE: Restrictions can be an array or single string, the manager handles converting it.


### Content listings and items

Should be used on any page which requires a list or repetitive items of content, such as Articles, Offers, FAQs, etc.


#### Configuration

To create templates, add configuration as above to config/app.yml

    site:

      ... # other config

      listing_templates:


Then you need to add configuration for each template type - depending on what content you need on each page.  See the content page information.


#### Basic configuration

It makes no sense to have a listing without content, so the basic listing uses the default listingItem.


          article:
            name: Article (with Category)
            use_categories: true  # this is the default
        
            item_status:
              - featured
              - editors_pick
              - thought_about
         
            listing_blocks:
              # no content
         
            item_blocks:
              image: 
                name: Image *
                type: AssetImage
                width: 290
                height: Any
                use_lang: false
                required: true
              content:
                name: Content
                type: HTML
                use_lang: true


This definition will require `article_listing.php` and `article_item.php` created in the templates/listing folder.

This particular listing contains a category and status for each item.  If listings have categories, these are managed in the same place as the items.  Statuses are set per listing, in the configuration.  These also certain actions to be performed on items, or specific items to be included on other pages (e.g: featured on the homepage).

Statuses and categories are not required, but are a core part of the listing items.


#### Rendering the content

When you render a listing, you have to render the items within the listing.  The included pager manages ordering (either automatic, set in the configuration; or manual, set in the CMS) and pagination.

          <?php echo $listing->renderContent('content'); ?>

          <?php if (0 < $pager->getNbResults()) : ?>
        <ul>
          <?php foreach ($pager->getResults() as $item) : 
            $item->ContentGroup->initialiseForRender($sf_user->getCulture());
            // If there is a category
            if ($item->ListingCategory->id) $itemUrl = internal_url_for_sitetree($sitetree, 'category_item', array('slug'=>$item->slug, 'category'=>$item->ListingCategory->slug));
            else $itemUrl = internal_url_for_sitetree($sitetree, 'item', array('slug'=>$item->slug)); ?>
          
                        <li>
                            <a href="<?php echo url_for($itemUrl); ?>">
                              <?php echo $item->renderContent('image'); ?>
                            </a>
                            <?php if ($item->status) : ?>
                              <h3><?php echo $item->status; ?></h3>
                            <?php endif; ?>
                                  
                            <h2><a href="<?php echo url_for($itemUrl); ?>"><?php echo $item->getTitle(); ?></a></h2>
                            <?php echo $item->renderContent('content'); ?>
                            <a class="read_more" href="<?php echo url_for($itemUrl); ?>">Read More</a>
                            <?php if ($item->item_date) : ?>
                           <?php echo format_date($item->item_date); ?>                       <?php endif; ?>
                        </li>  
                      
           <?php endforeach; ?>
        </ul>
      
        <?php if ($pager->haveToPaginate()) :
          $routing_type = ($category ? 'category_page' : 'page');
          $routing_params = array();
          if ($category) $routing_params['category'] = $category->getSlug();
          $currentPage = $pager->getPage(); ?>
        
          <div class="pagination">
            <ul class="pagination">
              <?php if ($currentPage != 1) : ?>
                <?php if (1 == $pager->getPreviousPage()) : ?>
                  <?php if ($category) : ?>
                    <li class="previous"><a href="<?php echo url_for(internal_url_for_sitetree($sitetree, 'category', $routing_params)); ?>">&lt; Previous</a></li>
                  <?php else : ?>
                    <li class="previous"><a href="<?php echo url_for(internal_url_for_sitetree($sitetree)); ?>">&lt; Previous</a></li>
                  <?php endif; ?>
                <?php else : ?>
                  <li class="previous"><a href="<?php echo url_for(internal_url_for_sitetree($sitetree, $routing_type, (array('page'=>$pager->getPreviousPage()) + $routing_params))); ?>">&lt; Previous</a></li>
                <?php endif; ?>
              <?php endif; ?>
              <?php foreach ($pager->getLinks() as $link) : ?>
                <?php if ($link == $pager->getPage()) : ?>
                  <li><?php echo $link; ?></li>
                <?php elseif (1 == $link) : ?>
                  <?php if ($category) : ?>
                    <li><a href="<?php echo url_for(internal_url_for_sitetree($sitetree, 'category', $routing_params)); ?>">1</a></li>
                  <?php else : ?>
                    <li><a href="<?php echo url_for(internal_url_for_sitetree($sitetree)); ?>">1</a></li>
                  <?php endif; ?>
                <?php else : ?>
                  <li><a href="<?php echo url_for(internal_url_for_sitetree($sitetree, $routing_type, (array('page'=>$link) + $routing_params))); ?>"><?php echo $link; ?></a></li>
                <?php endif; ?>
              <?php endforeach; ?>
              <?php if ($currentPage != $pager->getLastPage()) : ?>
                <li class="next"><a href="<?php echo url_for(internal_url_for_sitetree($sitetree, $routing_type, (array('page'=>$pager->getNextPage()) + $routing_params))); ?>">Next &gt;</a></li>
              <?php endif; ?>
            </ul>
          </div>
        <?php endif; ?>
      <?php endif; ?>


And the listing item, with appropriate markup (it can also render content from the listing).

    <?php echo $item->renderContent('image'); ?>

    <?php echo $item->renderContent('content'); ?>


#### Additional configuration

As per pages, the layout and template can be changed

      listing_layout: blank
      item_layout: layout

      listing_template: shared_listing
      item_template: shared_item


And items or listings can be excluded from the cache

      listing_cacheable: false
      item_cacheable: false


As per pages, specific stylesheets and javascripts can be added to the listing and/or item

      stylesheets: [custom]
      javascripts: [custom]

      item_stylesheets: [custom_item]
      item_javascripts: [custom_item]


Again, as per pages, listings can be restricted to certain route name(s).

      route_name: homepage
      restricted: true    # if restricted, only this template is returned


Or only available for particular pages:

       route_name: [ offers, personal ]

Or only available for certain sites (if multi-site setup):

		site:	[ gb, fr ]

NOTE: Restrictions can be an array or single string, the manager handles converting it.

#### RSS Feeds

Set site-wide site-specific RSS settings in `config/app.yml`

Config that is not set, will not be used (no config is required)

    site:
        definition:
          name:            ##PROJECTNAME##
          cultures:        [en]
          default_culture: en
          rss_config:
            author:  { name: SITE_NAME, email: SITE_EMAIL, link: SITE_URL }
            logo:    { url: /images/logo.png, width: 131, height: 123 }
            favicon: /favicon.ico
 
Enable and set listing RSS settings in listing_templates (only required item is rss_enabled)

Description and Content use renderContent() on $item

    article: 
      name: Articles (with RSS)

      # RSS config
      rss_enabled:       true
      rss_item_ordering: i.created_at # default
      rss_config:
        feed:
          # set here for feed specific, or in app_site_rss_config for sitewide
          author:  { name: SITE_NAME, email: SITE_EMAIL, link: SITE_URL }
          logo:    { url: /images/logo.png, width: 131, height: 123 }
          favicon: /favicon.ico

        item:
          description: summary   # identifier of a content block in blocks:
          content: page_content  # identifier of a content block in blocks:

Add the following to your `apps/frontend/template/layout.php` file, to include an auto-discoverable feed (make sure you have `$sitetree = siteManager::getInstance()->getCurrentSitetreeNode();` in the layout)

This also works for any feeds referenced in the External RSS Url field of the listing ($listing->getRssUrl();) - which can use Feedburner on the `/rss` url for the listing

Alternatively, substitute atom for rss to get an atom link

    <?php if (rss_for_sitetree($sitetree)) : ?> 
      <link rel="alternate" type="application/rss+xml" href="<?php echo rss_for_sitetree($sitetree); ?>" /> 
    <?php endif; ?>


### Extending content blocks

Creating new types of content blocks is a matter of creating a model and a form which extend the base content block types.

You will require a form, which is displayed on the page to enter and edit the content block.

You have to set up the widget and validator (along with implementing the `getValidatorOptions()` method - though if there is no validation for your content block, just leave it empty).  You can extend the rest as required.

    <?php
    class contentBlockTypeNEWTYPEForm extends contentBlockTypeForm {
  
      /**
       * Set up the field
       * 
       * @see contentBlockType/contentBlockTypeForm::configure()
       */
      public function configure() {
        parent::configure(); 
    
        $this->widgetSchema['value'] = new sfWidgetTYPE();
        $this->validatorSchema['value'] = new sfValidatorTYPE($this->getValidatorOptions());
        $this->widgetSchema->setLabel('value','&nbsp;');
      }
  
      /**
       * Get validation from the config
       */
      protected function getValidatorOptions() {
          // Use validator if set
          $definition = $this->getObject()->getContentBlockVersion()->getDefinition();
          $validatorOptions = array();
      
          ... calculate validation
    
          return $validatorOptions;
      }
  
      /**
       * Link to the content block 
       */
      public function getModelName() {
              return 'contentBlockTypeNEWTYPE';
        }
    }


You will also require a content block class, which is linked to a  content block and used when rendering the content block in the CMS and on the frontend.

There are several method that can be overwritten from the basic implementation in the class.  There are three required methods that need to be implemented from the interface for the content block to be usable.

Below is the standard implementation, which can obviously be tweaked, depending on the functionality of the content block.  The crux of the content block is the value - which should never be an id (or something easily changeable).  This value can either be rendered raw, or manipulated (i.e: used to retrieve an object from the database) before rendering - see the existing content blocks for examples.

    <?php

    /**
     * Basic NEWTYPE content block
     *
     * @see ContentBlockType for details
     */
    class ContentBlockTypeNEWTYPE extends ContentBlockType 
    {
  
      /**
       * @see ContentBlockType/ContentBlockTypeInterface::editRender()
       * 
       * @param sfWebRequest $request
       * @return sfForm
       */
      public function editRender(sfWebRequest $request) 
      {
          $field = $this->getFormName();
          $form = new ContentBlockTypeNEWTYPEForm($this);
      
        if ($request->hasParameter($field) 
              && ($request->hasParameter('save') || $request->hasParameter('save_and_publish') || $request->hasParameter('preview'))) 
        {
            $form->bind($request->getParameter($field)); 
          }
      
          return $form;
      }

      /**
       * Validation options:
       * 
       *     set out validation options that can be used in the definition
       * 
       * @see ContentBlockType/ContentBlockTypeInterface::editIsValid()
       * 
       * @param sfWebRequest $request
       * @return boolean
       */
      public function editIsValid(sfWebRequest $request) 
      {
        $field = $this->getFormName();
          $form = new contentBlockTypeTextForm($this);
      
          if ($request->hasParameter($field)) 
        {
            $form->bind($request->getParameter($field));
          }
      
          return $form->isValid();
      }

        /**
         * Render from value
         * This is called from render() and renderFromRequest() with the appropriate value
         * 
         * @see ContentBlockType/ContentBlockType::renderFromValue()
         * 
         * @param string $value
         * @return string
         */
      public function renderFromValue($value) 
      {
           return $value;
      }
    }


### Extending listing items

In order to integrate existing data from a previous site, or to extend the functionality of the listing item it may become necessary to extend the listing item.

The default listing item provides the following functionality:
 -  Translatable title (and automatic URL generation)
 -  Item date
 -  Category
 -  Configurable status
 -  Any other fields in content blocks via template configuration

If further functionality is required, is it required to extend the listingItem in Doctrine, so that the listingItem functionality (such as rendering, and links to listings) is carried over.

You will need to create a new model in the local site `schema.yml` (this inheritance creates new tables in the database)

Please note: Due to a limitation in Doctrine 1.x new translatable fields cannot be added without “hacking” the model (which will interfere with automatic class generation and updating - so not advisable).

    # model definitions ----------------------

    newListingItem:
      inheritance:
        extends: listingItem    
        type: concrete

      columns:
        # additional fields
        ...


To set up a listing with the new class you will need to add new lines into the configuration for the listing template.  Any items left undefined will use the default classes set in the listingManager (see the manager for more information).

        listing_templates:
          new_template:
            name: New item listing

            list_item_class: newListingItem
            list_item_form_class: newListingItemForm
            ...
