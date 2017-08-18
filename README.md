[![Build Status](https://secure.travis-ci.org/patrikx3/gitter.png)](http://travis-ci.org/patrikx3/gitter)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/patrikx3/gitlist/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/patrikx3/resume-web/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/patrikx3/gitlist/badges/coverage.png?b=master)]

# GitList

This is Klaus Silveira's fork, since he doesn't work on this project anymore.  
 
# Release version/update info

## Package
Done, just put on your server, nothing to build:   
https://github.com/patrikx3/gitlist/releases


## v0.5.5
* Fixed PHPUNIT 6

## v0.5.4
* Different submodule links for Gitlist and Github

## v0.5.3
* The markdown links are working right
* Shows submodules

## v0.5.2
* Added all Bootsswatch themes (https://bootswatch.com/)
* Removed default theme, kept only Bootstrap (though like over 10 themes now)
* Removed PHP 5 support, only >= 7
* Upgraded Silex v1 to v2
* Upgraded Twig v1 to v2
* Upgraded Symfony/twig-bridge v2 to v3
* Upgraded  Symfony/filesystem v2 to v3
* Upgraded Phpunit v4 to v6
* Moved from Showdown to Marked (more features)
* For building requires (not required for the server):
  * NodeJs >= 7.8
  * Bower
  * Grunt

   
# Old info
https://github.com/klaussilveira/gitlist

# Live demo

http://gitlist.patrikx3.com/

# Installation

## Requirements
For the build on your workstation (themes):
* ```NodeJs``` >= 7.8
* ```Bower``` (npm install -g bower)

In order to run GitList on your server, you'll need:

* ```git```
* ```Apache``` with ```mod_rewrite``` enabled or ```nginx```
* ```PHP``` >= 7.0 

## By hand
If you just want to get the project dependencies, instead of building everything:

```
git clone https://github.com/patrikx3/gitlist.git
curl -s http://getcomposer.org/installer | php
php composer.phar install
bower install
```

If you have Composer in your path, things get easier. But you know the drill.

[Install](INSTALL.md) - here.


# Further information
If you want to know more about customizing GitList, check the [Customization](https://github.com/patrikx3/gitlist/wiki/Customizing) page on the wiki. Also, if you're having problems with GitList, check the [Troubleshooting](https://github.com/patrikx3/gitlist/wiki/Troubleshooting) page. 