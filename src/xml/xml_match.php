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

namespace Phink\Xml;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

use Phink\Core\TObject;

/**
 * Description of match
 *
 * @author david
 */
class TXmlMatch extends TObject
{
    //put your code here

    private $_parentId = 0;
    private $_name = '';
    private $_text = '';
    private $_start = 0;
    private $_end = 0;
    private $_depth = 0;
    private $_tmpText = '';
    private $_childName = '';
    private $_hasChildren = false;
    private $_closer = '';
    private $_properties = array();
    private $_method = '';

    //$text, $groups, $position, $start, $end, $childName, $closer
    public function __construct(array $array)
    {
        $this->id = $array['id'];
        $this->_parentId = $array['parentId'];
        $this->_text = $array['element'];
        $this->_tmpText = $this->_text;
        $this->_name = $array['name'];
        $this->_start = $array['startsAt'];
        $this->_end = $array['endsAt'];
        $this->_depth = $array['depth'];
        $this->_closer = (isset($array['closer'])) ? $array['closer'] : NULL;
        $this->_childName = $array['childName'];
        $this->_properties = $array['properties'];
        $this->_method = $array['method'];

        $this->_hasChildren = isset($this->_closer);
        if ($this->_hasChildren) {
            $this->_end = $this->_closer['endsAt'];
        }
    }



    public function getParentId(): int
    {
        return $this->_parentId;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function getText(): string
    {
        return $this->_text;
    }

    public function getDepth(): int
    {
        return $this->_depth;
    }

    public function properties($key)
    {
        $result = false;
        if (isset($this->_properties[$key])) {
            $result = $this->_properties[$key];
        }
        return $result;
    }

    public function getStart(): int
    {
        return $this->_start;
    }

    public function getEnd(): int
    {
        return $this->_end;
    }

    public function getChildName(): stirng
    {
        return $this->_childName;
    }

    public function hasChildren(): bool
    {
        return $this->_hasChildren;
    }

    public function getCloser(): array
    {
        return $this->_closer;
    }

    public function getMethod(): string
    {
        return $this->_method;
    }
}
