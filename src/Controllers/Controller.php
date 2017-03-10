<?php

    namespace Controllers;

    use \WOK\Services\Services;

    abstract class Controller {


        /**
         * @var Services    $services           Services collection
        **/
        protected $services;

        /**
         * @var Collection  $settings           Settings collection
        **/
        protected $settings;


        /**
         * Instanciate a new controller by defining services
         * @param   Services        $services       Services collection
        **/
        public function __construct(Services $services) {

            $this->services = $services;
            $this->settings = $services->getService('Settings');

        }


        /**
         * Invoke a controller's action
         * @param   string      $action         Action name (equivalent to the method's name)
         * @param   array       $arguments      Action parameters (equivalent to the method's parameters)
        **/
        public function __invoke($action, array $arguments) {

            return call_user_func_array(array($this, $action), $arguments);

        }


        /**
         * Call of an undefined action
         * @throws BadMethodCallException
        **/
        public function __call($action, $arguments) {

            throw new \BadMethodCallException(
                'Undefined controller action `'.get_class($this).'::'.$action.'`'
            );

        }

    }
