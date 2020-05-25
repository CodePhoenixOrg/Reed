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

namespace Phink\Web;

use Phink\Core\TRouter;
use Phink\MVC\TCustomView;
use Phink\Registry\TRegistry;
use Phink\TAutoloader;
use Phink\MVC\TView;

/**
 * Description of router
 *
 * @author David
 */
class TWebRouter extends TRouter
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
            list($namespace, $className, $classText) = TAutoloader::getClassDefinition($dir . 'bootstrap' . CLASS_EXTENSION);
            include $dir . 'bootstrap' . CLASS_EXTENSION;

            $bootstrapClass = $namespace . '\\'  . $className;

            $bootstrap = new $bootstrapClass($dir);
            $bootstrap->start();
        }

        $view = new TView($this);

        if ($this->_isCached) {
            $class = TAutoloader::loadCachedFile($view);
            $class->perform();
            return true;
        }

        list($file, $class, $classText) = $this->includeController($view);
        $namespace = TAutoloader::grabKeywordName('namespace', $classText, ';');
        $className = TAutoloader::grabKeywordName('class', $classText, ' ');

        $view->parse();
        $uid = $view->getUID();
        $code = TRegistry::getCode($uid);

        // file_put_contents($this->getCacheFileName(), $code);

        eval('?>' . $code);

        $fqClassName = $namespace . '\\' . $className;

        $controller = new $fqClassName($view);

        $controller->perform();

        if ($view->isReedEngine()) {
            // cache the file
            $php = TRegistry::getHtml($uid);
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

    public function includeController(TCustomView $view): ?array
    {
        $file = '';
        $type = '';
        $code = '';


        $result = TAutoloader::includeViewClass($view, RETURN_CODE);
        if ($result !== null) {
            list($file, $type, $code) = $result;
        }
        if ($result === null) {
            if ($this->getRequest()->isAJAX() && $this->request->isPartialView()) {
                list($file, $type, $code) = TAutoloader::includeDefaultPartialController($this->namespace, $this->className);
            } else {
                list($file, $type, $code) = TAutoloader::includeDefaultController($this->namespace, $this->className);
            }

            TRegistry::setCode($view->getUID(), $code);
        }

        return [$file, $type, $code];
    }
}
