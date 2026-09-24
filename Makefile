.PHONY: help build test stan check composer shell

help:
	@echo "Available commands:"
	@echo "  make build             - Build docker containers"
	@echo "  make test              - Run Pest tests"
	@echo "  make analyze           - Run PHPStan analysis"
	@echo "  make check             - Run both tests and static analysis"
	@echo "  make composer ARGS=... - Run composer commands (e.g. make composer ARGS='install')"
	@echo "  make shell             - Open a shell inside the container"

build:
	docker compose build

test:
	docker compose run --rm app composer test

analyze:
	docker compose run --rm app composer stan

check: test analyze

composer:
	docker compose run --rm app composer $(ARGS)

shell:
	docker compose run --rm app sh
