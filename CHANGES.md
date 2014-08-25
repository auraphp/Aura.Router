- DOC: Update README and docblocks.

- ADD: Methods Route::setAccept() and Route::addAccept() to match against "Accept" headers.

- ADD: Methods Route::setMethod() and Route::addMethod() to explicitly match against HTTP methods.

- ADD: Testing on Travis for PHP 5.6.

- ADD: Method Router::addHead() to add a HEAD route

- ADD: Methods Router::getFailedRoute(), Route::failedMethod(), and Route::failedAccept(), along with route match scoring, to inspect the closest non-matching route.

- REF: Various refactorings to extract complex code to separate classes

- BRK: The routes no longer add a "controller" value by default; instead, they add only an "action" value that defaults to the route name. This makes the package ADR-centric by default instead of MVC-centric.

- CHG: Use ArrayObject for value matches

- CHG: Method Router::attachResource() now adds an "OPTIONS" route.

- REF: Extract a Generator class from the Route class

- ADD: Method Router::getMatchedRoute() for use after matching.

- ADD: Class-based config for Aura.*_Kernel packages.
