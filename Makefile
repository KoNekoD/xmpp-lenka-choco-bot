COMPOSE_FILE = ./config/docker/docker-compose.yml
COMPOSE_FILE_2 = ./config/docker/docker-compose.override.yml
DOCKER_COMPOSE = docker compose -f ${COMPOSE_FILE} -f ${COMPOSE_FILE_2}
DOCKER_COMPOSE_PHP_FPM_EXEC = ${DOCKER_COMPOSE} exec lenka-php-fpm

build:
	${DOCKER_COMPOSE} build

up:
	${DOCKER_COMPOSE} up -d --remove-orphans

down:
	${DOCKER_COMPOSE} down -v

down_force:
	${DOCKER_COMPOSE} down -v --rmi=all --remove-orphans

console:
	if ! ${DOCKER_COMPOSE} ps | grep -q lenka-php-fpm; then make up; fi
	${DOCKER_COMPOSE_PHP_FPM_EXEC} bash

create_network:
	docker network create --subnet 172.18.5.0/24 lenka_network >/dev/null 2>&1 || true

app_test_fixtures:
	${DOCKER_COMPOSE_PHP_FPM_EXEC} php bin/console -e=test do:fi:lo -n

phpunit:
	${DOCKER_COMPOSE_PHP_FPM_EXEC} composer run phpunit

composer_dev:
	${DOCKER_COMPOSE_PHP_FPM_EXEC} composer install

db_migrate:
	${DOCKER_COMPOSE_PHP_FPM_EXEC} bin/console do:mi:mi -n

db_diff:
	${DOCKER_COMPOSE_PHP_FPM_EXEC} bin/console do:mi:di -n

code_phpstan:
	${DOCKER_COMPOSE_PHP_FPM_EXEC} composer run phpstan

code_deptrac:
	${DOCKER_COMPOSE_PHP_FPM_EXEC} composer run deptrac

code_cs_fix:
	${DOCKER_COMPOSE_PHP_FPM_EXEC} composer run cs-fixer

code_rector:
	${DOCKER_COMPOSE_PHP_FPM_EXEC} composer run rector

code_cs_fix_diff:
	${DOCKER_COMPOSE_PHP_FPM_EXEC} composer run cs-fixer-diff

code_cs_fix_diff_status:
	if make code_cs_fix_diff; then \
	    printf '\n\n\n [OK] \n\n\n'; \
	    exit 0; \
	else \
	    printf '\n\n\n [FAIL] \n\n\n'; \
	    exit 1; \
	fi

code_cs_fix_diff_status_no_docker:
	if make code_cs_fix_diff_no_docker; then \
	    printf '\n\n\n [OK] \n\n\n'; \
	    exit 0; \
	else \
	    printf '\n\n\n [FAIL] \n\n\n'; \
	    exit 1; \
	fi
