<?php

    /**
     * League\lates
     * ===
     * Plates is a templates engine
     * @see http://platesphp.com/
    **/
    use League\Plates\Engine as Plates;

    return function($path, $locale = null) use($services, $settings) {

        if(!$locale) { // Assign default locale
            $locale = $settings->locales->default;
        }

        $source = __ROOT_DIR__.'/'.$settings->services->plates->source;

        $plates = new Plates($source.'/'.$path);

        /**
         * Set folders
         * ---
         * @see http://platesphp.com/engine/folders/
        **/
        $plates->addFolder('templates', $source.'/'.$path);
        foreach($settings->services->plates->folders as $key => $folder) {
            $plates->addFolder($name, $source.'/'.$path.'/'.$folder);
        }


        /**
         * Define `link` helper
         * ---
         * The link helper provides access to routes URLs
        **/
        $router = $services->getService('Router');
        $plates->registerFunction('link', function($route, array $args = array()) use($router) {

            return $router->getRoute($route)->getUrl($args);

        });

        /**
         * Define `i18n` helper
         * ---
         * The translate helper provides key to translated data strings
        **/
        $plates->registerFunction('i18n', function($key, array $parameters = array()) use($services, $path, $locale) {

                $domain = 'messages';

                // Retrieve domain [domain:id]
                if(($pos = strpos($key, ':')) !== false) {
                    $domain = substr($key, 0, -strlen($key) + $pos);
                    $key = substr($key, $pos + 1);
                }

                $translator = $services->getService('Locales', [$locale, [$domain], $path]);

                // Apply multiple choice translation [domain:id(n)]
                if(preg_match('/\((\d+)\)$/', $key, $match)) {
                    return $translator->transChoice($key, $match[1], $parameters, $domain);
                }

                // Default translation
                return $translator->trans($key, $parameters, $domain);

            }
        );


        /**
         * Define `fixTypo` helper
         * ---
         * The fixTypo helper provides a microtypography fixer
        **/
        $jolitypo = $services->getService('JoliTypo', [$locale]);
        $plates->registerFunction('fixTypo', function($string, $block = false) use($jolitypo) {

            return ($block ? $jolitypo->fix($string) : $jolitypo->fixString($string));

        });


        return $plates;

   };
