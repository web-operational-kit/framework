<?php

    /**
     * Doctrine/Cache
     * ---
     * @param   string     $store     Location path
    **/
    use \Doctrine\Common\Cache\FilesystemCache;

    return function($store = null) {

        $cache = new FilesystemCache(
            __ROOT_DIR__.'/var/cache'.(!empty($store) ? '/'.$store : ''), '.tmp'
        );
        return $cache;

    };
