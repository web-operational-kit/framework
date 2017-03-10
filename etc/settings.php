<?php

    /**
     *
     * Settings
     * ===
     *
     * This file returns the whole collection of
     * the project settings that may be used within itself.
     *
     * This file must return an array or a Collection through
     * the WOK\Collection\Collection component.
     *
    **/

    /**
     * @const WOK_ENV_DEBUG          Debug environment value
     * @const WOK_ENV_PREPROD        Pre-production environment value
     * @const WOK_ENV_PRODUCTION     Production environment value
     * @note  You could add some more environment constants using the binary values
    **/
    const WOK_ENV_DEBUG         = 0x01;
    const WOK_ENV_PREPROD       = 0x02;
    const WOK_ENV_PRODUCTION    = 0x08;


    return (object) array(


        /**
         * Environment state
         * ---
         * @see     WOK_ENVIRONMENT_* constants
         * @note    Environment states must be cumulated throught bits operators
         * @see     http://php.net/manual/fr/language.operators.bitwise.php
        **/
        'environment'   => WOK_ENV_DEBUG,


        /**
         * Locales
         * ---
        **/
        'locales'   => (object) array(
            'default'   => 'fr_FR',
            'accepted'  => array(
                'fr_FR',
                'en_US',
                'en_GB'
            ),
        ),


        /**
         * Services parameters
         * ---
        **/
        'services'  => (object) array(

            /**
             * Plates
             * ---
             *
             * Default plates folders
             * @see src/Services/Plates.php
             *
            **/
            'plates'  => (object) array(

                'extension' => 'php',
                'source'    => 'src/Templates',
                'folders'   => array(
                    'parts'    => 'parts',
                ),

            ),


            /**
             * JoliTypo
             * ---
             *
             * Default JoliTypo locales parameters.
             * @see src/Services/JoliTypo.php
             * @see https://github.com/jolicode/JoliTypo/blob/master/README.md#fixer-recommendations-by-locale
             *
            **/
            'jolitypo'  => array(

                'fr_FR' => array(
                    'Ellipsis', 'Dimension', 'Numeric', 'Dash',
                    'SmartQuotes', 'FrenchNoBreakSpace', 'NoSpaceBeforeComma',
                    'CurlyQuote', 'Hyphen', 'Trademark'
                ),

                'fr_CA' => array(
                    'Ellipsis', 'Dimension', 'Numeric', 'Dash', 'SmartQuotes',
                    'NoSpaceBeforeComma', 'CurlyQuote', 'Hyphen', 'Trademark'
                ),

                'en_GB' => array(
                    'Ellipsis', 'Dimension', 'Numeric',
                    'Dash', 'SmartQuotes', 'NoSpaceBeforeComma',
                    'CurlyQuote', 'Hyphen', 'Trademark'
                ),

                'de_DE' => array(
                    'Ellipsis', 'Dimension', 'Numeric', 'Dash', 'SmartQuotes',
                    'NoSpaceBeforeComma', 'CurlyQuote', 'Hyphen', 'Trademark'
                )

            ),


        )


    );
