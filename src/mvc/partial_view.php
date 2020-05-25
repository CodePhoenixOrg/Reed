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
 
 namespace Reed\MVC;

 use Reed\Core\IObject;
 use Reed\Web\IWebObject;
 use Reed\MVC\TCustomView;

class TPartialView extends TCustomView
{
    public function __construct(IWebObject $parent, ?string $className = null)
    {
        $this->className = $parent->getType();
        if($className !== null) {
            $this->className = $className;
        }
        parent::__construct($parent);

        $this->clonePrimitivesFrom($parent);

        $this->setViewName($this->className);
        $this->setNamespace();
        $this->setNames();
    }
}
