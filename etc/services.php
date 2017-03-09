<?php

    /**
     *
     * Services
     * ===
     *
     * This file provide a collection of services
     * (see it as a dependency injection).
     *
     * It must returns a callable function that
     * returns the services collection
     *
     * @return Closure
     *
    **/

    use WOK\Services\Services;

    return function($settings) {

        $services = new Services;


        /**
         * Autoload services from files
         * ---
        **/
        $files = new DirectoryIterator(__ROOT_DIR__.'/src/Services');
        foreach($files as $fileinfo) {

            // Prevent unwanted files
            if($fileinfo->isDot() || $fileinfo->isDir() || $fileinfo->getExtension() != 'php') {
                continue;
            }

            // Retrieve service informations
            $service  = $fileinfo->getBasename('.php');
            $callback = require $fileinfo->getPathname();

            if(!is_callable($callback)) {
                throw new \DomainException('Service file instanciator `'.$service.'` must return a callable function ('.$fileinfo->getPathname().').');
            }

            // Register service from filename
            $services->addService($service, $callback);

        }


        return $services;

    };
