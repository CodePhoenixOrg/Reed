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
 
 
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Reed\MVC;

use \Reed\Core\TRouter;
use Reed\Registry\TRegistry;

/**
 * Description of view
 *
 * @author david
 */
class TView extends TCustomView
{
    public function __construct(\Reed\Web\IWebObject $parent)
    {
        parent::__construct($parent);
        
        $this->viewName = $parent->getViewName();

        $this->clonePrimitivesFrom($parent);
        $this->cloneNamesFrom($parent);
        $this->getCacheFileName();
        $this->cacheFileName = $parent->getCacheFileName();

        // if ($this->getType() == 'TView' && $this->motherView === null) {
            $this->motherView = $this;
            $this->viewIsMother = true;
            $this->motherUID = $this->getUID();
        // }

        TRegistry::importClasses($this->getDirName());


    }

 }
