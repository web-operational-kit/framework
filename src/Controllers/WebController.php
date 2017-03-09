<?php

    namespace Controllers;

    /**
     * This astracted class provide
     * common used methods for WWW/HTTP controllers
    **/
    abstract class WebController extends HttpController {


        /**
         * Generate an HTML response;
         * @param string    $template       Template name
         * @param integer   $code           HTTP response coe
         * @param Closure   $treatment      Data treatment callback, must return an HTML string
         * @param integer   $cachetime      Caching time
         * @param string    $cachefile      Caching file name
        **/
        protected function _getHtmlResponse($html, $code = 200, $charset = null, $cachetime = 0, $lastModifiedTime = 0, $public = false) {

            // Instanciate $response
            $response = $this->response;
            $response->setStatus($code);

            $charset = ($charset ? $charset : $this->_getAcceptedCharset());
            $response->setHeader('Content-Type', 'text/html;charset='.$charset);

            // Set cache headers
            if($cachetime) {

                $etag = md5($html);

                if($this->request->getHeader('If-None-Match') == $etag) {
                    return $this->response->withStatus(304, 'Not Modified');
                }

                $elapsed          = time() - $lastModifiedTime;
                $timeleft         = $elapsed + $cachetime;

                $datetime = new \DateTime(date('r', intval(time()+$timeleft)));
                $datetime->setTimezone(new \DateTimeZone('GMT'));
                $response->setHeader('Expires', $datetime->format('r'));

                // Cache life time
                $response->addHeader('Cache-Control', [
                    'max-age='.$timeleft, 's-maxage='.$timeleft, 'max-stale=0'
                ]);

                $response->setHeader('Pragma',         ($timeleft    ? 'cache'  : 'no-cache, no-store'));
                $response->addHeader('Cache-Control',  ($public      ? 'public' : 'private'));

                $response->addHeader('Etag', $etag);

                if(!empty($lastModifiedTime)) {
                    $datetime = new \DateTime(date('r', $lastModifiedTime));
                    $datetime->setTimezone(new \DateTimeZone('GMT'));
                    $response->addHeader('Last-Modified', $datetime->format('r'));
                }

            }

            // Set no-cache headers
            else {

                $response->setHeader('Pragma',         'no-cache, no-store');
                $response->setHeader('Cache-Control',  'private');
                $response->setHeader('Expires',        '0');

            }

            // Encoding content
            $resquestHeaders = $this->request->getHeaders();
            $encoding = $resquestHeaders->getHeaderOrderedValues('Accept-Encoding', ['null']);
            $gzip     = (($pos = array_search('gzip', $encoding)) !== false ? $pos : -1);
            $deflate  = (($pos = array_search('deflate', $encoding)) !== false ? $pos : -1);

            if($gzip || $deflate) {

                $method = ($gzip < $deflate ? 'gzip' : 'deflate');

                $body = $response->getBody();
                $body->write($method == 'gzip' ? gzencode($html) : gzdeflate($html));

                $response->setHeader('Content-Encoding', $method);
                $response->setHeader('Content-Length', $body->getSize());
                $response->setBody($body);

            }

            // Not encoded content
            else {
                $response->getBody()->write($html);
            }

            return $response;

        }


        /**
         * Retrieve accepted charset
         * @return string   Returns the prefered accepted charset (of the default one)
        **/
        protected function _getAcceptedCharset() {

            $defaultCharset   = $charset = mb_internal_encoding();
            $acceptedCharsets = $this->request->getHeaders()->getHeaderOrderedValues('Accept-charset');

            // Retrieve optimized charset
            if($acceptedCharsets) {

                $defaultCharsetPosition = array_search($defaultCharset, $acceptedCharsets);

                if($defaultCharsetPosition !== false && $defaultCharsetPosition > 0) {

                    $preferedCharset = $acceptedCharset[0];
                    if(in_array($preferedCharset, mb_list_encodings())) {

                        $charset = $preferedCharset;

                    }
                }

            }

            return $charset;

        }


        /**
         * Generate an HTML view
         * @param     string     $template     Template's path
         * @param     array      $data         View's data
         * @param     array      $parameters   Plates service parameters
         * @return    string     Returns an HTML string
        **/
        protected function _parseHtmlView($template, array $data = array(), array $parameters = array()) {

            $plates = $this->services->getService('plates', [
                'rootpath'   => (isset($parameters['rootpath']) ? $parameters['rootpath'] : null),
                'locale'     => (isset($parameters['locale']) ? $parameters['locale'] : null)
            ]);

            $html = $plates->render($template, $data);

            return $html;

        }


        /**
         * Generate a file response;
         * @param   string      $filepath       Absolute file path
         * @param   integer     $code           HTTP response code
         * @param   boolean     $download       Whether set download headers or not
        **/
        public function _getFileResponse($filepath, $code = 200, $download = false, $cachetime = 0, $public = false) {

            // The file must be found
            if(!file_exists($filepath)) {
                throw new \InvalidArgumentException('The file '.$filepath.' could not be found');
            }


            // Disable runtime time limits
            set_time_limit(0);

            $file           = new\WOK\Stream\Stream(fopen($filepath, 'r'));
            $mime           = $this->_getMimeType($filepath);
            $isTextFile     = (mb_substr($mime, 0, mb_strlen($textPattern = 'text/')) == $textPattern);

            $response = $this->response->withStatus($code);

            $response->setHeader('Content-Type', $mime);
            $response->setHeader('Content-Length', (string) $file->getSize());

            // Downloadable file header ?
            $response->setHeader(
                'Content-disposition',
                ($download ? 'attachment; filename='.basename($filepath) : 'inline')
            );

            // Set response body (not HEAD request)
            if($this->request->getMethod() != 'HEAD') {

                $response->setBody($file);

                // Accept ranges for non-text files
                if(!$isTextFile) {

                    // Tell the browser that this response accept bytes ranges
                    $response->setHeader('Accept-Ranges', 'bytes');


                    // Split the file as range (or set accept range header)
                    if($range = $this->request->getHeader('Range')) {

                        // Get max range size (default: 10% from memory_limit)
                        $maxRangeSize       = \Helpers\Fn::getbytes(ini_get('memory_limit'));
                        $maxRangeSize       = ($maxRangeSize ? round($maxRangeSize * .10, 0) : $file->getSize());
                        $maxRangeSize       = $file->getSize();

                        // Get parts size and length
                        $rangeUnit          = mb_strstr($range, '=', true);
                        $rangeInterval      = mb_substr($range, mb_strlen($rangeUnit)+1);
                        $rangeStartByte     = 0;
                        $rangeEndByte       = $maxRangeSize - 1; // $file->getSize()


                        // Accept any range header format (`x-`, `x-y`, `-y`)
                        list($rangeStartByte, $rangeEndByte) = explode('-', $rangeInterval);
                        if(!$rangeEndByte) {

                            $maxRangeLength = $file->getSize() - $rangeStartByte;
                            if($maxRangeLength > $maxRangeSize) {
                                $maxRangeLength = $maxRangeSize;
                            }

                            $rangeEndByte = $rangeStartByte + $maxRangeLength - 1;

                        }

                        // Bytes unit required and end byte must be bigger than starting one
                        if($rangeUnit != 'bytes' || $rangeStartByte > $rangeEndByte) {

                            // Wrong range request response
                            $response->setStatus(416, 'Requested Range Not Satisfiable');
                            $response->setHeader('Content-Range', 'bytes */' . $file->getSize());

                        }

                        // Alright ! Everything is OK
                        else {

                            $rangeLength = ($rangeEndByte - $rangeStartByte) + 1;
                            $rangeStream = new \WOK\Stream\Stream(fopen('php://temp', 'w+'));

                            // Redefine response headers
                            $response->setStatus(206, 'Partial Content');
                            $response->setHeader('Content-Length', $rangeLength);
                            $response->setHeader('Content-Range',
                                $rangeUnit.' '.$rangeStartByte.'-'.$rangeEndByte.'/'.$file->getSize()
                            );

                            // Retrieve file content range
                            $file->seek($rangeStartByte);
                            $rangeStream->write($file->read($rangeLength));

                            $response->setBody($rangeStream);

                        }

                    }

                }

                // Encoding content
                if($isTextFile) {

                    $resquestHeaders = $this->request->getHeaders();
                    $encoding = $resquestHeaders->getHeaderOrderedValues('Accept-Encoding', ['null']);
                    $gzip     = (($pos = array_search('gzip', $encoding)) !== false ? $pos : -1);
                    $deflate  = (($pos = array_search('deflate', $encoding)) !== false ? $pos : -1);

                    if($gzip || $deflate) {

                        $content    = $response->getBody()->getContent();
                        $method     = ($gzip < $deflate ? 'gzip' : 'deflate');

                        $body = new \WOK\Stream\Stream(fopen('php://temp', 'w+'));

                        $response->setHeader('Content-Encoding', $method);
                        $body->write($method == 'gzip' ? gzencode($content) : gzdeflate($content));

                        $response->setBody($body);
                        $response->setHeader('Content-Length', $body->getSize());

                    }

                }

            }

            // Set cache headers
            if($cachetime) {

                $etag = md5((string)$response->getBody());

                if($this->request->getHeader('If-None-Match') == $etag) {
                    return $this->response->withStatus(304, 'Not Modified');
                }

                $lastModifiedTime = filemtime($filepath);
                $elapsed          = time() - $lastModifiedTime;
                $timeleft         = $elapsed + $cachetime;

                $datetime = new \DateTime(date('r', intval(time()+$timeleft)));
                $datetime->setTimezone(new \DateTimeZone('GMT'));
                $response->setHeader('Expires', $datetime->format('r'));

                // Cache life time
                $response->addHeader('Cache-Control', [
                    'max-age='.$timeleft, 's-maxage='.$timeleft, 'max-stale=0'
                ]);

                $response->setHeader('Pragma',         ($timeleft    ? 'cache'  : 'no-cache, no-store'));
                $response->addHeader('Cache-Control',  ($public      ? 'public' : 'private'));

                $response->addHeader('Etag', $etag);

                if(!empty($lastModifiedTime)) {
                    $datetime = new \DateTime(date('r', $lastModifiedTime));
                    $datetime->setTimezone(new \DateTimeZone('GMT'));
                    $response->addHeader('Last-Modified', $datetime->format('r'));
                }

            }

            // Set no-cache headers
            else {

                $response->setHeader('Pragma',         'no-cache, no-store');
                $response->addHeader('Cache-Control',  'private');
                $response->setHeader('Expires',        '0');

            }

            return $response;

        }


        /**
         * Retrieve mime type from extension
         * @param   string      $filename       File path or filename
        **/
        protected function _getMimeType($filename) {

            switch(pathinfo($filename, PATHINFO_EXTENSION)) {

                case 'js':
                    $mime = 'application/javascript';
                    break;

                case 'css':
                    $mime = 'text/css';
                    break;

                case 'svg':
                    $mime = 'image/svg+xml';
                    break;

                default:
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $filename);
                    finfo_close($finfo);

            }

            return $mime;

        }

        /**
         * Page not found
         * ---
         * @note This action invoke the Site\Errors->pageNotFound() action
        **/
        public function pageNotFound($argument = null) {

            $reflection = new \ReflectionClass('Controllers\Site\Errors');
            $controller = $reflection->newInstance($this->services);

            // @note This call the __invoke() controller method
            return call_user_func($controller, 'pageNotFound', [$argument]);

        }

    }
