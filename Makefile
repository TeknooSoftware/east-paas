### Variables

# Applications
COMPOSER ?= /usr/bin/env composer
DEPENDENCIES ?= latest
PHP ?= /usr/bin/env php

### Helpers
all: clean depend

.PHONY: all

### Dependencies
depend:
ifeq ($(DEPENDENCIES), lowest)
	COMPOSER_MEMORY_LIMIT=-1 ${COMPOSER} update --prefer-lowest --prefer-dist --no-interaction --ignore-platform-reqs;
else
	COMPOSER_MEMORY_LIMIT=-1 ${COMPOSER} update --prefer-dist --no-interaction --ignore-platform-reqs;
endif

.PHONY: depend

### QA
qa: lint phpstan phpcs phpcpd

lint:
	find ./src -name "*.php" -exec ${PHP} -l {} \; | grep "Parse error" > /dev/null && exit 1 || exit 0
	find ./infrastructures -name "*.php" -exec ${PHP} -l {} \; | grep "Parse error" > /dev/null && exit 1 || exit 0

phploc:
	${PHP} vendor/bin/phploc src
	${PHP} vendor/bin/phploc infrastructures

phpstan:
	${PHP} -d memory_limit=256M vendor/bin/phpstan analyse src infrastructures --level max

phpcs:
	${PHP} vendor/bin/phpcs --standard=PSR12 --extensions=php src/
	${PHP} vendor/bin/phpcs --standard=PSR12 --extensions=php infrastructures/

phpcpd:
	${PHP} vendor/bin/phpcpd --exclude */di.php src/
	${PHP} vendor/bin/phpcpd infrastructures/

.PHONY: qa lint phploc phpstan phpcs phpcpd

### Testing
test:
	XDEBUG_MODE=coverage ${PHP} -dmax_execution_time=0 -dzend_extension=xdebug.so -dxdebug.coverage_enable=1 vendor/bin/phpunit -c phpunit.xml -v --colors --coverage-text
	${PHP} vendor/bin/behat
	rm -rf tests/var/cache/

.PHONY: test

### Cleaning
clean:
	rm -rf vendor

.PHONY: clean
