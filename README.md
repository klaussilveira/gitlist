[![Build Status](https://travis-ci.org/patrikx3/gitlist.svg?branch=master)](https://travis-ci.org/patrikx3/gitlist)


# GitList

Klaus Silveira does not respond, so I created this repo so that anyone can extend it.  
 
This is an open source project. Time is a precious thing, so I have no time to give support and fix issues for someone else. I fix a bug, when I have an error that I need. If you got an issue, error or bug, I hope someone will have time to do it for you, otherwise, you are on you own.  

Though, if I know the solution, I will tell you.  
  
If you want to extend, fix bugs and add in new features on your own time, I help you , as ```patrikx3``` is an orgnization. I can add in anyone in no time. Not forking, you become a member, do not even need a pull merge request. 

# Release version/update info
[README](release.md) 
   
# Old info
https://github.com/klaussilveira/gitlist

# Live demo

http://gitlist.patrikx3.tk/

# Installation

## Requirements
In order to run GitList on your server, you'll need:

* ```git```
* ```Apache``` with ```mod_rewrite``` enabled or ```nginx```
* ```PHP``` >= 7.0 
* ```NodeJs``` >= 7.8
* ```Bower``` (npm install -g bower)

## Package
https://github.com/patrikx3/gitlist/releases


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