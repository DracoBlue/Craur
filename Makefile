
help:

	@echo "Possible targets:"
	@echo "  test - build all test suites"
	@echo "  test-php - build php test suites only"
	exit 0

test:

	@make test-php

test-php:

	@cd php/tests && ls *.php | while read file; do echo "Executing: $$file"; php "$$file" && echo "   -> ok!";done

.PHONY: test help

# vim: ts=4:sw=4:noexpandtab!:
