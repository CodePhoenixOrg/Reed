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


namespace Reed\Registry;

use Reed\Core\TStaticObject;
use Reed\Core\TRegistry;

/**
 * Description of registry
 *
 * @author david
 */

class TClassInfo extends TStaticObject
{
    private $_type = '';
    private $_alias = '';
    private $_path = '';
    private $_namespace = '';
    private $_hasTemplate = false;
    private $_canRender = false;
    private $_isAutoloaded = false;
    private $_details = [];
    private $_isValid = false;

    public function __construct(array $info) 
    {
        $this->_type = key($info);
        $this->_details = isset($info[$this->_type]) ? $info[$this->_type] : [];

        if(count($this->_details) < 5) {
            throw new \Exception("The class info is incomplete");
        }

        $this->_alias = isset($this->_details["alias"]) ? $this->_details["alias"] : '';
        $this->_path = isset($this->_details["path"]) ? $this->_details["path"] : '';

        if(!isset($this->_details["path"])) {
            throw new \Exception("The path detail is missing");
        }

        $this->_namespace = isset($this->_details["namespace"]) ? $this->_details["namespace"] : '';

        if(!isset($this->_details["namespace"])) {
            throw new \Exception("The namespace detail is missing");
        }

        $this->_hasTemplate = isset($this->_details["hasTemplate"]) ? $this->_details["hasTemplate"] : false;

        if(!isset($this->_details["hasTemplate"])) {
            throw new \Exception("The hasTemplate detail is missing");
        }

        $this->_canRender = isset($this->_details["canRender"]) ? $this->_details["canRender"] : false;

        if(!isset($this->_details["canRender"])) {
            throw new \Exception("The canRender detail is missing");
        }

        $this->_isAutoloaded = isset($this->_details["isAutoloaded"]) ? $this->_details["isAutoloaded"] : false;

        if(!isset($this->_details["isAutoloaded"])) {
            throw new \Exception("The isAutoloaded detail is missing");
        }

        $this->_isValid = true;

    }

    public static function builder(array $info) : void 
    {
        $ci = TClassInfo::create($info);
    }

    public function getType(): string
    {
        return $this->_type;
    }
    
    public function getAlias(): string
    {
        return $this->_alias;
    }

    public function getPath(): string
    {
        return $this->_path;
    }

    public function getNamespace(): string
    {
        return $this->_namespace;
    }

    public function hasTemplate(): bool
    {
        return $this->_hasTemplate;
    }

    public function canRender(): bool
    {
        return $this->_canRender;
    }

    public function isAutoloaded(): bool
    {
        return $this->_isAutoloaded;
    }

    public function isValid() : bool 
    {
        return $this->_isValid;
    }

    public function register() {
        TRegistry::registerClass($this);
    }
}
