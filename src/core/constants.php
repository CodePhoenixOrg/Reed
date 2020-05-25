<?php

namespace Reed\Core\Constants;

define('DOCUMENT_ROOT', isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '');

define('IS_WEBAPP', !empty(DOCUMENT_ROOT));
// Web environment
if (IS_WEBAPP) {
    
    define('SRC_DIR', realpath(DOCUMENT_ROOT . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR);
    define('ROOT_DIR', realpath(SRC_DIR . '..') . DIRECTORY_SEPARATOR);
    define('CACHE_DIR', SRC_DIR . 'cache' . DIRECTORY_SEPARATOR);
    define('APP_DIR', SRC_DIR . 'app' . DIRECTORY_SEPARATOR);
    define('APP_DATA', ROOT_DIR . 'data' . DIRECTORY_SEPARATOR);
    define('WEB_DIR', SRC_DIR . 'web' . DIRECTORY_SEPARATOR);
    define('VIEWS_DIR', APP_DIR . 'views' . DIRECTORY_SEPARATOR);

    define('REQUEST_URI', $_SERVER['REQUEST_URI']);
    define('REQUEST_PARAMS', $_REQUEST);
    define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
    define('QUERY_STRING', $_SERVER['QUERY_STRING']);
    define('ACCEPT', $_SERVER['HTTP_ACCEPT']);
}

// CLI environment
if (!IS_WEBAPP) {
    define('CACHE_DIR', 'cache');
    define('SRC_DIR', '.' . DIRECTORY_SEPARATOR);
    define('ROOT_DIR', realpath(SRC_DIR . '..') . DIRECTORY_SEPARATOR);
}

define('INPUT_DIR', ROOT_DIR . 'input' . DIRECTORY_SEPARATOR);
define('OUTPUT_DIR', ROOT_DIR . 'output' . DIRECTORY_SEPARATOR);
define('CONFIG_DIR', ROOT_DIR . 'config' . DIRECTORY_SEPARATOR);
define('LOGS_DIR', ROOT_DIR . 'logs' . DIRECTORY_SEPARATOR);

define('JSON_EXTENSION', '.json');
define('CSV_EXTENSION', '.csv');
