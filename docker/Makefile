.PHONY: bash check-code sign-app show-log test build

# Try to use "docker compose" and fall back to "docker-compose" if not available
DOCKER_COMPOSE := $(shell docker compose > /dev/null && echo docker compose || echo docker-compose)

build:
	$(DOCKER_COMPOSE) build

ls-db:
	$(DOCKER_COMPOSE) run --rm app su -c "ls -hal data/mydb.db*" www-data

fetch-db:
	$(DOCKER_COMPOSE) run --rm app su -c "cp data/mydb.db apps/nextpod" www-data

push-db:
	$(DOCKER_COMPOSE) run --rm app su -c "cp apps/nextpod/mydb.db* data" www-data

bash:
	$(DOCKER_COMPOSE) run --rm app su -c "bash" www-data

check-code:
	$(DOCKER_COMPOSE) run --rm app su -c "./occ app:check-code nextpod" www-data

sign-app:
	$(DOCKER_COMPOSE) run --rm app ../sign-app.sh www-data

show-log:
	$(DOCKER_COMPOSE) run --rm app tail -f /var/www/html/data/nextcloud.log

#test:
#	$(DOCKER_COMPOSE) run --rm app su -c "cd custom_apps/nextpod && make test" www-data
