# Dyanabol

## Description
Dyanabol is a RESTful JSON object storage API for rapid prototyping 

## Features
* Zend Framework 2 Module
* Stores most JSON objects with no schema changes

## Requirements
* >= PHP 5.5
* PHP Mysql driver
* MySQL
* Zend Framework 2

## Installation
* Install Apache2, make sure modrewrite is enabled
* Install Composer 
* Install the Zend Framework 2 skeletal application
* install the Dyanabol module
* in /config/application.config.php , comment out the Application module, and add the Dyanabol module
* copy the contents of the global, local, and application config files from data/
* import the dyanabol.sql database into your mysql

## Instructions for use 
* Making requests requires two headers: 
  * Content-type application/json
  * Authorization Basic c3VwZXJiYXNpYzp0aGlzSXN0aDM2NA==
* To store an object, POST it to / as json. The only required attribute is "object_class".
* to retrieve an object, GET it from /[object_class]/[id]

## End Points
* / post : create new object
* / get : list objects by unique name
* /[object_class] get : list objects of this [object_class]
* /[object_class]/[id] get : get object with this name and id
* /[object_class]/[id] put : update object with this name and id
* /[object_class]/[id] delete : delete object with this name and id

## http basic auth Creds
superbasic:thisIsth364

## version history
* v0.8.1
  * correct reponse codes
  * more verbose error messages