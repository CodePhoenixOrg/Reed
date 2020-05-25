<?php

namespace Reed\Core;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of autoload
 *
 * @author David
 */
class Autoloader
{
    /**
     * Registers the autoloader class with the PHP SPL autoloader.
     *
     * @param bool $prepend Prepend the autoloader on the stack instead of appending it.
     */
    public static function register($prepend = false)
    {
        spl_autoload_register(array(new self, 'autoload'), true, $prepend);
    }

    public function autoload($fqClassName)
    {
        $className = ltrim($fqClassName, '\\');
        $fileName  = '';


        $nsParts = explode('\\', $className);

        $className = lcfirst(array_pop($nsParts));
        $filepath = strtolower(implode(DIRECTORY_SEPARATOR, $nsParts));

        $fileName = DOCUMENT_ROOT . 'app' . DIRECTORY_SEPARATOR . $filepath . DIRECTORY_SEPARATOR . $className . '.class.php';

    }


}
