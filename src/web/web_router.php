<?php
namespace Reed\Web;

use Reed\Core\Router;
use Reed\MVC\CustomView;
use Reed\Registry\Registry;
use Reed\Autoloader;
use Reed\Core\Autoloader as CoreAutoloader;
use Reed\MVC\View;

/**
 * Description of router
 *
 * @author David
 */
class WebRouter extends Router
{
    private $_isCached = false;

    public function __construct($parent)
    {
        $this->clonePrimitivesFrom($parent);

        $this->translation = $parent->getTranslation();
    }

    public function translate(): bool
    {
        $isTranslated = false;

        $info = (object) \pathinfo($this->path);
        $this->viewName = $info->filename;
        $this->dirName = $info->dirname;
        $this->bootDirName = $info->dirname;

        if ($this->componentIsInternal) {
            $this->dirName = dirname($this->dirName, 2);
        }

        $this->className = ucfirst($this->viewName);

        $this->setNamespace();
        $this->setNames();

        if (file_exists(SRC_ROOT . $this->getPath())) {
            // $this->path = SRC_ROOT . $this->getPath();
            $isTranslated = true;
        }

        if (file_exists(SITE_ROOT . $this->getPath())) {
            // $this->path = SITE_ROOT . $this->getPath();
            $isTranslated = true;
        }

        $this->_isCached = file_exists($this->getCacheFileName());

        return $this->_isCached || $isTranslated;
    }

    public function dispatch(): bool
    {

        $dir =  dirname(SRC_ROOT . $this->bootDirName, 1) . DIRECTORY_SEPARATOR;

        if ($this->componentIsInternal) {
            $dir =  dirname(SITE_ROOT . $this->bootDirName, 1) . DIRECTORY_SEPARATOR;
        }

        if (file_exists($dir . 'bootstrap' . CLASS_EXTENSION)) {
            list($namespace, $className, $classText) = CoreAutoloader::getClassDefinition($dir . 'bootstrap' . CLASS_EXTENSION);
            include $dir . 'bootstrap' . CLASS_EXTENSION;

            $bootstrapClass = $namespace . '\\'  . $className;

            $bootstrap = new $bootstrapClass($dir);
            $bootstrap->start();
        }

        $view = new TView($this);

        if ($this->_isCached) {
            $class = Autoloader::loadCachedFile($view);
            $class->perform();
            return true;
        }

        list($file, $class, $classText) = $this->includeController($view);
        $namespace = Autoloader::grabKeywordName('namespace', $classText, ';');
        $className = Autoloader::grabKeywordName('class', $classText, ' ');

        $view->parse();
        $uid = $view->getUID();
        $code = Registry::getCode($uid);

        // file_put_contents($this->getCacheFileName(), $code);

        eval('?>' . $code);

        $fqClassName = $namespace . '\\' . $className;

        $controller = new $fqClassName($view);

        $controller->perform();

        if ($view->isReedEngine()) {
            // cache the file
            $php = Registry::getHtml($uid);
            $code = str_replace(HTML_PLACEHOLDER, $php, $code);
            file_put_contents($this->getCacheFileName(), $code);
        }
        return false;
    }

    public function setNamespace(): void
    {
        if (file_exists(CONFIG_DIR . 'namespace')) {
            $this->namespace = file_get_contents(CONFIG_DIR . 'namespace');
            $this->namespace .= '\\Controllers';

            return;
        }

        $re = '/([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/m';
        // preg_match($re, SERVER_NAME, $matches, PREG_OFFSET_CAPTURE, 0);
        $namespace = \preg_replace($re, 'Localhost', SERVER_NAME);

        $sa = explode('.', $namespace);

        if (count($sa) == 0) {
            $sa = [$namespace];
        }
        if (count($sa) > 1) {
            array_pop($sa);
            if (count($sa) == 2) {
                array_shift($sa);
            }
        }
        $this->namespace = str_replace('-', '_', ucfirst($sa[0]));
        file_put_contents(CONFIG_DIR . 'namespace', $this->namespace);

        $this->namespace .= '\\Controllers';
    }

    public function includeController(CustomView $view): ?array
    {
        $file = '';
        $type = '';
        $code = '';


        $result = Autoloader::includeViewClass($view, RETURN_CODE);
        if ($result !== null) {
            list($file, $type, $code) = $result;
        }
        if ($result === null) {
            if ($this->isClientTemplate() && $this->request->isPartialView()) {
                list($file, $type, $code) = Autoloader::includeDefaultPartialController($this->namespace, $this->className);
            } else {
                list($file, $type, $code) = Autoloader::includeDefaultController($this->namespace, $this->className);
            }

            Registry::setCode($view->getUID(), $code);
        }

        return [$file, $type, $code];
    }
}
