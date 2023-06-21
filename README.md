# Pimcore-Salesforce-Connector

This bundle allows integration of the Pimcore PIM system with your existing Salesforce system with just a few steps. It offers further customisation, allowing you to configure and utilize the bundle as per your requirements.

# Key features:

* Easy mapping of Pimcore PIM object fields with existing Salesforce system. 
* Synchronize existing Pimcore objects with Salesforce in real-time.
* Add multiple mapping fields with just a click in Pimcore.
* Update and modify Salesforce objects within the Pimcore admin panel.
* Define object class from the Pimcore PIM system for streamlined management of Salesforce objects.

# Prerequisites

* Working Salesforce org.
* Pimcore version 10 or above.
* PHP version 8.1 or above.

# Getting Started/Installation:

1. Install Pimcore-Salesforce Connector command line  `composer require syncrasy/pimcore-salesforce-bundle`
2. Enable the bundle by running `bin/console pimcore:bundle:enable SyncrasyPimcoreSalesforceBundle`
3. Install Bundle by running `bin/console pimcore:bundle:install SyncrasyPimcoreSalesforceBundle`
4. Clear cache by running `bin/console cache:clear --no-warmup`
5. Reload Pimcore instance
6. Enter required values in Pimcore Website Settings
    * salesforce-org: enter environment type (production or development)
    * salesforce-password:
    * salesforce-username:
7. Create a new object of class SalesForceSetup

# Configure and Setup

1. Use website Settings to supply the Salesforce information like credentials and environment type
2. Access Pimcore Salesforce Connector from the wrench icon
3. Create mapping for desired class in Pimcore which you would like to sync in Salesforce
4. Basic Configuration:
    * Pimcore Classes - choose from the list of existing Pimcore classes
    * Salesforce Objects - choose from the list of existing Salesforce objects
    * Field for SF ID - a unique field from Pimcore class to store the Saleforce ID
    * Pimcore Unique Field - a unique field for mapping. Example email
    * Salesforce Unique Field - a unique field for mapping. Example email
5. Column Configuration - configure the Pimcore class fields which will be mapped to Saleforce object fields.

# Supported Pimcore field types:

1. Calculated Value Type
2. Date Types
3. Dynamic Select Types
4. Geographic Types
5. Localized Fields
6. Number Types
