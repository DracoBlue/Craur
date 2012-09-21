
help:

	@echo "Possible targets:"
	@echo "  test - build all test suites"
	@echo "  test-constant - build all test suites, as soon as a file changes"
	exit 0

test:

	@cd php && ../vendor/naith/naith/naith run

test-constant:

	@cd php && ../vendor/naith/naith/naith run-constant

install-composer:

	@if [ ! -d ./bin ]; then mkdir bin; fi
	@if [ ! -f ./bin/composer.phar ]; then curl -s http://getcomposer.org/installer | php -n -d date.timezone="Europe/Berlin" -- --install-dir=./bin/; fi

install-dependencies:

	@make install-composer
	@php -d date.timezone="Europe/Berlin" ./bin/composer.phar -- update
	
.PHONY: test help

# vim: ts=4:sw=4:noexpandtab!:
