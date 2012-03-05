
help:

	@echo "Possible targets:"
	@echo "  test - build all test suites"
	@echo "  test-php - build php test suites only"
	exit 0

test:

	@make test-php

test-php:

	@./run_tests.sh

.PHONY: test help

# vim: ts=4:sw=4:noexpandtab!:
