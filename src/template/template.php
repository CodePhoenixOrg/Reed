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


/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reed\Template;

use Reed\Registry\TRegistry;

/**
 * Description of view
 *
 * @author david
 */
class TTemplate extends TCustomTemplate
{
    public function __construct(\Reed\Web\IWebObject $parent, array $dictionary)
    {
        parent::__construct($parent, $dictionary);

        $this->viewName = $parent->getViewName();

        $this->clonePrimitivesFrom($parent);
        $this->cloneNamesFrom($parent);
        $this->getCacheFileName();
        $this->cacheFileName = $parent->getCacheFileName();
        $this->fatherTemplate = $parent;
        $this->viewIsFather = true;
        $this->fatherUID = $parent->getUID();

        TRegistry::importClasses($this->getDirName());
    }
}
