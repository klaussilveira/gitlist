.PHONY: help build
.DEFAULT_GOAL := help
NAME := gitlist
VERSION := $(shell git show -s --format=%h)

help: # Display the application manual
	@echo -e "$(NAME) version \033[33m$(VERSION)\n\e[0m"
	@echo -e "\033[1;37mUSAGE\e[0m"
	@echo -e "  \e[4mmake\e[0m <command> [<arg1>] ... [<argN>]\n"
	@echo -e "\033[1;37mAVAILABLE COMMANDS\e[0m"
	@grep -E '^[a-zA-Z_-]+:.*?# .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?# "}; {printf "  \033[32m%-20s\033[0m %s\n", $$1, $$2}'

check-deps:
	@if ! [ -x "$$(command -v composer)" ]; then\
	  echo -e '\n\033[0;31mcomposer is not installed.';\
	  exit 1;\
	else\
	  echo -e "\033[0;32mcomposer installed\033[0m";\
	fi

setup: check-deps # Setup dependencies and development configuration
	composer install

test: # Run automated test suite
	$(EXEC_PHP) composer test

build: # Build application package
	@rm -rf vendor/
	@composer install --ignore-platform-reqs --no-dev --no-scripts -o
	@zip ./build.zip \
	-r * .[^.]* \
	-x '.github/*' \
	-x 'cache/*' \
	-x 'logo/*' \
	-x 'tests/*' \
	-x 'pkg_builder/*' \
	-x 'build.xml' \
	-x '.git/*' \
	-x '.gitignore'  \
	-x '.php-cs-fixer.cache' \
	-x '.php-cs-fixer.php' \
	-x '.phpunit.result.cache' \
	-x 'composer.json' \
	-x 'composer.lock' \
	-x 'Makefile' \
	-x 'phpunit.xml.dist' \
