init: docker-down-clear docker-pull docker-build docker-up composer-install
up: docker-up
down: docker-down
restart: docker-down docker-up
test: composer-test

docker-up:
	docker compose up -d --build

docker-down:
	docker compose down --remove-orphans

docker-down-clear:
	docker compose down -v --remove-orphans

docker-pull:
	docker compose pull

docker-build:
	docker compose build

composer-install:
	docker compose exec php-fpm composer install -vv

composer-da:
	docker compose exec php-fpm composer dumpautoload

composer-test:
	docker compose exec php-fpm composer test
