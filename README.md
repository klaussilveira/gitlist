# GitList: an elegant and modern git repository viewer
[![Build Status](https://secure.travis-ci.org/klaussilveira/gitlist.png)](http://travis-ci.org/klaussilveira/gitlist)

GitList is an elegant and modern web interface for interacting with multiple git repositories. It allows you to browse repositories using your favorite browser, viewing files under different revisions, commit history, diffs. It also generates RSS feeds for each repository, allowing you to stay up-to-date with the latest changes anytime, anywhere. GitList was written in PHP, on top of the [Silex](http://silex.sensiolabs.org/) microframework and powered by the Twig template engine. This means that GitList is easy to install and easy to customize. Also, the GitList gorgeous interface was made possible due to [Bootstrap](http://twitter.github.com/bootstrap/). 

## Features
* Multiple repository support
* Multiple branch support
* Multiple tag support
* Commit history, blame, diff
* RSS feeds
* Syntax highlighting
* Repository statistics

## Screenshots
[![GitList Screenshot](http://dl.dropbox.com/u/62064441/th1.jpg)](http://cloud.github.com/downloads/klaussilveira/gitlist/1.jpg)
[![GitList Screenshot](http://dl.dropbox.com/u/62064441/th2.jpg)](http://cloud.github.com/downloads/klaussilveira/gitlist/2.jpg)
[![GitList Screenshot](http://dl.dropbox.com/u/62064441/th3.jpg)](http://cloud.github.com/downloads/klaussilveira/gitlist/3.jpg)
[![GitList Screenshot](http://dl.dropbox.com/u/62064441/th4.jpg)](http://cloud.github.com/downloads/klaussilveira/gitlist/4.jpg)
[![GitList Screenshot](http://dl.dropbox.com/u/62064441/th5.jpg)](http://cloud.github.com/downloads/klaussilveira/gitlist/5.jpg)

You can also see a live demo [here](http://git.gofedora.com).

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
* Apache with mod_rewrite enabled or nginx
* PHP 5.3.3

## Installing
* Download GitList from [gitlist.org](http://gitlist.org/) and decompress to your `/var/www/gitlist` folder, or anywhere else you want to place GitList. 
* Rename the `config.ini-example` file to `config.ini`.
* Open up the `config.ini` and configure your installation. You'll have to provide where your repositories are located and the base GitList URL (in our case, http://localhost/gitlist).
* Create the cache folder and give the correct permissions:

```
cd /var/www/gitlist
mkdir cache
chmod 777 cache
```

That's it, installation complete! If you're having problems, check this [tutorial](http://gofedora.com/insanely-awesome-web-interface-git-repos/) by Kulbir Saini or the [Troubleshooting](https://github.com/klaussilveira/gitlist/wiki/Troubleshooting) page.

## Building
GitList uses [Composer](http://getcomposer.org/) to manage dependencies and [Ant](http://ant.apache.org/) to build the project. In order to run all the targets in the build script, you will need [PHPUnit](http://www.phpunit.de/), [phpcpd](https://github.com/sebastianbergmann/phpcpd), [phploc](https://github.com/sebastianbergmann/phploc), [PHPMD](http://phpmd.org/) and [PHP_Depend](http://pdepend.org).

Once you have all the dependencies set, you can clone the repository and run Ant:

```
git clone https://github.com/klaussilveira/gitlist.git
ant
```

If you just want to get the project dependencies, instead of building everything:

```
git clone https://github.com/klaussilveira/gitlist.git
curl -s http://getcomposer.org/installer | php
php composer.phar install
```

If you have Composer in your path, things get easier. But you know the drill.

## Further information
If you want to know more about customizing GitList, check the [Customization](https://github.com/klaussilveira/gitlist/wiki/Customizing) page on the wiki. Also, if you're having problems with GitList, check the [Troubleshooting](https://github.com/klaussilveira/gitlist/wiki/Troubleshooting) page. Don't forget to report issues and suggest new features! :)
