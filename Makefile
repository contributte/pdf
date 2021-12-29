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
	vendor/bin/tester -s -p phpdbg --colors 1 -C -d memory_limit=512M --coverage ./coverage.xml --coverage-src ./src tests

coverage-html:
	vendor/bin/tester -s -p phpdbg --colors 1 -C -d memory_limit=512M --coverage ./coverage.html --coverage-src ./src tests
