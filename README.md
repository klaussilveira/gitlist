# GitList: an elegant and modern git repository viewer
[![Build Status](https://secure.travis-ci.org/klaussilveira/GitList.png)](http://travis-ci.org/klaussilveira/GitList)

GitList is an elegant and modern web interface for interacting with multiple git repositories. It allows you to browse repositories using your favorite browser, viewing files under different revisions, commit history, diffs. It also generates RSS feeds for each repository, allowing you to stay up-to-date with the latest changes anytime, anywhere. GitList was written in PHP, on top of the [Silex](http://silex.sensiolabs.org/) microframework and powered by the Twig template engine. This means that GitList is easy to install and easy to customize. Also, the GitList gorgeous interface was made possible due to [Bootstrap](http://twitter.github.com/bootstrap/). 

## Features
* Multiple repository support
* Multiple branch support
* Multiple tag support
* Commit history, blame, diff
* RSS feeds
* Syntax highlighting
* Repository statistics

## Authors and contributors
* [Klaus Silveira](http://www.klaussilveira.com) (Creator, developer)

## License
[New BSD license](http://www.opensource.org/licenses/bsd-license.php)

## Todo
* improve the current test code coverage
* test the interface
* error handling can be greatly improved during parsing
* submodule support
* multilanguage support

## Requirements
In order to run GitList on your server, you'll need:

* git
* Apache and mod_rewrite enabled
* PHP 5.3.3

## Installing
Download the GitList latest package and decompress to your `/var/www/gitlist` folder, or anywhere else you want to place GitList. You can also clone the repository:

```
git clone https://github.com/klaussilveira/gitlist.git /var/www/gitlist
```

Now open up the `config.ini` and configure your installation. You'll have to provide where your repositories are located and the base GitList URL (in our case, http://localhost/gitlist). Now, let's create the cache folder and give the correct permissions:

```
cd /var/www/gitlist
mkdir cache
chmod 777 cache
```

That's it, installation complete!

## Further information
If you want to know more about customizing GitList, check the [Customization](https://github.com/klaussilveira/gitlist/wiki/Customizing) page on the wiki. Also, if you're having problems with GitList, check the [Troubleshooting](https://github.com/klaussilveira/gitlist/wiki/Customizing) page. Don't forget to report issues and suggest new features! :)
