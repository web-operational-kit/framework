<?php

    namespace Controllers;

    use \WOK\Services\Services;
    use \WOK\HttpMessage\Response;

    abstract class HttpController extends Controller {

        /**
         *  @var    Request       $request          Request instance
        **/
        protected $request;

        /**
         *  @var    Response      $response         Response instance
        **/
        protected $response;

        /**
         * Instanciate a new controller by defining services
         * @param   Services        $services       Services collection
        **/
        public function __construct(Services $services) {

            $this->request      = $services->getService('Request');
            $this->response     = $services->getService('Response');

            parent::__construct($services);

        }


        /**
         * Invoke a HTTP controller's action then send response
         * @param   string      $action         Action name (equivalent to the method's name)
         * @param   array       $arguments      Action parameters (equivalent to the method's parameters)
        **/
        public function __invoke($action, array $arguments) {

            $request  = $this->request;
            $response = parent::__invoke($action, $arguments);

            if(!($response instanceof Response)) {
                var_dump(get_class($this), $action, $arguments);
                var_dump(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)); exit;

                throw new \UnexpectedValueException('Controllers action ('.get_class($this).'::'.$action.') must return a `'.Response::class.'` object ('.gettype($response).' returned instead)');
            }

            /* Let's generate that response */
            $body         = $response->getBody();

            // Add Content-Length header if not defined
            if($body->getSize() > 0 && !$response->hasHeader('Content-Length')) {
                $response->setHeader('Content-Length', $body->getSize());
            }

            // Send headers
            $HttpHeaderString = 'HTTP/'.$response->getProtocolVersion().' '.$response->getStatus();
            header($HttpHeaderString, true, $response->getStatusCode());

            foreach($response->getHeaders() as $name => $value) {
                header($name.': '.$value, true);
            }

            // foreach($response->getCookies() as $cookie) {
            //     header('Set-Cookie: '. (string) $cookie);
            // }

            // Send body
            if($body->getSize() > 0 ) {

                $startOffset    = 0;
                $maxLength      = -1;

                echo $body->getContent();

            }


        }


    }
