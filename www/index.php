<?php

    /**
     *
     * Entrypoint
     * ===
     *
     * This file is the main HTTP entrypoint.
     * It instanciate every resources that may be
     * needed to threat with HTTP requests.
     *
    **/


    /**
     *
     * Dependencies
     * ---
     *
     * Dependencies are defined with Composer.
     *
     * @see ../composer.json
     *
    **/
    require "../vendor/autoload.php";
    require "../etc/config.php";


    /**
     *
     * Settings
     * ---
     *
     *
     *
     * @return array|WOK\Collection\Collection
    **/
    $settings = require "../etc/settings.php";


    /**
     *
     * Http Bridge
     * ---
     *
     * Instanciate both request and response as a bridge.
     * This provide interfaces to get and generate messages
     * over the HTTP protocol.
     *
    **/
    $request    = WOK\HttpMessage\ServerRequest::createFromGlobals();
    $response   = WOK\HttpMessage\Response::createFromRequest($request);


    /***
     *
     * Routes
     * ---
     *
     * Update routes while the server request target is prefixed
     * with a parent local path. Eg:
     * /path-to-my-url  ->  /project/path-to-my-url
     *
    **/
    $router = call_user_func(require "../etc/routes.php", $settings);

    if($request->hasServerParam('DOCUMENT_ROOT')) {

        $abspath = realpath($request->getServerParam('DOCUMENT_ROOT'));
        $prefix  = mb_substr(__DIR__, mb_strlen($abspath));

        $request->setAttribute('RequestTargetPrefix', $prefix);

        foreach($router as $item) {

            $route = $item->route;

                $uri  = $route->getUri();

                if(!(string)$uri->getHost()) {
                    $uri->setHost($request->getUri()->getHost());
                }

                if(!$uri->getScheme()) {
                    $uri->setScheme($request->getUri()->getScheme());
                }

                if(!empty($prefix)) {
                    $uri->setPath($prefix . (string) $uri->getPath());
                }

                $route->setUri($uri);

            $router->addRoute($route, $item->target, $item->name);

        }

    }


    /**
     *
     * Services
     * ---
     *
     * Define main services
     *
    **/
    $services = call_user_func(require "../etc/services.php", $settings);
    $services->addService('Settings',       $settings);
    $services->addService('Request',        $request);
    $services->addService('Response',       $response);
    $services->addService('Router',         $router);


    /**
     *
     * Filp/Whoops
     * ---
     *
     * Display errors on debug environment
     *
    **/
    if($settings->environment & WOK_ENV_DEBUG) {

        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);

        $whoops->pushHandler(function($exception, $inspector, $run) use($services) {

            $monolog = $services->getService('Monolog');
            $monolog->error($exception->getMessage());

            return \Whoops\Handler\Handler::DONE;

        });

        $whoops->register();

    }


    /**
     * Define the route exec
    **/
    $exec = function($route) use($services) {

        // Execute the controller action
        list($controller, $action) = $route->action;
        $reflection = new \ReflectionClass($controller);
        $controller = $reflection->newInstance($services);

        // @note This call the __invoke() controller method
        call_user_func($controller, $action, $route->parameters);

    };


    /**
     *
     * Response
     * ---
     *
     * Execute the request associated action
     *
    **/
    try {

        // Retrieve the route action
        // ---
        // As the router throws an exception for an undefined route
        // We define a default error route in this case.
        try {

            $route = $router->match($request->getMethod(), $request->getUri());

        }
        catch(Exception $e) {

            $route = (object) array(
                'name'          => 'Site\Errors->pageNotFound',
                'action'        => ['Controllers\Site\Errors', 'pageNotFound'],
                'parameters'    => array('exception' => $e)
            );

        }

        $exec($route);

    }

    // Call the Errors::internalError controller
    catch(Exception $e) {

        $route = (object) array(
            'name'          => 'Site\Errors->internalError',
            'action'        => ['Controllers\Site\Errors', 'internalError'],
            'parameters'    => array('exception' => $e)
        );

        $exec($route);

    }
