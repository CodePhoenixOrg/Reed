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
namespace Reed\Core;

use Reed\Log\TLog;

class TStaticObject
{
    protected static $logger = null;
    
    private function __construct()
    {
       
    }
    
    public static function getLogger() : TLog
    {
        if(self::$logger === null) {
            self::$logger = TLog::create();
        }
        return self::$logger;
    }

    public static function create(...$params)
    {
        $class = __CLASS__;
        $object = null;
        
        if(count($params) > 0) {
            $object = new $class();
        } else {
            $object = new $class($params);
        }
        
        return $object;
    }
}