up:
	docker compose up -d

down:
	docker compose down

install:
	docker compose exec php composer install

migrate:
	docker compose exec php composer migrate

migrate-rollback:
	docker compose exec php composer migrate:rollback

migrate-status:
	docker compose exec php composer migrate:status

seed:
	docker compose exec php composer seed

test:
	docker compose exec php composer test

stan:
	docker compose exec php composer stan

cs-fix:
	docker compose exec php composer cs-fix

check:
	docker compose exec php composer check

setup: 
	up install migrate seed