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
    require "../etc/config.php";
    require "../vendor/autoload.php";



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
    $response   = new WOK\HttpMessage\Response();


    /***
     *
     * Routes
     * ---
     *
     * Update routes while the server request target is prefixed
     * with a parent local path.
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
    **/
    $services = call_user_func(require "../etc/services.php", $settings);
    $services->addService('settings',       $settings);
    $services->addService('request',        $request);
    $services->addService('request',        $request);
    $services->addService('response',       $response);
    $services->addService('router',         $router);

    if($settings->environment['debug']) {

        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);

        $whoops->pushHandler(function($exception, $inspector, $run) use($services) {

            $monolog = $services->getService('monolog');
            $monolog->error($exception->getMessage());

            return \Whoops\Handler\Handler::DONE;

        });

        $whoops->register();

    }

    /**
     *
     * Generate the response
     * ---
     *
    **/
    try {

        // Retrieve the route action
        // ---
        // As the router throws an exception for an undefined route
        // We define a default error route in this case.
        try {

            $action = $router->match($request->getMethod(), $request->getUri());

        }
        catch(Exception $e) {

            $action = (object) array(
                'name'          => 'Site\Errors->pageNotFound',
                'controller'    => 'Controllers\Site\Errors',
                'action'        => 'pageNotFound',
                'parameters'    => array('exception' => $e)
            );

        }

        // Assign request parameters
        $attributes = $request->getAttributes()->all();
        $request    = $request->withAttributes(array_merge($attributes, $action->parameters));
        $services->addService('request', $request);

        // Execute the controller action
        $reflection = new \ReflectionClass($action->controller);
        $controller = $reflection->newInstance($services);

        // @note This call the __invoke() controller method
        call_user_func($controller, $action->action, $action->parameters);

    }

    // Call the Errors::internalError controller
    catch(Exception $e) {

        $action = (object) array(
            'name'          => 'Site\Errors->internalError',
            'controller'    => 'Controllers\Site\Errors',
            'action'        => 'internalError',
            'parameters'    => array('exception' => $e)
        );

        // Assign request parameters
        $attributes = $request->getAttributes()->all();
        $request    = $request->withAttributes(array_merge($attributes, $action->parameters));
        $services->addService('request', $request);


        // Execute the controller action
        $reflection = new \ReflectionClass($action->controller);
        $controller = $reflection->newInstance($services);

        // @note This call the __invoke() controller method
        call_user_func($controller, $action->action, $action->parameters);

    }
