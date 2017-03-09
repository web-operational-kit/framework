<?php

    /**
     * JoliCode/JoliTypo
     * ---
     * @param   string      $locale     Locale key code
     * @param   array       $custom     Custom parameters (prefix value with `-` to remove)
    **/
    return function($locale, array $custom = array()) use ($settings) {

        $fixers     = $settings->services->jolitypo;
        $parameters = (isset($fixers[$locale]) ? $fixers[$locale] : array());

        // Custom parameters
        if(!empty($custom)) {
            foreach($custom as $param) {

                /* Cases: ['-RemoveOption', '+AddOption', 'AddOption'] */
                $prefix = mb_substr($param, 0, 1);

                if($prefix == '-' || $prefix == '+') {
                    $param = mb_substr($param, 1);
                }

                $pos = array_search($param, $parameters);

                if($prefix == '-' && $pos !== false) {
                    unset($parameters[$pos]);
                }
                else {
                    $parameters[] = $param;
                }

            }
        }

        $jolitypo = new \JoliTypo\Fixer($parameters);
        $jolitypo->setLocale($locale);

        return $jolitypo;

    };
