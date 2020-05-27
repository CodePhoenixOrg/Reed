<?php
/*
 * Copyright (C) 2020 David Blanchard
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

namespace Reed;

use Reed\Cache\TCache;
use Reed\Core\TStaticObject;
use Reed\MVC\TCustomView;
use Reed\MVC\TPartialView;
use Reed\Registry\TRegistry;
use Reed\Template\TCustomTemplate;
use Reed\Web\UI\TCustomCachedControl;
use Reed\Web\UI\TCustomControl;

class TAutoloader extends TStaticObject
{
    private $directory;
    private $prefix;
    private $prefixLength;

    /**
     * @param string $baseDirectory Base directory where the source files are located.
     */
    public function __construct($baseDirectory = __DIR__)
    {
        // $this->directory = $baseDirectory;
        // $this->prefix = __NAMESPACE__ . '\\';
        // $this->prefixLength = strlen($this->prefix);
    }

    /**
     * Registers the autoloader class with the PHP SPL autoloader.
     *
     * @param bool $prepend Prepend the autoloader on the stack instead of appending it.
     */
    public static function register($prepend = false)
    {
        // spl_autoload_register(array(new self, 'autoload'), true, $prepend);
    }

    public static function innerClassNameToFilename($className): string
    {
        $translated = '';
        $className = substr($className, 1);
        $l = strlen($className);
        for ($i = 0; $i < $l; $i++) {
            if (ctype_upper($className[$i])) {
                $translated .= '_' . strtolower($className[$i]);
            } else {
                $translated .= $className[$i];
            }
        }

        $translated = substr($translated, 1);

        return $translated;
    }

    public static function userClassNameToFilename($className): string
    {
        $translated = '';
        $l = strlen($className);
        for ($i = 0; $i < $l; $i++) {
            if (ctype_upper($className[$i])) {
                $translated .= '_' . strtolower($className[$i]);
            } else {
                $translated .= $className[$i];
            }
        }

        $translated = substr($translated, 1);

        return $translated;
    }


    /**
     * Loads a class from a file using its fully qualified name.
     *
     * @param string $className Fully qualified name of a class.
     */
    public function autoload($className)
    {
        // if (0 === strpos($className, $this->prefix)) {
        //     $parts = explode('\\', substr($className, $this->prefixLength));
        //     $className = array_pop($parts);

        //     $translated = self::innerClassNameToFilename($className);

        //     $filepath = $this->directory . DIRECTORY_SEPARATOR . strtolower(implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . $translated);
        //     $filepath .= (file_exists($filepath . PREHTML_EXTENSION)) ? CLASS_EXTENSION : '.php';

        //     //file_put_contents(STARTER_FILE, "include '$filepath';"  . "\n", FILE_APPEND);
        //     include $filepath;
        // }
    }

    private static function _includeInnerClass(TCustomView $view, object $info, bool $withCode = true): array
    {
        $className = $view->getClassName();
        $viewName = $view->getViewName();

        // $filename = $info->path . 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR  . \Reed\TAutoloader::innerClassNameToFilename($className) . CLASS_EXTENSION;
        // $filename = $view->getControllerFileName();
        $filename = $info->path . 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR  . $viewName. CLASS_EXTENSION;

        if ($filename[0] == '@') {
            $filename = \str_replace('@/', Reed_APPS_ROOT, $filename);
        }
        if ($filename[0] == '~') {
            $filename = \str_replace('~/', Reed_WIDGETS_ROOT, $filename);
        }
        //self::getLogger()->debug('INCLUDE INNER PARTIAL CONTROLLER : ' . $filename, __FILE__, __LINE__);

        $code = file_get_contents($filename, FILE_USE_INCLUDE_PATH);

        if ($withCode) {
            $code = substr(trim($code), 0, -2) . PHP_EOL . CONTROL_ADDITIONS;
            TRegistry::setCode($view->getUID(), $code);
        }

        return [$filename, $info->namespace . '\\' . $className, $code];
    }

    /**
     * Load the controller file, parse it in search of namespace and classname.
     * Alternatively execute the code if the class is not already declared
     *
     * @param string $filename The controller filename
     * @param int $params The bitwise constants values that determine the behavior
     *                    INCLUDE_FILE : execute the code
     *                    RETURN_CODE : ...
     * @return boolean
     */
    public static function includeClass(string $filename, $params = 0): ?array
    {
        $classFilename = SRC_ROOT . $filename;
        if (!file_exists($classFilename)) {
            $classFilename = SITE_ROOT . $filename;
        }
        if (!file_exists($classFilename)) {
            return null;
        }

        list($namespace, $className, $code) = self::getClassDefinition($classFilename);

        $fqClassName = trim($namespace) . "\\" . trim($className);

        $file = str_replace('\\', '_', $fqClassName) . '.php';

        if (isset($params) && ($params && RETURN_CODE === RETURN_CODE)) {
            $code = substr(trim($code), 0, -2) . PHP_EOL . CONTROL_ADDITIONS;
            TRegistry::setCode($filename, $code);
        }

        self::getLogger()->debug(__METHOD__ . '::' . $filename, __FILE__, __LINE__);

        if ((isset($params) && ($params && INCLUDE_FILE === INCLUDE_FILE)) && !class_exists('\\' . $fqClassName)) {
            if (\Phar::running() != '') {
                include pathinfo($filename, PATHINFO_BASENAME);
            } else {
                //include $classFilename;
            }
        }

        return [$classFilename, $fqClassName, $code];
    }

     /**
     * Load the controller file, parse it in search of namespace and classname.
     * Alternatively execute the code if the class is not already declared
     *
     * @param string $filename The controller filename
     * @param int $params The bitwise constants values that determine the behavior
     *                    INCLUDE_FILE : execute the code
     *                    RETURN_CODE : ...
     * @return boolean
     */
    public static function includeViewClass(TCustomTemplate $view, $params = 0): ?array
    {
        $filename = $view->getControllerFileName();
        $classFilename = SRC_ROOT . $filename;
        if (!file_exists($classFilename)) {
            $classFilename = SITE_ROOT . $filename;
        }
        if (!file_exists($classFilename)) {
            return null;
        }

        list($namespace, $className, $code) = self::getClassDefinition($classFilename);

        $fqClassName = trim($namespace) . "\\" . trim($className);

        $file = str_replace('\\', '_', $fqClassName) . '.php';

        if (isset($params) && ($params && RETURN_CODE === RETURN_CODE)) {
            $code = substr(trim($code), 0, -2) . PHP_EOL . CONTROL_ADDITIONS;
            TRegistry::setCode($view->getUID(), $code);
        }

        self::getLogger()->debug(__METHOD__ . '::' . $filename, __FILE__, __LINE__);

        if ((isset($params) && ($params && INCLUDE_FILE === INCLUDE_FILE)) && !class_exists('\\' . $fqClassName)) {
            if (\Phar::running() != '') {
                include pathinfo($filename, PATHINFO_BASENAME);
            } else {
                //include $classFilename;
            }
        }

        return [$classFilename, $fqClassName, $code];
    }

    public static function includeModelByName(string $modelName): ?array
    {
        $file = '';
        $type = '';
        $code = '';

        //self::getLogger()->debug('MODEL NAME : ' . $modelName, __FILE__, __LINE__);

        $modelFileName = 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $modelName . CLASS_EXTENSION;

        $result = self::includeClass($modelFileName, INCLUDE_FILE);
        if ($result !== null) {
            list($file, $type, $code) = $result;
        }
        if ($type === '') {
            $type = DEFAULT_MODEL;
        }
        $file = $modelFileName;

        return [$file, $type, $code];
    }

    public static function includeDefaultController(string $namespace, string $className): array
    {
        $file = '';
        $type = '';
        $code = '';
        $type = DEFAULT_CONTROLLER;
        $code = self::controllerTemplate($namespace, $className);
        $code = substr(trim($code), 0, -2) . CONTROL_ADDITIONS;

        return [$file, $type, $code];
    }

    public static function includeDefaultPartialController(string $namespace, string $className): array
    {
        $file = '';
        $type = '';
        $code = '';
        $type = DEFAULT_PARTIAL_CONTROLLER;
        $code = self::partialControllerTemplate($namespace, $className);
        $code = substr(trim($code), 0, -2) . CONTROL_ADDITIONS;

        return [$file, $type, $code];
    }

    public static function import(TCustomControl $ctrl, string $className): bool
    {
        if (!isset($className)) {
            $className = $ctrl->getClassName();
        }
        $result = false;
        $file = '';
        $type = '';
        $code = '';

        $cacheFilename = '';
        //$classFilename = '';
        $cacheJsFilename = '';
        $viewName = '';

        $info = TRegistry::classInfo($className);
        self::getLogger()->dump('CLASS INFO::' . $className, $info, __FILE__, __LINE__);

        if ($info !== null) {
            $viewName = self::innerClassNameToFilename($className);
            $path = PHINK_VENDOR_LIB . $info->path;

            if ($info->path[0] == '@') {
                $path = str_replace("@" . DIRECTORY_SEPARATOR, PHINK_VENDOR_APPS, $info->path);
            }
            if ($info->path[0] == '~') {
                $path = str_replace("~" . DIRECTORY_SEPARATOR, PHINK_VENDOR_WIDGETS, $info->path);
            }

            $cacheFilename = TCache::cacheFilenameFromView($viewName, $ctrl->isInternalComponent());
        }
        if ($info === null) {
            $viewName = self::userClassNameToFilename($className);

            //$classFilename = 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $className . CLASS_EXTENSION;
            $cacheFilename = TCache::cacheFilenameFromView($viewName, $ctrl->isInternalComponent());
            self::getLogger()->debug('CACHED JS FILENAME: ' . $cacheJsFilename, __FILE__, __LINE__);
        }
        $cacheJsFilename = TCache::cacheJsFilenameFromView($viewName, $ctrl->isInternalComponent());
        $cacheCssFilename = TCache::cacheCssFilenameFromView($viewName, $ctrl->isInternalComponent());

        if (file_exists(SRC_ROOT . $cacheFilename)) {

            if (file_exists(DOCUMENT_ROOT . $cacheJsFilename)) {
                $ctrl->appendJsToBody($viewName);

                self::getLogger()->debug('INCLUDE CACHED JS CONTROL: ' . DOCUMENT_ROOT . $cacheJsFilename, __FILE__, __LINE__);
                $ctrl->getResponse()->addScript($cacheJsFilename);
            }
            self::getLogger()->debug('INCLUDE CACHED CONTROL: ' . SRC_ROOT . $cacheFilename, __FILE__, __LINE__);
            // self::includeClass($cacheFilename, RETURN_CODE);

            include SRC_ROOT . $cacheFilename;

            return true;
        }

        $include = null;
        //            $modelClass = ($include = TAutoloader::includeModelByName($viewName)) ? $include['type'] : DEFALT_MODEL;
        //            include SRC_ROOT . $include['file'];
        //            $model = new $modelClass();


        self::getLogger()->debug('PARSING ' . $viewName . '!!!');
        $view = new TPartialView($ctrl, $className);

        if ($info !== null) {
            list($file, $type, $code) = self::_includeInnerClass($view, $info);
            $view->getCacheFilename();
        } else {
            list($file, $type, $code) = self::includeViewClass($view, RETURN_CODE);
        }
        TRegistry::setCode($view->getUID(), $code);
        self::getLogger()->debug($view->getControllerFileName() . ' IS REGISTERED : ' . (TRegistry::exists('code', $view->getControllerFileName()) ? 'TRUE' : 'FALSE'), __FILE__, __LINE__);
        self::getLogger()->debug('CONTROLLER FILE NAME OF THE PARSED VIEW: ' . $view->getControllerFileName());
        $view->parse();

        self::getLogger()->debug('CACHE FILE NAME OF THE PARSED VIEW: ' . $view->getCacheFileName());
        self::getLogger()->debug('ROOT CACHE FILE NAME OF THE PARSED VIEW: ' . SRC_ROOT . $cacheFilename);

        include SRC_ROOT . $cacheFilename;

        return true;
    }

    public static function loadCachedFile(Web\IWebObject $parent): TCustomCachedControl
    {
        self::getLogger()->dump('PARENT OBJECT', $parent->getType());
        self::getLogger()->dump('PARENT OBJECT', $parent->getClassName());

        // $parent->setCacheFilename();
        $cacheFilename = $parent->getCacheFilename();
        self::getLogger()->debug('CACHE FILE NAME TO INCLUDE: ' . $cacheFilename);

        list($namespace, $className, $code) = self::getClassDefinition($cacheFilename);

        include $cacheFilename;

        $fqClassName = $namespace . '\\' . $className;

        $controller = new $fqClassName($parent);

        return $controller;
    }


    public static function getClassDefinition(string $filename): array
    {
        $classText = file_get_contents($filename);

        $namespace = self::grabKeywordName('namespace', $classText, ';');
        $className = self::grabKeywordName('class', $classText, ' ');

        return [$namespace, $className, $classText];
    }

    public static function grabKeywordName(string $keyword, string $classText, $delimiter): string
    {
        $result = '';

        $start = strpos($classText, $keyword);
        if ($start > -1) {
            $start += \strlen($keyword) + 1;
            $end = strpos($classText, $delimiter, $start);
            $result = substr($classText, $start, $end - $start);
        }

        return $result;
    }

    public static function getDefaultNamespace(): string
    {
        $sa = explode('.', SERVER_NAME);
        array_pop($sa);
        if (count($sa) == 2) {
            array_shift($sa);
        }

        return ucfirst(str_replace('-', '_', $sa[0])) . '\\Controllers';
    }

    public static function controllerTemplate(string $namespace, string $className): string
    {
        $result = <<<CONTROLLER
<?php
namespace $namespace;

use Reed\MVC\TController;

class $className extends TController
{
    
       
}
CONTROLLER;

        return $result;
    }

    public static function partialControllerTemplate(string $namespace, string $className): string
    {
        $result = <<<CONTROLLER
<?php
namespace $namespace;

use Reed\MVC\TPartialController;

class $className extends TPartialController
{
    
       
}
CONTROLLER;

        return $result;
    }
}
