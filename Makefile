.PHONY: install qa cs csf phpstan tests coverage-clover coverage-html

install:
	composer update

qa: phpstan cs

cs:
	vendor/bin/codesniffer src tests

csf:
	vendor/bin/codefixer src tests

phpstan:
	vendor/bin/phpstan analyse -c phpstan.neon src

tests:
	vendor/bin/tester tests -s -C

coverage-clover:
	mkdir -p ./tests/tmp
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage ./tests/tmp/coverage.xml --coverage-src ./src tests

coverage-html:
	mkdir -p ./tests/tmp
	vendor/bin/tester -s -p phpdbg --colors 1 -C --coverage ./tests/tmp/coverage.html --coverage-src ./src tests
