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

namespace Reed\Web;

/**
 * Description of TWebObject
 *
 * @author david
 */

use Reed\Cache\TCache;
use Reed\Core\TObject;
use Reed\Registry\TRegistry;
use Reed\Template\ETemplateType;
use Reed\Template\TCustomTemplate;

trait TWebObject
{
    use THttpTransport;

    private static $_pageNumber;
    private static $_pageCount;
    protected $redis = null;
    protected $modelFileName = '';
    protected $viewFileName = '';
    protected $controllerFileName = '';
    protected $jsControllerFileName = '';
    protected $cssFileName = '';
    protected $cacheFileName = null;
    protected $jsCacheFileName = null;
    protected $cssCacheFileName = null;
    protected $preHtmlName = '';
    protected $viewName = '';
    protected $actionName = '';
    protected $className = '';
    protected $dirName = '';
    protected $bootDirName = '';
    protected $namespace = '';
    protected $code = '';
    protected $parameters = [];
    protected $commands = [];
    protected $application = null;
    protected $path = '';
    protected $reedEngine = null;
    protected $parentView = null;
    protected $parentType = null;
    protected $fatherTemplate = null;
    protected $fatherUID = '';
    protected $templatePath = '';
    protected $templateType = ETemplateType::NON_PHINK_TEMPLATE;
    protected $componentIsInternal = false;
    protected $templateIsClient = false;
    protected $isPartial = false;

    public function appendJsToBody(string $viewName): void
    {
        $lock = RUNTIME_DIR . $viewName . '.lock';

        if (file_exists($lock)) {
            return;
        }

        $script = $this->getJsCacheFileName($viewName);

        if (!$this->isClientTemplate()) {
            $view = $this->getFatherTemplate();
            $uid = $view->getUID();

            if (!TRegistry::exists('html', $uid)) {
                return;
            }

            $scriptURI = TCache::absoluteURL($script);
            $jscall = <<<JSCRIPT
            <script type='text/javascript' src='{$scriptURI}'></script>
JSCRIPT;

            $html = TRegistry::getHtml($uid);

            if ($jscall !== null) {
                $view->appendToBody($jscall, $html);
                TRegistry::setHtml($uid, $html);
                file_put_contents($lock, date('Y-m-d h:i:s'));
            }
        }
        if ($this->isClientTemplate()) {
            $this->response->addScript($script);
        }
    }

    public function getCacheFileName(?string $viewName = null): string
    {
        if ($this->cacheFileName === null) {
            if ($viewName === null) {
                $viewName = $this->viewName;
            }
            $this->cacheFileName = SRC_ROOT . TCache::cacheFilenameFromView($this->viewName, $this->isInternalComponent());
        }
        return $this->cacheFileName;
    }

    public function getJsCacheFileName(?string $viewName = null): string
    {
        if ($this->jsCacheFileName === null) {
            if ($viewName === null) {
                $viewName = $this->viewName;
            }
            $this->jsCacheFileName = TCache::cacheJsFilenameFromView($viewName, $this->isInternalComponent());
        }
        return $this->jsCacheFileName;
    }

    public function getCssCacheFileName(?string $viewName = null): string
    {
        if ($this->cssCacheFileName === null) {
            if ($viewName === null) {
                $viewName = $this->viewName;
            }
            $this->cssCacheFileName = TCache::cacheCssFilenameFromView($viewName, $this->isInternalComponent());
        }
        return $this->cssCacheFileName;
    }

    public function getFatherTemplate(): ?TCustomTemplate
    {
        return $this->fatherTemplate;
    }

    public function getFatherUID(): string
    {
        return $this->fatherUID;
    }

    public function getParentType()
    {
        return $this->parentType;
    }

    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    public function getTemplateType()
    {
        return $this->templateType;
    }

    public function isClientTemplate()
    {
        return $this->templateIsClient;
    }

    public function isPartialTemplate()
    {
        return $this->isPartial;
    }

    public function isInnerTemplate()
    {
        return $this->componentIsInternal;
    }

    public function setRedis(array $params): void
    {
        if (class_exists('Redis')) {

            // $this->redis = new Redis($params);
            $this->redis = null;
        }
    }

    public function getRedis(): ?object
    {
        return $this->redis;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getDirName(): string
    {
        return $this->dirName;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getActionName(): string
    {
        return $this->actionName;
    }

    public function getFileNamespace(): string
    {
        return $this->namespace;
    }

    public function getRawPhpName(): string
    {
        return $this->cacheFileName;
    }

    public function getModelFileName(): string
    {
        return $this->modelFileName;
    }

    public function getViewFileName(): string
    {
        return $this->viewFileName;
    }

    public function getControllerFileName(): string
    {
        return $this->controllerFileName;
    }

    public function getJsControllerFileName(): string
    {
        return $this->jsControllerFileName;
    }

    public function getCssFileName(): string
    {
        return $this->cssFileName;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getQueryParameters(string $param = null)
    {
        if (!isset($this->parameters[$param])) {
            return false;
        }

        $value = $this->parameters[$param];

        return $this->filterParameter($value);
    }

    public function filterParameter($param)
    {
        $result = filter_var($param, FILTER_SANITIZE_ENCODED);
        $result = html_entity_decode($result, ENT_QUOTES);

        return $result;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getViewName(): string
    {
        return $this->viewName;
    }

    public function setViewName(string $className = ''): void
    {
        $dummy = 0;
        if (!empty($className)) {

            $info = TRegistry::classInfo($className);
            if ($info !== null) {
                $this->viewName = TCustomTemplate::innerClassNameToFilename($className);
            }
            if ($info === null) {
                $this->viewName = TCustomTemplate::userClassNameToFilename($className);
            }
            return;
        }

        if (empty($className)) {
            $requestUriParts = explode('/', REQUEST_URI);
            $this->viewName = array_pop($requestUriParts);
            $viewNameParts = explode('.', $this->viewName);
            $this->viewName = array_shift($viewNameParts);
        }
    }

    public function getReedEnginge()
    {
        return $this->reedEngine;
    }

    public function renderTemplate(array $dictionary = []): string
    {
        $result = '';

        if ($this->getReedEnginge() !== null) {
            $result = $this->getReedEnginge()->render($this->getViewName() . PREHTML_EXTENSION, $dictionary);
        }

        return $result;
    }

    public function renderTemplateByName(string $viewName, array $dictionary = []): string
    {
        $result = '';

        if ($this->getReedEnginge() !== null) {
            $result = $this->getReedEnginge()->render($viewName, $dictionary);
        }

        return $result;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(): void
    {
        $this->namespace = $this->getFileNamespace();

        if (!isset($this->namespace)) {
            $this->namespace = TObject::getDefaultNamespace();
        }
    }

    public function isInternalComponent(): bool
    {
        return $this->componentIsInternal;
    }

    public function setNames(?string $typeName = null): void
    {
        if ($typeName !== null) {
            $this->setViewName($typeName);
        }

        if ($typeName === null) {
            $this->actionName = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';
        }
        $this->modelFileName = 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $this->viewName . CLASS_EXTENSION;
        $this->viewFileName = 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->viewName . PREHTML_EXTENSION;
        $this->cssFileName = 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->viewName . CSS_EXTENSION;
        $this->controllerFileName = 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $this->viewName . CLASS_EXTENSION;
        $this->jsControllerFileName = 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $this->viewName . JS_EXTENSION;
        if ($this->isInternalComponent()) {
            $dirName = $this->getDirName();
            $this->modelFileName = $dirName . DIRECTORY_SEPARATOR . $this->modelFileName;
            $this->viewFileName = $dirName . DIRECTORY_SEPARATOR . $this->viewFileName;
            $this->cssFileName = $dirName . DIRECTORY_SEPARATOR . $this->cssFileName;
            $this->controllerFileName = $dirName . DIRECTORY_SEPARATOR . $this->controllerFileName;
            $this->jsControllerFileName = $dirName . DIRECTORY_SEPARATOR . $this->jsControllerFileName;
        }

        if (!file_exists(SITE_ROOT . $this->viewFileName) && !file_exists(SRC_ROOT . $this->viewFileName)) {
            $info = TRegistry::classInfo($this->className);
            if ($info !== null) {
                // $this->viewName = \Reed\TCustomTemplate::classNameToFilename($this->className);
                if ($info->path[0] == '@') {
                    $path = str_replace("@" . DIRECTORY_SEPARATOR, PHINK_VENDOR_APPS, $info->path) . 'app' . DIRECTORY_SEPARATOR;
                    $this->controllerFileName = $path . 'controllers' . DIRECTORY_SEPARATOR . $this->viewName . CLASS_EXTENSION;
                    $this->jsControllerFileName = $path . 'controllers' . DIRECTORY_SEPARATOR . $this->viewName . JS_EXTENSION;
                    $this->cssFileName = $path . 'views' . DIRECTORY_SEPARATOR . $this->viewName . CSS_EXTENSION;
                    $this->viewFileName = $path . 'views' . DIRECTORY_SEPARATOR . $this->viewName . PREHTML_EXTENSION;
                } else if ($info->path[0] == '~') {
                    $path = str_replace("~" . DIRECTORY_SEPARATOR, PHINK_VENDOR_WIDGETS, $info->path) . DIRECTORY_SEPARATOR;
                    $this->controllerFileName = $path . 'controllers' . DIRECTORY_SEPARATOR . $this->viewName . CLASS_EXTENSION;
                    $this->jsControllerFileName = $path . 'controllers' . DIRECTORY_SEPARATOR . $this->viewName . JS_EXTENSION;
                    $this->cssFileName = $path . 'views' . DIRECTORY_SEPARATOR . $this->viewName . CSS_EXTENSION;
                    $this->viewFileName = $path . 'views' . DIRECTORY_SEPARATOR . $this->viewName . PREHTML_EXTENSION;
                } else {
                    $this->viewName = \Reed\TCustomTemplate::innerClassNameToFilename($this->className);

                    $path = PHINK_VENDOR_LIB . $info->path;
                    $this->controllerFileName = $path . $this->viewName . CLASS_EXTENSION;
                    $this->jsControllerFileName = $path . $this->viewName . JS_EXTENSION;
                    $this->cssFileName = $path . $this->viewName . CSS_EXTENSION;
                    $this->viewFileName = $path . $this->viewName . PREHTML_EXTENSION;
                }
                // $path = $info->path;
                if (!$info->hasTemplate) {
                    $this->viewFileName = '';
                }

                $this->className = $info->namespace . '\\' . $this->className;
            }
        }

        $this->getCacheFileName();
    }

    public function getMvcFileNamesByViewName(string $viewName): ?array 
    {
        $modelFileName = 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $viewName . CLASS_EXTENSION;
        $viewFileName = 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $viewName . PREHTML_EXTENSION;
        $cssFileName = 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $viewName . CSS_EXTENSION;
        $controllerFileName = 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $viewName . CLASS_EXTENSION;
        $jsControllerFileName = 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $viewName . JS_EXTENSION;
        if ($this->isInternalComponent()) {
            $dirName = $this->getDirName();
            $modelFileName = $dirName . DIRECTORY_SEPARATOR . $modelFileName;
            $viewFileName = $dirName . DIRECTORY_SEPARATOR . $viewFileName;
            $cssFileName = $dirName . DIRECTORY_SEPARATOR . $cssFileName;
            $controllerFileName = $dirName . DIRECTORY_SEPARATOR . $controllerFileName;
            $jsControllerFileName = $dirName . DIRECTORY_SEPARATOR . $jsControllerFileName;
        }

        $cacheFileName = SRC_ROOT . TCache::cacheFilenameFromView($viewName);

        return [
            'modelFileName' => $modelFileName,
            'viewFileName' => $viewFileName,
            'controllerFileName' => $controllerFileName,
            'jsControllerFileName' => $jsControllerFileName,
            'cssFileName' => $cssFileName,
            'cacheFileName' => $cacheFileName,
        ];
    }

    public function getMvcFileNamesByTypeName(?string $typeName = null): ?array
    {
        $result = [];

        $info = TRegistry::classInfo($typeName);
        if ($info !== null) {
            $viewName = TCustomTemplate::innerClassNameToFilename($typeName);
        }
        if ($info === null) {
            $viewName = TCustomTemplate::userClassNameToFilename($typeName);
        }

        if ($typeName === null) {
            $actionName = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';
        }
        $modelFileName = 'app' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $viewName . CLASS_EXTENSION;
        $viewFileName = 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $viewName . PREHTML_EXTENSION;
        $cssFileName = 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $viewName . CSS_EXTENSION;
        $controllerFileName = 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $viewName . CLASS_EXTENSION;
        $jsControllerFileName = 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $viewName . JS_EXTENSION;
        if ($this->isInternalComponent()) {
            $dirName = $this->getDirName();
            $modelFileName = $dirName . DIRECTORY_SEPARATOR . $modelFileName;
            $viewFileName = $dirName . DIRECTORY_SEPARATOR . $viewFileName;
            $cssFileName = $dirName . DIRECTORY_SEPARATOR . $cssFileName;
            $controllerFileName = $dirName . DIRECTORY_SEPARATOR . $controllerFileName;
            $jsControllerFileName = $dirName . DIRECTORY_SEPARATOR . $jsControllerFileName;
        }

        if (!file_exists(SITE_ROOT . $viewFileName) && !file_exists(SRC_ROOT . $viewFileName)) {
            $info = TRegistry::classInfo($typeName);
            if ($info !== null) {
                $viewName = \Reed\TCustomTemplate::innerClassNameToFilename($typeName);

                if ($info->path[0] == '@') {
                    $path = str_replace("@" . DIRECTORY_SEPARATOR, PHINK_VENDOR_APPS, $info->path) . 'app' . DIRECTORY_SEPARATOR;
                    $controllerFileName = $path . 'controllers' . DIRECTORY_SEPARATOR . $viewName . CLASS_EXTENSION;
                    $jsControllerFileName = $path . 'controllers' . DIRECTORY_SEPARATOR . $viewName . JS_EXTENSION;
                    $cssFileName = $path . 'views' . DIRECTORY_SEPARATOR . $viewName . CSS_EXTENSION;
                    $viewFileName = $path . 'views' . DIRECTORY_SEPARATOR . $viewName . PREHTML_EXTENSION;
                } else {

                    $path = PHINK_VENDOR_LIB . $info->path;
                    $controllerFileName = $path . $viewName . CLASS_EXTENSION;
                    $jsControllerFileName = $path . $viewName . JS_EXTENSION;
                    $cssFileName = $path . $viewName . CSS_EXTENSION;
                    $viewFileName = $path . $viewName . PREHTML_EXTENSION;
                }
                // $path = $info->path;
                if (!$info->hasTemplate) {
                    $viewFileName = '';
                }

                $typeName = $info->namespace . '\\' . $typeName;
            }
        }

        $cacheFileName = SRC_ROOT . TCache::cacheFilenameFromView($viewName);

        return [
            'modelFileName' => $modelFileName,
            'viewFileName' => $viewFileName,
            'controllerFileName' => $controllerFileName,
            'jsControllerFileName' => $jsControllerFileName,
            'cssFileName' => $cssFileName,
            'cacheFileName' => $cacheFileName,
        ];
    }

    public function cloneNamesFrom($parent): void
    {
        // $this->className = $parent->getClassName();
        $this->actionName = $parent->getActionName();
        $this->viewFileName = $parent->getViewFileName();
        $this->cssFileName = $parent->getCssFileName();
        $this->controllerFileName = $parent->getControllerFileName();
        $this->jsControllerFileName = $parent->getJsControllerFileName();
        $this->namespace = $parent->getNamespace();
    }

    public function clonePrimitivesFrom($parent)
    {
        $this->path = $parent->getPath();
        $this->dirName = $parent->getDirName();

        $this->parameters = $parent->getParameters();
        $this->componentIsInternal = $parent->isInternalComponent();

    }
}
