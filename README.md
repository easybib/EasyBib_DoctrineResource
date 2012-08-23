EasyBib DoctrineResource
========================

A wrapper for setting up the Doctrine EntityManager to use it in all modules of EasyBib.
It assumes you're using a ZF MVC application with modules placed inside an "app" folder.

Installation & Configuration
----------------------------
 1. Use composer to add EasyBib_DoctrineResource as dependency
 2. Drop in a doctrine.ini into your config folder - see docs folder for an example
 3. Use it!

Usage
-----
 1. Load your doctrine.ini with Zend_Config_Ini or ez_Core_LoadConfig (Zend_Config_Ini object is needed!)
 2. Set the options of Resource:
    - timestampble: enables Gedmo Timestampable support for Doctrine entities 
        see https://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/timestampable.md
    - sluggable: enables Gedmo Sluggable support for Doctrine entities 
        see https://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/sluggable.md
    - tree: enables Gedmo Tree nested behavior for Doctrine entities
        see: https://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/tree.md
    - profile: enables debugging of all Doctrine SQL queries (they get echoed)
 
 3. Initialize DoctrineResource by:
 
        $doctrineResource =  new \EasyBib\Doctrine\DoctrineResource(				 			
            $doctrineIniConfig, // your doctrine.ini settings
       	    $root,              // path to your app root folder
            'default',          // zf mvc module name you want to use the DoctrineResource from
       	    $options            // the options array for loading needed PlugIns, can be an empty array()
       	 );

 4. Use the Doctrine EntityManager: 
         
        $em = $doctrineResource->getEntityManager();
        
 5. Have fun with Doctrine!
      
