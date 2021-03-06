<?php

error_reporting(0);
define("LUXON", true);

try {
    function _sort($path, &$entries) {
        $new_entries = [];

        foreach ($entries as $entry) {
            $fullpath = $path.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($fullpath)) {
                array_unshift($new_entries, $entry);
            } else if (is_file($fullpath)) {
                array_push($new_entries, $entry);
            }
        }

        $entries = $new_entries;
    }

    function __load($path) {
        if (is_dir($path)) {
            $entries = scandir($path);
            _sort($path, $entries);

            foreach ($entries as $entry) {
                $fullpath = $path.DIRECTORY_SEPARATOR.$entry;
                if (in_array($entry, array(".", "..")) || is_link($fullpath)) continue;
                __load($fullpath);
            }
        } else if (is_file($path) && preg_match("/\.php$/i", $path) === 1) {
            require_once $path;
        } else {
            throw new Exception("Failed to load directory or file: $path");
        }
    }

    $paths = ["config", "utils", "database", "modules", "models", "controllers", "other", "routes"];
    foreach ($paths as $path) {
        __load($path);
    }
    
    if (APP_REQUIRE_HTTPS && empty($_SERVER["HTTPS"])) {
        $host = $_SERVER['HTTP_HOST'];
        
        if (preg_match("/^[a-z0-9\.\-\:]*$/", $host) === 1) {
            $uri = $_SERVER['REQUEST_URI'];
            if (strlen($uri) > 0 && strpos($uri, '/') === 0) {
                header("Location: https://".$host.$uri);
                exit;
            }
        }
        
        throw new Exception();
    }
    
    Router::accept();
} catch (NoRouteException $_EXCEPTION) {
    global $_EXCEPTION;
    Router::setStatus(404); // not found
    include_once "errorpages/404_route.php";
} catch (Exception $_EXCEPTION) {
    global $_EXCEPTION;
    Router::setStatus(500); // internal server error
    include_once "errorpages/500_internal.php";
}