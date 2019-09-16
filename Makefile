COMPOSE_PROJECT_NAME=yii2-queue
COMPOSE_FILE=tests/docker-compose.yml

build:
	@cp -n .env.example .env
	docker-compose pull
	docker-compose build --pull

test: test73 test72 test71 test70 test56
test73:
	docker-compose build --pull php73
	docker-compose run php73 phpdbg -qrr vendor/bin/codecept run --coverage --coverage-html $(FOLDER)
	docker-compose down
test72:
	docker-compose build --pull php72
	docker-compose run php72 phpdbg -qrr vendor/bin/codecept run --coverage --coverage-html $(FOLDER)
	docker-compose down
test71:
	docker-compose build --pull php71
	docker-compose run php71 phpdbg -qrr vendor/bin/codecept run --coverage --coverage-html $(FOLDER)
	docker-compose down
test70:
	docker-compose build --pull php70
	docker-compose run php70 phpdbg -qrr vendor/bin/codecept run --coverage --coverage-html $(FOLDER)
	docker-compose down
test56:
	docker-compose build --pull php56
	docker-compose run php56 vendor/bin/codecept run $(FOLDER)
	docker-compose down

benchmark: benchmark73 benchmark72 benchmark71 benchmark70 benchmark56
benchmark73:
	docker-compose build --pull php73
	docker-compose run php73 tests/yii benchmark/waiting
	docker-compose down
benchmark72:
	docker-compose build --pull php72
	docker-compose run php72 tests/yii benchmark/waiting
	docker-compose down
benchmark71:
	docker-compose build --pull php71
	docker-compose run php71 tests/yii benchmark/waiting
	docker-compose down
benchmark70:
	docker-compose build --pull php70
	docker-compose run php70 tests/yii benchmark/waiting
	docker-compose down
benchmark56:
	docker-compose build --pull php56
	docker-compose run php56 tests/yii benchmark/waiting
	docker-compose down

benchmark-queue: benchmark73-queue benchmark72-queue benchmark71-queue benchmark70-queue benchmark56-queue
benchmark73-queue:
	docker-compose build --pull php73
	docker-compose run php73 tests/yii benchmark/queue isolate
	docker-compose down
benchmark72-queue:
	docker-compose build --pull php72
	docker-compose run php72 tests/yii benchmark/queue isolate
	docker-compose down
benchmark71-queue:
	docker-compose build --pull php71
	docker-compose run php71 tests/yii benchmark/queue isolate
	docker-compose down
benchmark70-queue:
	docker-compose build --pull php70
	docker-compose run php70 tests/yii benchmark/queue isolate
	docker-compose down
benchmark56-queue:
	docker-compose build --pull php56
	docker-compose run php56 tests/yii benchmark/queue isolate
	docker-compose down


check-cs:
	docker-compose build php72
	docker-compose run php72 php-cs-fixer fix --diff --dry-run
	docker-compose down

clean:
	docker-compose down
	sudo rm -rf tests/runtime/*
	sudo rm -f .php_cs.cache
	sudo rm -rf composer.lock
	sudo rm -rf vendor/

clean-all: clean
	sudo rm -rf tests/runtime/.composer*
