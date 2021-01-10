<?php
namespace Reed\Web\UI;

use Reed\Core\IObject;
use Reed\Autoloader;

class Control extends CustomCachedControl
{
    public function __construct(ElementInterface $parent)
    {
        parent::__construct($parent);

        $this->view = $parent;

        $this->clonePrimitivesFrom($parent);

        $this->className = $this->getType();
        $this->viewName = $this->view->getViewName();

        $this->setNames();
        
        $this->getCacheFileName();

        list($file, $type, $code) = Autoloader::includeModelByName($this->viewName);
        $model = SRC_ROOT . $file;
        if (file_exists($model)) {
            include $model;
            $modelClass = $type;

            $this->model = new $modelClass();
        }
    }

    
}