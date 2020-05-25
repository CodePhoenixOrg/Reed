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

namespace Phink\Web\UI;

use Phink\Cache\TCache;
use Phink\Core\IObject;
use Phink\MVC\TActionInfo;
use Phink\MVC\TCustomView;
use Phink\MVC\TModel;
use Phink\Registry\TRegistry;
use Phink\Web\TWebObject;
use Phink\TAutoloader;

/**
 * Description of custom_control
 *
 * @author David
 */
abstract class TCustomCachedControl extends TCustomControl
{
    protected $model = null;
    protected $innerHtml = '';
    protected $viewHtml = '';
    protected $isDeclared = false;

    public function __construct(IObject $parent)
    {
        parent::__construct($parent);
    }

    public function getView(): ?TCustomView
    {
        return $this->view;
    }

    public function getModel(): TModel
    {
        return $this->model;
    }

    public function getInnerHtml(): string
    {
        return $this->innerHtml;
    }

    public function renderView(): void
    {
        // include "data://text/plain;base64," . base64_encode($this->viewHtml);
        eval('?>' . $this->viewHtml . '<?php ');
    }

    public function createObjects(): void
    {
    }

    public function declareObjects(): void
    {
    }

    public function afterBinding(): void
    {
    }

    public function displayHtml(): void
    {
    }

    public function getViewHtml(): void
    {
        ob_start();
        if (!$this->isDeclared) {
            //$this->createObjects();
            $this->declareObjects();
            //            $this->partialLoad();
        }
        $uid = ($this->getView() !== null) ? $this->getView()->getUID() : '';
        if (TRegistry::exists('html', $uid)) {
            $html = TRegistry::getHtml($uid);
            eval('?>' . $html);
        } else {
            $this->displayHtml();
        }

        $html = ob_get_clean();
        // TWebObject::register($this);

        $this->unload();

        if (file_exists(SRC_ROOT . $this->getJsControllerFileName())) {
            $cacheJsFilename = TCache::cacheJsFilenameFromView($this->viewName, $this->isInternalComponent());
            if (!file_exists(DOCUMENT_ROOT . $cacheJsFilename)) {
                copy(SRC_ROOT . $this->getJsControllerFileName(), DOCUMENT_ROOT . $cacheJsFilename);
            }
            $this->response->addScriptFirst($cacheJsFilename);
        }
        $this->response->setData('view', $html);
    }

    public function render(): void
    {
        $this->init();
        $this->createObjects();
        $this->beforeBinding();
        $this->declareObjects();
        $this->afterBinding();
        $this->isDeclared = true;

        $this->displayHtml();

        $this->renderHtml();

        //TWebObject::register($this);

        $this->unload();
    }

    public function perform(): void
    {
        $this->init();
        $this->createObjects();
        if ($this->getRequest()->isAJAX()) {
            $this->partialLoad();

            try {
                $actionName = $this->actionName;

                $params = $this->validate($actionName);
                $actionInfo = $this->invoke($actionName, $params);
                if ($actionInfo instanceof TActionInfo) {
                    $this->response->setData($actionInfo->getData());
                }

                $this->beforeBinding();
                $this->declareObjects();
                $this->afterBinding();

                if (
                    $this->request->isPartialView()
                    || ($this->request->isView() && $actionName !== 'getViewHtml')
                ) {
                    $this->getViewHtml();
                }
            } catch (\BadMethodCallException $ex) {
                $this->response->setData('error', $ex->getMessage());
            }

            $this->response->sendData();
        } else {
            $this->load();
            $this->beforeBinding();
            $this->declareObjects();
            $this->afterBinding();

            if ($this->view->isReedEngine()) {
                $uid = $this->getView()->getUID();
                if (TRegistry::exists('html', $uid)) {
                    $php = TRegistry::getHtml($uid);
                    eval('?>' . $php); 
                } else {
                    $this->displayHtml();
                }
            } elseif ($this->view->isTwigEngine()) {
                $html = $this->view->getTwigHtml();
                echo $html;
            }

            //TWebObject::register($this);

            if ($this->getParent()->isMotherView()) {
                TRegistry::dump($this->getUID());
            }

            $this->unload();
        }
    }

    public function __destruct()
    {
        unset($this->model);
    }
}
