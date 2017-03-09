<?php

    /**
     *
     * Routes
     * ===
     *
     * This file provide a routes collection.
     *
     * It must returns a callable function that
     * returns the Router collection
     *
     * @return Callable function that return WOK\Router\Collection
     *
    **/

    use WOK\Router\Collection;
    use WOK\Router\Route;

    return function($settings) {

        $router = new Collection;


        /**
         * Homepage
         * ---
         * @slug /
        **/
        $router->addRoute(
            new Route(
                'HTTP', '/'
            ),
            ['Controllers\Site\Pages', 'homepage'],
            'Site\Pages->homepage'
        );
        

        return $router;

    };
