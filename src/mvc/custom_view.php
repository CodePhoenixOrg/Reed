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

namespace Phink\MVC;

use Phink\Cache\TCache;
use Phink\Registry\TRegistry;
use Phink\TAutoloader;
use Phink\Web\IWebObject;
use Phink\Web\UI\TCustomControl;
use Phink\Xml\TXmlDocument;

abstract class TCustomView extends TCustomControl
{
    use \Phink\Web\UI\TCodeGenerator {
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
    protected $viewIsMother = false;
    protected $engineIsReed = true;
    protected $engineIsTwig = false;

    function __construct(IWebObject $parent)
    {
        parent::__construct($parent);

        $this->clonePrimitivesFrom($parent);

        //$this->redis = new Client($this->context->getRedis());
    }
    
    function isMotherView(): bool
    {
        return $this->viewIsMother;
    }

    function isDirty(): bool
    {
        return $this->_dirty;
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

    function setTwig(array $dictionary): void
    {
        $viewName = $this->getViewName() . PREHTML_EXTENSION;
        $this->setTwigByName($viewName, $dictionary);
    }

    function setTwigByName(string $viewName, array $dictionary): void
    {
        $html = $this->renderTwigByName($viewName, $dictionary);
        $this->twigHtml = $html;

        $this->engineIsTwig = true;
        $this->engineIsReed = false;
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
        // self::$logger->debug($this->viewName . ' IS REGISTERED : ' . (TRegistry::exists('code', $this->controllerFileName) ? 'TRUE' : 'FALSE'), __FILE__, __LINE__);

        // $this->viewHtml = $this->redis->mget($templateName);
        // $this->viewHtml = $this->viewHtml[0];

        while (empty($this->getViewHtml())) {
            if (file_exists(SRC_ROOT . $this->viewFileName) && !empty($this->viewFileName)) {
                self::$logger->debug('PARSE SRC ROOT FILE : ' . $this->viewFileName, __FILE__, __LINE__);

                $this->viewHtml = file_get_contents(SRC_ROOT . $this->viewFileName);
                continue;
            }
            if (file_exists(SITE_ROOT . $this->viewFileName) && !empty($this->viewFileName)) {
                self::$logger->debug('PARSE SITE ROOT FILE : ' . $this->viewFileName, __FILE__, __LINE__);

                $this->viewHtml = file_get_contents(SITE_ROOT . $this->viewFileName);
                continue;
            }

            $viewPath = SITE_ROOT . $this->getDirName() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $this->viewName . PREHTML_EXTENSION;
            // if (file_exists(SITE_ROOT . $this->getPath()) && !empty($this->getPath() && empty($this->viewHtml))) {
            // if (file_exists(SITE_ROOT . $this->getPath()) && !empty($this->getPath())) {
            if (file_exists($viewPath)) {
                $path = $this->getPath();
                if ($path[0] == '@') {
                    $path = str_replace("@" . DIRECTORY_SEPARATOR, SITE_ROOT, $this->getPath());
                } else {
                    $path = SITE_ROOT . $this->getPath();
                }
                self::$logger->debug('PARSE PHINK VIEW : ' . $path, __FILE__, __LINE__);

                $this->viewHtml = file_get_contents($path);

                continue;
            }
            break;
        }
        // else {
        //     self::$logger->debug('PARSE PHINK PLUGIN : ' . $this->getPath(), __FILE__, __LINE__);

        //     $this->viewHtml = file_get_contents(SITE_ROOT . $this->viewFileName, FILE_USE_INCLUDE_PATH);
        // }
        $head = $this->getStyleSheetTag();
        $script = $this->getScriptTag();

        if ($this->isMotherView()) {
            if ($head !== null) {
                TRegistry::push($this->getMotherUID(), 'head', $head);
                $this->appendToHead($head, $this->viewHtml);
            }
            if ($script !== null) {
                TRegistry::push($this->getMotherUID(), 'scripts', $script);
                $this->appendToBody($script, $this->viewHtml);
            }
        }

        // if (!$this->isMotherView() && !$this->getRequest()->isAJAX()) {
        //     $view = $this->getMotherView();
        //     $uid = $view->getUID();

        //     if (TRegistry::htmlExists($uid)) {
        //         $html = TRegistry::getHtml($uid);

        //         if ($head !== null) {
        //             $this->appendToHead($head, $html);
        //         }
        //         if ($script !== null) {
        //             $this->appendToBody($script, $html);
        //         }
        //         TRegistry::setHtml($view->getUID(), $html);
        //     }
        // }

        // $this->redis->mset($templateName, $this->viewHtml);
        // self::$logger->debug('HTML VIEW : [' . substr($this->viewHtml, 0, (strlen($this->viewHtml) > 25) ? 25 : strlen($this->viewHtml)) . '...]');
        // self::$logger->debug('HTML VIEW : <pre>[' . PHP_EOL . htmlentities($this->viewHtml) . PHP_EOL . '...]</pre>');
        $doc = new TXmlDocument($this->viewHtml);
        $doc->matchAll();

        // $matches = $doc->getList();

        // foreach($matches as $match) {
        //     self::$logger->debug(print_r($match, true) . PHP_EOL);
        // }

        if ($doc->getCount() > 0) {
            $declarations = $this->writeDeclarations($doc, $this);
            $this->creations = $declarations->creations;
            $this->additions = $declarations->additions;
            $this->afterBinding = $declarations->afterBinding;
            $this->viewHtml = $this->writeHTML($doc, $this);
        }

        TRegistry::setHtml($this->getUID(), $this->viewHtml);

        if (!TRegistry::exists('code', $this->getUID())) {
            self::$logger->debug('NO NEED TO WRITE CODE: ' . $this->controllerFileName, __FILE__, __LINE__);
            return false;
        }

        $code = TRegistry::getCode($this->getUID());
        // We store the parsed code in a file so that we know it's already parsed on next request.
        $code = str_replace(CREATIONS_PLACEHOLDER, $this->creations, $code);
        $code = str_replace(ADDITIONS_PLACEHOLDER, $this->additions, $code);
        if (!$this->isMotherView() || $this->getRequest()->isAJAX()) {
            $code = str_replace(HTML_PLACEHOLDER, $this->viewHtml, $code);
        }
        $code = str_replace(DEFAULT_CONTROLLER, DEFAULT_CONTROL, $code);
        $code = str_replace(DEFAULT_PARTIAL_CONTROLLER, DEFAULT_PARTIAL_CONTROL, $code);
        $code = str_replace(CONTROLLER, CONTROL, $code);
        $code = str_replace(PARTIAL_CONTROLLER, PARTIAL_CONTROL, $code);
        if (!empty(trim($code))) {
            self::$logger->debug('SOMETHING TO CACHE : ' . $this->getCacheFileName(), __FILE__, __LINE__);
            if (!$this->isMotherView()) {
                file_put_contents($this->getCacheFileName(), $code);
            }
            TRegistry::setCode($this->getUID(), $code);
        }

        $this->engineIsReed = true;
        // $this->redis->mset($this->preHtmlName, $this->declarations . $this->viewHtml);

        self::register($this);

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
                self::getLogger()->debug("copy(" . $src . ", " . $dest . ")");
            }
        }

        return $ok;
    }

    function getScriptTag(): ?string
    {
        $cacheJsFilename = TCache::cacheJsFilenameFromView($this->getViewName(), $this->isInternalComponent());
        $script = "<script src='" . TAutoloader::absoluteURL($cacheJsFilename) . "'></script>" . PHP_EOL;

        $ok = $this->safeCopy($this->getJsControllerFileName(), $cacheJsFilename);

        return ($ok) ? $script : null;
    }

    function getStyleSheetTag(): ?string
    {
        $cacheCssFilename = TCache::cacheCssFilenameFromView($this->getViewName(), $this->isInternalComponent());
        $head = "<link rel='stylesheet' href='" . TAutoloader::absoluteURL($cacheCssFilename) . "' />" . PHP_EOL;

        $ok = $this->safeCopy($this->getCssFileName(), $cacheCssFilename);

        return ($ok) ? $head : null;
    }


    function appendToBody(string $scripts, string &$viewHtml): void
    {
        if ($scripts !== '') {
            $scripts .= '</body>' . PHP_EOL;
            $viewHtml = str_replace('</body>', $scripts, $viewHtml);
            // TRegistry::write($this->getMotherUID(), 'scripts', $scripts);c
            // $motherView->addScriptTag($scripts);
        }
    }

    function appendToHead(string $head, string &$viewHtml): void
    {
        if ($head !== '') {
            $head .= '</head>' . PHP_EOL;
            $viewHtml = str_replace('</head>', $head, $viewHtml);
            // TRegistry::write($this->getMotherUID(), 'linkRel', $head);
            // $motherView->addLinkRelTag($head);
        }
    }

    function register(IWebObject $object): void
    {
        TRegistry::write(
            $object->getUID(),
            [
                "id" => $object->getId(),
                "name" => $object->getViewName(),
                "UID" => $object->getUID(),
                "parentUID" => ($object->getParent() !== null) ? $object->getParent()->getUID() : '',
                "isMotherView" => ($object->isMotherView()) ? "true" : "false",
                "view" => $object->getViewFileName(),
                "controller" => $object->getControllerFileName(),
                "css" => $object->getCssFileName(),
                "js" => $object->getJsControllerFileName(),
                "cache" =>
                [
                    "controller" => SRC_ROOT . TCache::cacheFilenameFromView($object->getViewName(), $this->isInternalComponent()),
                    "css" => SRC_ROOT . TCache::cacheCssFilenameFromView($object->getViewName(), $this->isInternalComponent()),
                    "js" => SRC_ROOT . TCache::cacheJsFilenameFromView($object->getViewName(), $this->isInternalComponent()),
                ],
            ]
        );
    }
}
