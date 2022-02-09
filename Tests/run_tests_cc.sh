rm -rf tests/_output
php vendor/bin/codecept run acceptance --steps --coverage-html
