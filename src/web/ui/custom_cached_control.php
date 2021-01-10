<?php
namespace Reed\Web\UI;

use FunCom\ElementInterface;
use Reed\Cache\Cache;
use Reed\Core\IObject;
use Reed\Registry\Registry;
use Reed\Template\CustomTemplate;

/**
 * Description of custom_control
 *
 * @author David
 */
abstract class CustomCachedControl extends CustomControl
{
    protected $model = null;
    protected $innerHtml = '';
    protected $viewHtml = '';
    protected $isDeclared = false;

    public function __construct(ElementInterface $parent)
    {
        parent::__construct($parent);
    }

    public function getView(): ?CustomTemplate
    {
        return $this->view;
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
        if (Registry::exists('html', $uid)) {
            $html = Registry::getHtml($uid);
            eval('?>' . $html);
        } else {
            $this->displayHtml();
        }

        $html = ob_get_clean();
        // WebObject::register($this);

        $this->unload();

        if (file_exists(SRC_ROOT . $this->getJsControllerFileName())) {
            $cacheJsFilename = Cache::cacheJsFilenameFromView($this->viewName, $this->isInternalComponent());
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

        //WebObject::register($this);

        $this->unload();
    }

    public function perform(): void
    {
        $this->init();
        $this->createObjects();
        if ($this->isClientTemplate()) {
            $this->partialLoad();

            try {
                $actionName = $this->actionName;

                $params = $this->validate($actionName);
                $actionInfo = $this->invoke($actionName, $params);
                // if ($actionInfo instanceof TActionInfo) {
                //     $this->response->setData($actionInfo->getData());
                // }

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
                if (Registry::exists('html', $uid)) {
                    $php = Registry::getHtml($uid);
                    eval('?>' . $php); 
                } else {
                    $this->displayHtml();
                }
            } elseif ($this->view->isTwigEngine()) {
                $html = $this->view->getTwigHtml();
                echo $html;
            }

            //WebObject::register($this);

            if ($this->getParent()->isFatherTemplate()) {
                Registry::dump($this->getUID());
            }

            $this->unload();
        }
    }

    public function __destruct()
    {
    }
}
