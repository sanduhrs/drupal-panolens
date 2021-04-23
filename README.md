CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------

A Javascript 360 Panorama Viewer.

Panolens.js is an event-driven and WebGL based panorama viewer. Lightweight and flexible. It's built on top of Three.JS.

This module integrates Panolens.js library and Three.js library and provides field formatters to display image panorama and video panorama.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/panolens

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/panolens

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.

 * Run `drush panolens:download-panolens`
 
 or

 * Download Three.js from https://github.com/mrdoob/three.js/archive/r105.zip
 * Unzip to libraries libraries/panolens.js/
 * Download Panolens.js from https://github.com/pchen66/panolens.js/archive/v0.11.0.zip
 * Unzip to libraries/three.js/

 CONFIGURATION
-------------
 
 * For the image panorama create an image field
 * Choose the and configure appropriate formatter

 * For the video panorama create an file field
 * Choose the and configure appropriate formatter

MAINTAINERS
-----------

Current maintainers:
 * Stefan Auditor (sanduhrs) - drupal.org/u/sanduhrs
