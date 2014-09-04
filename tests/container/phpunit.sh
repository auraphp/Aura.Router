if [ -d vendor ]
then
    composer update
else
    composer require aura/di:dev-develop-2
fi
phpunit $@
exit $?
