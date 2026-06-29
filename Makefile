PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

docker-start:
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