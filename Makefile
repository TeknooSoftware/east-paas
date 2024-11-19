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
	COMPOSER_MEMORY_LIMIT=-1 ${COMPOSER} update --prefer-lowest --prefer-dist --no-interaction;
else
	COMPOSER_MEMORY_LIMIT=-1 ${COMPOSER} update --prefer-dist --no-interaction;
endif

.PHONY: depend

### QA
qa: lint phpstan phpcs audit
qa-offline: lint phpstan phpcs

lint:
	find ./src -name "*.php" -exec ${PHP} -l {} \; | grep "Parse error" > /dev/null && exit 1 || exit 0
	find ./infrastructures -name "*.php" -exec ${PHP} -l {} \; | grep "Parse error" > /dev/null && exit 1 || exit 0

phpstan:
	${PHP} -d memory_limit=256M vendor/bin/phpstan analyse src infrastructures --level max

phpcs:
	${PHP} vendor/bin/phpcs --standard=PSR12 --extensions=php src/ infrastructures/

audit:
	${COMPOSER} audit

.PHONY: qa qa-offline lint phpstan phpcs audit

### Testing
test:
	rm -rf tests/var/cache/
	XDEBUG_MODE=coverage ${PHP} -dmax_execution_time=0 -dzend_extension=xdebug.so -dxdebug.mode=coverage vendor/bin/phpunit -c phpunit.xml --colors --coverage-text
ifeq ($(DEPENDENCIES), lowest)
	${PHP} -d memory_limit=390M vendor/bin/behat
else
	${PHP} -d memory_limit=320M vendor/bin/behat
endif
	rm -rf tests/var/cache/

.PHONY: test

### Cleaning
clean:
	rm -rf vendor

.PHONY: clean
