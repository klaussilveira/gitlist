#!/usr/bin/env bash

sudo rm -rf ./cache
mkdir -p ./cache
touch ./cache/.gitkeep
chmod 0777 ./cache

sudo rm -rf ./git-test/
mkdir -p ./git-test/
cd ./git-test
git clone --bare https://github.com/patrikx3/gitlist
git clone --bare https://github.com/patrikx3/gitter
git clone --bare https://github.com/patrikx3/corifeus
git clone --bare https://github.com/patrikx3/corifeus-builder
git clone --bare https://github.com/patrikx3/gitlist-workspace
git clone --bare https://github.com/patrikx3/onenote

sudo chmod 0777 ./git-test

cd ..
cp ./artifacts/config.ini ./

bower install
composer install

chown patrikx3:patrikx3 ./cache
chown patrikx3:patrikx3 ./git-test
chown patrikx3:patrikx3 ./config.ini


