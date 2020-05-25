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

namespace Reed\Web\UI;

use Reed\Core\IObject;
use Reed\MVC\TActionInfo;
use Reed\MVC\TCustomView;
use Reed\MVC\TModel;
use Reed\Registry\TRegistry;
use Reed\Web\TWebObject;
use Reed\TAutoloader;

class TPartialControl extends TCustomCachedControl
{
    public function __construct(IObject $parent)
    {
        parent::__construct($parent);

        $this->clonePrimitivesFrom($parent);

        $this->className = $this->getType();
        $this->setViewName($this->className);
        $this->setNames();
        $this->view = null;
        $this->getCacheFileName();

        list($file, $type, $code) = TAutoloader::includeModelByName($this->viewName);
        $model = SRC_ROOT . $file;
        if (file_exists($model)) {
            include $model;
            $modelClass = $type;

            $this->model = new $modelClass();
        }
    }
    
}
