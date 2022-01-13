<p align="left"><img src="logo/horizontal.png" alt=gitlist" height="120px"></p>

# GitList: an elegant git repository viewer
![Build Status](https://github.com/klaussilveira/gitlist/actions/workflows/php.yml/badge.svg)

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
![Screenshot 1](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/1.png)
![Screenshot 2](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/2.png)
![Screenshot 3](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/3.png)
![Screenshot 4](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/4.png)
![Screenshot 5](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/5.png)
![Screenshot 6](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/6.png)
![Screenshot 7](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/7.png)
![Screenshot 8](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/8.png)
![Screenshot 9](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/9.png)
![Screenshot 10](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/10.png)

## Requirements
In order to run GitList on your server, you'll need:

* PHP 5.3+
* git
* Webserver (Apache, nginx, lighttpd)

## Installation
* Download GitList from [gitlist.org](http://gitlist.org/) and decompress to your `/var/www/gitlist` folder, or anywhere else you want to place GitList.
* Do not download a branch or tag from GitHub, unless you want to use the development version. The version available for download at the website already has all dependencies bundled, so you don't have to use composer or any other tool
* Rename the `config.ini-example` file to `config.ini`.
* Open up the `config.ini` and configure your installation. You'll have to provide where your repositories are located.
* In case GitList isn't accessed through the root of the website, open `.htaccess` and edit RewriteBase (for example, `/gitlist/` if GitList is accessed through http://localhost/gitlist/).
* Set file permissions for `.htaccess`

  ```
  chmod 644 .htaccess
  ```

* Create the cache folder and give read/write permissions to your web server user:

  ```
  cd /var/www/gitlist
  mkdir cache
  chmod 777 cache
  ```

That's it, installation complete! If you're having problems, check the [Troubleshooting](https://github.com/klaussilveira/gitlist/wiki/Troubleshooting) page.


## Authors and contributors
* [Klaus Silveira](http://www.klaussilveira.com) (Creator, developer)

## License
[New BSD license](http://www.opensource.org/licenses/bsd-license.php)

## Development
GitList uses [Composer](http://getcomposer.org/) to manage dependencies and [Ant](http://ant.apache.org/) to build the project.

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

## Contributing
If you are a developer, we need your help. GitList is a young project and we have lots of stuff to do. Some developers are contributing with new features, others with bug fixes. But you can also dedicate yourself to refactoring the current codebase and improving what we already have. This is very important, we want GitList to be a state-of-the-art application, and we need your help for that.

* Stay tuned to possible bugs, suboptimal code, duplicated code, overcomplicated expressions and unused code
* Improve the test coverage by creating unit and functional tests

## Further information
If you want to know more about customizing GitList, check the [Customization](https://github.com/klaussilveira/gitlist/wiki/Customizing) page on the wiki. Also, if you're having problems with GitList, check the [Troubleshooting](https://github.com/klaussilveira/gitlist/wiki/Troubleshooting) page. Don't forget to report issues and suggest new features! :)
