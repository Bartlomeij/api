Api: http://localhost:8000/api
Documentation: http://localhost:8000/documentation

Admin account:
User: admin@example.com
Pass: verySafeAdminPassword

If you want to run the application on your localhost, after git clone go to the project's directory and
run:
composer install (php7.1 required)

Here, enter some information about your database, and set secret and jwt keys. Make sure the user
has permission to create database. Doctrine will create two databases - one for production and one
with "_test" suffix as a test environment. If user has no permission to create database - you need to
create two databases - first named like you set in composer, the second with "_test" suffix and skip
the database:create steps later. Next, generate SSH keys passing twice jwt_key_pass_phrase you've
set during composer install.

mkdir var/jwt
openssl genrsa -out var/jwt/private.pem -aes256 4096
openssl rsa -pubout -in var/jwt/private.pem -out var/jwt/public.pem

Now run bin/console server:start. If port is other than 8000 you need to change TEST_BASE_URL value in phpunit.xml.dist file.

Then create databases, migrate and populate data:

bin/console doctrine:database:create --env=prod
bin/console doctrine:database:create --env=test
bin/console doctrine:migrations:migrate --env=prod
bin/console doctrine:migrations:migrate --env=test
bin/console doctrine:fixtures:load

Fixtures will populate production database only, test database tables are truncated before every
single test. Now you should be able to run all 55 tests successfully:

vendor/bin/phpunit

Working on your localhost you may see some debug informations that won't be displayed on
production.

After work you can stop the server with server:stop and drop databases if you want to:

bin/console doctrine:database:drop --env=prod --force
bin/console doctrine:database:drop --env=test --force

If you have any questions - check the documentation and please do not hesitate to write or call me.
Cheers!