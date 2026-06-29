PORT ?= 8000

start:
	docker compose up --abort-on-container-exit

validate:
	composer validate --no-check-publish

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public routes tests

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 public routes tests

test:
	composer exec --verbose phpunit tests

test-coverage:
	composer exec --verbose phpunit tests -- --coverage-clover=build/logs/clover.xml

test-coverage-text:
	composer exec --verbose phpunit tests -- --coverage-text

check: validate lint test