<?php

/*
 * Copyright (C) 2019 David Blanchard
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Reed\Log;

//require_once 'constants.php';

/**
 * Description of log
 *
 * @author david
 */
class TLog
{
    private static $_logger = null;

    private function __construct()
    {

    }

    public static function create()
    {
        if (self::$_logger === null) {
            self::$_logger = new TLog();
        }
        return self::$_logger;
    }

    public function dump($message, $object)
    {
        $this->debug($message . '::' . print_r($object, true) . PHP_EOL);
    }

    public function debug($message, $filename = null, $line = null)
    {
        $this->_log(DEBUG_LOG, $message, $filename, $line);
    }

    public function sql($message, $filename = null, $line = null)
    {
        $this->_log(SQL_LOG, $message, $filename, $line);
    }

    public function error(\Throwable $ex, $filename = null, $line = null)
    {
        $message = '';

        if ($ex instanceof \ErrorException) {
            $message .= 'Error severity: ' . $ex->getSeverity() . PHP_EOL;
        }
        $message .= 'Error code: ' . $ex->getCode() . PHP_EOL;
        $message .= 'In ' . $ex->getFile() . ', line ' . $ex->getLine() . PHP_EOL;
        $message .= 'With the message: ' . $ex->getMessage() . PHP_EOL;
        $message .= 'Stack trace: ' . $ex->getTraceAsString() . PHP_EOL;

        $this->_log(ERROR_LOG, $message, $filename, $line);
    }

    private function _log($filepath, $message, $filename = null, $line = null)
    {
        $message = (is_array($message) || is_object($message)) ? print_r($message, true) : $message;

        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755);
        }
        $handle = fopen($filepath, 'a');

        if (SRC_ROOT) {
            $filename = substr($filename, strlen(SRC_ROOT));
        }
        $message = date('Y-m-d h:i:s') . ((isset($filename)) ? ":$filename" : '') . ((isset($line)) ? ":$line" : '') . " : $message" . PHP_EOL;
        fwrite($handle, $message . PHP_EOL);
        fclose($handle);
    }

    public function getDebugLog(): string
    {
        if (!\file_exists(DEBUG_LOG)) {
            return '';
        }
        return \file_get_contents(DEBUG_LOG);
    }

    public function getErrorLog(): string
    {
        if (!\file_exists(ERROR_LOG)) {
            return '';
        }
        return \file_get_contents(ERROR_LOG);
    }

    public function getSqlLog(): string
    {
        if (!\file_exists(SQL_LOG)) {
            return '';
        }
        return \file_get_contents(SQL_LOG);
    }

    public function getPhpErrorLog(): string
    {
        if (!\file_exists(DOCUMENT_ROOT . 'php_error_log')) {
            return '';
        }
        return \file_get_contents(DOCUMENT_ROOT . 'php_error_log');
    }

    public function clearAll(): void
    {
        if (file_exists(DEBUG_LOG)) {
            unlink(DEBUG_LOG);
        }
        if (file_exists(SQL_LOG)) {
            unlink(SQL_LOG);
        }
        if (file_exists(DOCUMENT_ROOT . 'php_error_log')) {
            unlink(DOCUMENT_ROOT . 'php_error_log');
        }

    }
}
