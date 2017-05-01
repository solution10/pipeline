ci:
	php ./vendor/bin/phpunit --coverage-html report
	php ./vendor/bin/phpcs --standard=PSR2 ./src ./tests -p
