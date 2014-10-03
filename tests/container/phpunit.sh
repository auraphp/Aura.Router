if [ -d vendor ]
then
    composer update
else
    composer require "aura/di:2.0.*@dev"
fi
phpunit $@
exit $?
