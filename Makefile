
help:

	@echo "Possible targets:"
	@echo "  test - build all test suites"
	@echo "  test-constant - build all test suites, as soon as a file changes"
	exit 0

test:

	@cd php && libs/naith/naith run

test-constant:

	@cd php && libs/naith/naith run-constant
	
.PHONY: test help

# vim: ts=4:sw=4:noexpandtab!:
