<?php
namespace Reed\Web\UI;

use FunCom\Core\Autoloader as CoreAutoloader;
use FunCom\ElementInterface;
use Reed\Core\IObject;
use Reed\Autoloader;

class PartialControl extends CustomCachedControl
{
    public function __construct(ElementInterface $parent)
    {
        parent::__construct($parent);

        $this->clonePrimitivesFrom($parent);

        $this->className = $this->getType();
        $this->setViewName($this->className);
        $this->setNames();
        $this->view = null;
        $this->getCacheFileName();

        list($file, $type, $code) = CoreAutoloader::includeModelByName($this->viewName);
        $model = SRC_ROOT . $file;
        if (file_exists($model)) {
            include $model;
            $modelClass = $type;

            $this->model = new $modelClass();
        }
    }
    
}
