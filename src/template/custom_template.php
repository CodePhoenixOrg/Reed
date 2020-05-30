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

namespace Reed\Template;

use Reed\Cache\TCache;
use Reed\Core\TObject;
use Reed\Registry\TRegistry;
use Reed\Web\IWebObject;
use Reed\Web\UI\TCustomControl;
use Reed\Xml\TXmlDocument;

abstract class TCustomTemplate extends TCustomControl
{
    use \Reed\Web\UI\TCodeGenerator {
        writeDeclarations as private;
        writeHTML as private;
    }

    protected $router = null;
    protected $viewHtml = '';
    protected $twigHtml = '';
    protected $preHtml = '';
    protected $designs = array();
    protected $design = '';
    protected $creations = '';
    protected $additions = '';
    protected $afterBinding = '';
    protected $modelIsIncluded = false;
    protected $controllerIsIncluded = false;
    protected $pattern = '';
    protected $depth = 0;
    protected $viewIsFather = false;
    protected $engineIsReed = true;
    protected $engineIsTwig = false;
    protected $dictionary = [];

    function __construct(IWebObject $parent, array $dictionary)
    {
        parent::__construct($parent);

        $this->clonePrimitivesFrom($parent);

        //$this->redis = new Client($this->context->getRedis());

        $this->dictionary = $dictionary;
        $uid = $this->getUID();
        TRegistry::write('template', $uid, $dictionary);
    }

    function isFatherTemplate(): bool
    {
        return $this->viewIsFather;
    }

    function getDictionary(): ?array
    {
        return $this->dictionary;
    }

    function isReedEngine(): bool
    {
        return $this->engineIsReed;
    }

    function isTwigEngine(): bool
    {
        return $this->engineIsTwig;
    }

    function getDepth(): int
    {
        return $this->depth;
    }
    function setDepth($value): void
    {
        $this->depth = $value;
    }

    function getCreations(): string
    {
        return $this->creations;
    }

    function getAdditions(): string
    {
        return $this->additions;
    }

    function getAfterBinding(): string
    {
        return $this->afterBinding;
    }

    // public function setViewHtml($html)
    // {
    //     $this->viewHtml = $html;
    // }

    function getViewHtml(): string
    {
        return $this->viewHtml;
    }

    function setTwigHtml($html): void
    {
        $this->twigHtml = $html;
    }

    function getTwigHtml(): string
    {
        return $this->twigHtml;
    }

    function loadView($filename): string
    {
        $lines = file($filename);
        $text = '';
        foreach ($lines as $line) {
            // $text .= trim($line) . PHP_EOL;
            $text .= $line;
        }

        return $text;
    }

    function parse(): bool
    {
        while (empty($this->getViewHtml())) {
            if (file_exists(SRC_ROOT . $this->viewFileName) && !empty($this->viewFileName)) {
                self::getLogger()->debug('PARSE SRC ROOT FILE : ' . $this->viewFileName, __FILE__, __LINE__);

                $this->viewHtml = file_get_contents(SRC_ROOT . $this->viewFileName);
                continue;
            }
            if (file_exists(SITE_ROOT . $this->viewFileName) && !empty($this->viewFileName)) {
                self::getLogger()->debug('PARSE SITE ROOT FILE : ' . $this->viewFileName, __FILE__, __LINE__);

                $this->viewHtml = file_get_contents(SITE_ROOT . $this->viewFileName);
                continue;
            }

            $viewPath = SITE_ROOT . $this->getDirName() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->viewName . PREHTML_EXTENSION;
            if (file_exists($viewPath)) {
                $path = $this->getPath();
                if ($path[0] == '@') {
                    $path = str_replace("@" . DIRECTORY_SEPARATOR, SITE_ROOT, $this->getPath());
                } else {
                    $path = SITE_ROOT . $this->getPath();
                }
                self::getLogger()->debug('PARSE Reed VIEW : ' . $path, __FILE__, __LINE__);

                $this->viewHtml = file_get_contents($path);

                continue;
            }
            break;
        }
        $head = $this->getStyleSheetTag();
        $script = $this->getScriptTag();

        if ($this->isFatherTemplate()) {
            if ($head !== null) {
                TRegistry::push($this->getFatherUID(), 'head', $head);
                $this->appendToHead($head, $this->viewHtml);
            }
            if ($script !== null) {
                TRegistry::push($this->getFatherUID(), 'scripts', $script);
                $this->appendToBody($script, $this->viewHtml);
            }
        }

        $doc = new TXmlDocument($this->viewHtml);
        $doc->matchAll();

        $firstMatch = $doc->getMatchById(0);
        if ($firstMatch->getMethod() === 'extends') {

            $masterFilename = $firstMatch->properties('template');

            self::getLogger()->debug('MASTER FILE NAME::' . VIEWS_DIR . $masterFilename);

            $masterHtml = file_get_contents(VIEWS_DIR . $masterFilename);

            self::getLogger()->debug('MASTER FILE EXITS::' . (false !== $masterHtml) ? 'TRUE' : 'FALSE');

            $masterDoc = new TXmlDocument($masterHtml);
            $masterDoc->matchAll();

            $this->viewHtml = $masterDoc->replaceMatches($doc, $this->viewHtml);

            self::getLogger()->debug('BEGIN MASTER DOC LIST');
            self::getLogger()->debug($masterDoc->getList());
            self::getLogger()->debug('END MASTER DOC LIST');

            $doc = new TXmlDocument($this->viewHtml);
            $doc->matchAll();
    
            self::getLogger()->debug('BEGIN FINAL DOC LIST');
            self::getLogger()->debug($masterDoc->getList());
            self::getLogger()->debug('END FINAL DOC LIST');
        }


        if ($doc->getCount() > 0) {
            $declarations = $this->writeDeclarations($doc, $this);
            $this->creations = $declarations->creations;
            $this->additions = $declarations->additions;
            $this->afterBinding = $declarations->afterBinding;
            $this->viewHtml = $this->writeHTML($doc, $this);
        }

        TRegistry::setHtml($this->getUID(), $this->viewHtml);

        if (!TRegistry::exists('code', $this->getUID())) {
            self::getLogger()->debug('NO NEED TO WRITE CODE: ' . $this->controllerFileName, __FILE__, __LINE__);
            return false;
        }

        $code = TRegistry::getCode($this->getUID());
        // We store the parsed code in a file so that we know it's already parsed on next request.
        $code = str_replace(CREATIONS_PLACEHOLDER, $this->creations, $code);
        $code = str_replace(ADDITIONS_PLACEHOLDER, $this->additions, $code);
        if (!$this->isFatherTemplate() || $this->isClientTemplate()) {
            $code = str_replace(HTML_PLACEHOLDER, $this->viewHtml, $code);
        }
        $code = str_replace(DEFAULT_CONTROLLER, DEFAULT_CONTROL, $code);
        $code = str_replace(DEFAULT_PARTIAL_CONTROLLER, DEFAULT_PARTIAL_CONTROL, $code);
        $code = str_replace(CONTROLLER, CONTROL, $code);
        $code = str_replace(PARTIAL_CONTROLLER, PARTIAL_CONTROL, $code);
        if (!empty(trim($code))) {
            self::getLogger()->debug('SOMETHING TO CACHE : ' . $this->getCacheFileName(), __FILE__, __LINE__);
            if (!$this->isFatherTemplate()) {
                file_put_contents($this->getCacheFileName(), $code);
            }
            TRegistry::setCode($this->getUID(), $code);
        }

        $this->engineIsReed = true;
        // $this->redis->mset($this->preHtmlName, $this->declarations . $this->viewHtml);


        // We generate the code, but we don't flag it as parsed because it was not "executed"
        return false;
    }

    function safeCopy(string $filename, string $cacheFilename): bool
    {
        $ok = false;
        $src = SRC_ROOT . $filename;
        $dest = DOCUMENT_ROOT . $cacheFilename;

        if (!file_exists($src)) {
            $src = SITE_ROOT . $filename;
        }

        if (file_exists($src)) {
            $ok = file_exists($dest);
            if (!$ok) {
                $ok = copy($src, $dest);
            }
        }

        return $ok;
    }

    function getScriptTag(): ?string
    {
        $cacheJsFilename = TCache::cacheJsFilenameFromView($this->getViewName(), $this->isInternalComponent());
        $script = "<script src='" . TCache::absoluteURL($cacheJsFilename) . "'></script>" . PHP_EOL;

        $ok = $this->safeCopy($this->getJsControllerFileName(), $cacheJsFilename);

        return ($ok) ? $script : null;
    }

    function getStyleSheetTag(): ?string
    {
        $cacheCssFilename = TCache::cacheCssFilenameFromView($this->getViewName(), $this->isInternalComponent());
        $head = "<link rel='stylesheet' href='" . TCache::absoluteURL($cacheCssFilename) . "' />" . PHP_EOL;

        $ok = $this->safeCopy($this->getCssFileName(), $cacheCssFilename);

        return ($ok) ? $head : null;
    }


    function appendToBody(string $scripts, string &$viewHtml): void
    {
        if ($scripts !== '') {
            $scripts .= '</body>' . PHP_EOL;
            $viewHtml = str_replace('</body>', $scripts, $viewHtml);
        }
    }

    function appendToHead(string $head, string &$viewHtml): void
    {
        if ($head !== '') {
            $head .= '</head>' . PHP_EOL;
            $viewHtml = str_replace('</head>', $head, $viewHtml);
        }
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
    public static function includeTemplateClass(TCustomTemplate $template, $params = 0): ?array
    {
        $filename = $template->getControllerFileName();
        $classFilename = SRC_ROOT . $filename;
        if (!file_exists($classFilename)) {
            $classFilename = SITE_ROOT . $filename;
        }
        if (!file_exists($classFilename)) {
            return null;
        }

        list($namespace, $className, $code) = TObject::getClassDefinition($classFilename);

        $fqClassName = trim($namespace) . "\\" . trim($className);

        $file = str_replace('\\', '_', $fqClassName) . '.php';

        if (isset($params) && ($params && RETURN_CODE === RETURN_CODE)) {
            $code = substr(trim($code), 0, -2) . PHP_EOL . CONTROL_ADDITIONS;
            TRegistry::setCode($template->getUID(), $code);
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

    public static function controllerTemplate(string $namespace, string $className, bool $isPartial): string
    {
        $partial = ($isPartial) ? 'Partial' : '';

        $result = <<<CONTROLLER
<?php
namespace $namespace;

use Reed\MVC\T{$partial}Controller;

class $className extends T{$partial}Controller
{
    
       
}
CONTROLLER;

        return $result;
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
            $viewName = TObject::innerClassNameToFilename($className);
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
        $view = new TPartialTemplate($ctrl, $className);

        if ($info !== null) {
            list($file, $type, $code) = TCustomTemplate::includeInnerClass($view, $info);
            $view->getCacheFilename();
        } else {
            list($file, $type, $code) = TCustomTemplate::includeTemplateClass($view, RETURN_CODE);
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

    private static function includeInnerClass(TCustomTemplate $view, object $info, bool $withCode = true): array
    {
        $className = $view->getClassName();
        $viewName = $view->getViewName();

        // $filename = $info->path . 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR  . \Reed\TAutoloader::innerClassNameToFilename($className) . CLASS_EXTENSION;
        // $filename = $view->getControllerFileName();
        $filename = $info->path . 'app' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR  . $viewName . CLASS_EXTENSION;

        if ($filename[0] == '@') {
            $filename = \str_replace('@/', PHINK_APPS_ROOT, $filename);
        }
        if ($filename[0] == '~') {
            $filename = \str_replace('~/', PHINK_WIDGETS_ROOT, $filename);
        }
        //self::getLogger()->debug('INCLUDE INNER PARTIAL CONTROLLER : ' . $filename, __FILE__, __LINE__);

        $code = file_get_contents($filename, FILE_USE_INCLUDE_PATH);

        if ($withCode) {
            $code = substr(trim($code), 0, -2) . PHP_EOL . CONTROL_ADDITIONS;
            TRegistry::setCode($view->getUID(), $code);
        }

        return [$filename, $info->namespace . '\\' . $className, $code];
    }
}
