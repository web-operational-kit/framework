<?php

    /**
     * Monolog
     * ---
     * @param   string     $name     Log file name
    **/
    use Monolog\Logger as Monolog;
    use \Monolog\Handler\StreamHandler;

    return function($name = 'logs') {

        $monolog = new Monolog('logs');

        $monolog->pushHandler(
            new StreamHandler(
                __ROOT_DIR__.'/var/logs/'.$name.'.log',
                Monolog::DEBUG
            )
        );

        return $monolog;

    };
