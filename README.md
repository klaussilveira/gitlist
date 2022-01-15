# GitList: an elegant git repository viewer
![Build Status](https://github.com/klaussilveira/gitlist/actions/workflows/build.yml/badge.svg)
[![GitHub release](https://img.shields.io/github/release/klaussilveira/gitlist.svg)](https://github.com/klaussilveira/gitlist/releases/latest)
[![License](https://img.shields.io/github/license/klaussilveira/gitlist)](https://github.com/klaussilveira/gitlist/blob/master/LICENSE)
[![Crowdin](https://badges.crowdin.net/gitlist/localized.svg)](https://crowdin.com/project/gitlist)

GitList is an elegant and modern web interface for interacting with multiple git repositories. It allows you to browse repositories using your favorite browser, viewing files under different revisions, commit history, diffs. It also generates RSS/Atom feeds for each repository, allowing you to stay up-to-date with the latest changes anytime, anywhere. GitList was written in PHP, on top of the [Symfony](https://symfony.com) framework and powered by the Twig template engine. This means that GitList is easy to install and easy to customize. Also, the GitList interface was made possible due to [Bootstrap](https://getbootstrap.com).

## Features
* Multiple repository support
* Multiple branch support
* Multiple tag support
* Commit history, blame, diff
* RSS/Atom feeds
* Syntax highlighting via CodeMirror or Ace
* Repository statistics

## Screenshots
![Screenshot 1](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/1.png)
![Screenshot 2](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/2.png)
![Screenshot 3](https://raw.githubusercontent.com/klaussilveira/gitlist/gh-pages/img/roller/3.png)

## Requirements
In order to run GitList on your server, you'll need:

* PHP 8.1
* git 2
* Webserver (Apache, nginx)

## Installation
* Download GitList from [gitlist.org](http://gitlist.org/) and decompress to your `/var/www/gitlist` folder, or anywhere else you want to place GitList.
* Do not download a branch or tag from GitHub, unless you want to use the development version. The version available for download at the website already has all dependencies bundled, so you don't have to use composer or any other tool
* Open up the `config/config.yml` and configure your installation. You'll have to provide where your repositories are located.
  * Alternatively, you can export the environment variable `DEFAULT_REPOSITORY_DIR` with the directory containing your repositories
* Create the cache and log folder and give it read/write permissions to your web server user:

```
cd /var/www/gitlist
mkdir -p var/cache
chmod 777 var/cache
mkdir -p var/log
chmod 777 var/log
```

That's it, installation complete! If you're having problems, check the [Troubleshooting](https://github.com/klaussilveira/gitlist/wiki/Troubleshooting) page.

## Development
GitList comes with a Docker Compose configuration intended for development purposes. It contains a PHP image with all necessary extensions, as well as a Node image for frontend assets.

To get started, just clone the repo and run the setup script:

```bash
git clone https://github.com/klaussilveira/gitlist.git
make setup
```

It should take care of letting you know what is missing, if anything. Once finished, run the test suite to make sure everything is in order:

```bash
make test
make acceptance
```

There are other commands available. To learn more:

```bash
make help
```

## Contributing
If you are a developer, we need your help. GitList is small, but we have lots of stuff to do. Some developers are contributing with new features, others with bug fixes. But you can also dedicate yourself to refactoring the current codebase and improving what we already have. This is very important, we want GitList to be a state-of-the-art application, and we need your help for that.

* Stay tuned to possible bugs, suboptimal code, duplicated code, overcomplicated expressions and unused code
* Improve the test coverage by creating unit and functional tests

If you are not a developer, you can also contribute by helping [translate GitList](https://crowdin.com/project/gitlist).

## Further information
If you want to know more about customizing GitList, check the [Customization](https://github.com/klaussilveira/gitlist/wiki/Customizing) page on the wiki. Also, if you're having problems with GitList, check the [Troubleshooting](https://github.com/klaussilveira/gitlist/wiki/Troubleshooting) page. Don't forget to report issues and suggest new features! :)

## Legacy
GitList was born in [May 2012](https://github.com/klaussilveira/gitlist/commit/df43c987cf02a3521ac65cf5bd4a4f54cf749177), a time were Composer was still a novelty and Silex was all the rage. We have tried to maintain GitList as-is for as long as possible, but the PHP ecosystem changed so much in all those years that it became too time consuming to maintain it. Thus, `2.0` was born on top of Symfony 6 and we'll keep moving with the times.

Legacy, however, is still [available here](https://github.com/klaussilveira/gitlist/tree/legacy) and we will try to keep it secure and working on newer PHP versions.
