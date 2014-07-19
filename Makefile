
help:

	@echo "Possible targets:"
	@echo "  test - build all test suites"
	@echo "  test-constant - build all test suites, as soon as a file changes"
	@echo "  install-dependencies - install composer if necessary and install or update all vendor libraries"
	@exit 0

test:

	@vendor/dracoblue/naith/naith run

test-constant:

	@vendor/dracoblue/naith/naith run-constant

install-dependencies:

	@composer update
	
.PHONY: test help

# vim: ts=4:sw=4:noexpandtab!:
