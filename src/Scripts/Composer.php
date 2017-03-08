<?php

    namespace WOK\Scripts;

    use Composer\Script\Event;
    // use Composer\Installer\PackageEvent;


    class Composer {

        public static function install(Event $event) {

            // $package = $event->getOperation()->getPackage();
            $extra = $event->getComposer()->getPackage()->getExtra();

            $packages = $event->getComposer()->getRepositoryManager()->getLocalRepository()->getPackages();

            $installManager = $event->getComposer()->getInstallationManager();

            var_dump(
                $extra,
                $event->getName(),
                $event->getArguments()
            );
            // do stuff

        }

        public static function update(Event $event) {

            self::install($event);

        }


    }
