.PHONY: help
.DEFAULT_GOAL := help
NAME := gitlist
VERSION := $(shell git show -s --format=%h)
DOCKER_COMPOSE ?= docker compose
EXEC_DOCKER ?= $(DOCKER_COMPOSE) exec -T
EXEC_PHP ?= $(EXEC_DOCKER) php-fpm
EXEC_NODE ?= $(EXEC_DOCKER) node
EXEC_WEB ?= $(EXEC_DOCKER) web

help: # Display the application manual
	@echo "$(NAME) version \033[33m$(VERSION)\n\e[0m"
	@echo "\033[1;37mUSAGE\e[0m"
	@echo "  \e[4mmake\e[0m <command> [<arg1>] ... [<argN>]\n"
	@echo "\033[1;37mAVAILABLE COMMANDS\e[0m"
	@grep -E '^[a-zA-Z_-]+:.*?# .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?# "}; {printf "  \033[32m%-20s\033[0m %s\n", $$1, $$2}'

check-deps: check-local-overrides
	@if ! [ -x "$$(command -v docker)" ]; then\
	  echo '\n\033[0;31mdocker is not installed.';\
	  exit 1;\
	else\
	  echo "\033[0;32mdocker installed\033[0m";\
	fi

setup: check-deps # Setup dependencies and development configuration
	$(DOCKER_COMPOSE) pull || true
	$(DOCKER_COMPOSE) up -d --build
	$(EXEC_PHP) composer install

up: # Create and start containers
	$(DOCKER_COMPOSE) up -d

clean: # Cleanup containers and build artifacts
	$(DOCKER_COMPOSE) down
	$(MAKE) setup

bash: # Start a bash session in the PHP container
	$(EXEC_PHP) /bin/bash

test: # Run automated test suite
	$(EXEC_PHP) composer test
	$(EXEC_NODE) npm run test

acceptance: # Run acceptance test suite
	$(EXEC_NODE) npm run cypress

show-app: # Open application in your browser
	xdg-open http://$$(docker compose port webserver 80)/

update: # Update dependencies
	$(EXEC_PHP) composer update

format: # Run code style autoformatter
	$(EXEC_PHP) composer format

build: # Build application package
	@rm -rf vendor/
	@rm -rf public/assets/*
	@composer install --ignore-platform-reqs --no-dev --no-scripts -o
	@npm run build
	@zip ./build.zip \
	-r * .[^.]* \
	-x '.github/*' \
	-x 'bin/*' \
	-x 'docker/*' \
	-x 'node_modules/*' \
	-x 'tests/' \
	-x 'var/cache/*' \
	-x 'var/log/*' \
	-x '.dockerignore' \
	-x '.editorconfig' \
	-x '.env' \
	-x '.env.dist' \
	-x '.git/*' \
	-x '.gitignore'  \
	-x '.php-cs-fixer.cache' \
	-x '.php-cs-fixer.php' \
	-x '.phpunit.result.cache' \
	-x '.prettierrc' \
	-x 'composer.json' \
	-x 'composer.lock' \
	-x 'docker-compose.override.yml' \
	-x 'docker-compose.override.yml.dist' \
	-x 'docker-compose.yml' \
	-x 'Makefile' \
	-x 'package-lock.json' \
	-x 'package.json' \
	-x 'phpstan.neon' \
	-x 'phpunit.xml.dist' \
	-x 'postcss.config.js' \
	-x 'webpack.config.js' \

fix-perms:
	sudo setfacl -R -m u:root:rwX -m u:`whoami`:rwX var/cache var/log vendor/
	sudo setfacl -dR -m u:root:rwx -m u:`whoami`:rwx var/cache var/log vendor/

check-local-overrides:
	@$(MAKE) --quiet .env
	@$(MAKE) --quiet docker-compose.override.yml

docker-compose.override.yml:
	@ln -s --backup=none docker-compose.override.yml.dist $@

.env:
	@ln -s --backup=none .env.dist $@
