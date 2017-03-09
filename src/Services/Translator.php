<?php

    /**
     * Symfony/Translator
     * ---
     * @param   string      $locale         Locale key
    **/
    return function($locale, array $domains = array()) use($settings) {

        // Retrieve language from locale
        $language = mb_strstr($locale, '_', true);

        // Set default domain as `messages`
        if(empty($domains)) {
            $domains[] = 'messages';
        }

        // Instanciate service
        $translator = new \Symfony\Component\Translation\Translator($locale);
        $translator->addLoader('ini', new \Symfony\Component\Translation\Loader\IniFileLoader());

        // Register fallback locales
        $translator->setFallbackLocale($settings->locales->accepted);

        // Register translations
        foreach($domains as $domain) {
            $filepath = __ROOT_DIR__.'/src/Locales/'.$locale.'/'.$domain.'.'.$language.'.ini';
            $translator->addResource('ini', $filepath, $locale, $domain);
        }

        return $translator;

    };
