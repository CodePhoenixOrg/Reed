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

namespace Reed\Core;

use \ReflectionClass;
use DateTime;
use Reed\Registry\TRegistry;

interface IObject
{
    function getUID(): string;
    function getId(): string;
    function getParent(): ?IObject;
    // function setParent(IObject $parent) : void;
    function getType(): string;
}
/**
 * Description of TObject
 *
 * @author david
 */

class TObject extends TStaticObject implements IObject
{
    private $_reflection = null;
    protected $parent = null;
    protected $uid = '';
    protected $id = 'noname';
    protected $serialFilename = '';
    protected $isSerialized = '';
    protected $children = [];
    protected $fqClassName = '';
    protected static $instance = null;

    public function __construct(IObject $parent = null)
    {
        $this->parent = $parent;
        $this->uid = uniqid(rand(), true);
    }

    public function getUID(): string
    {
        if ($this->uid == '') {
            $this->uid = uniqid('', true);
        }
        return $this->uid;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId($value): void
    {
        //TObject::$logger->dump(__CLASS__ . ':' . __METHOD__, $value);
        $this->id = $value;
    }

    public function isAwake(): bool
    {
        return $this->isSerialized;
    }

    public function getReflection(): ?ReflectionClass
    {
        if ($this->_reflection == NULL) {
            $this->_reflection = new ReflectionClass(get_class($this));
        }
        return $this->_reflection;
    }

    public function getMethodParameters($method): ?array
    {
        $ref = $this->getReflection();
        $met = $ref->getMethod($method);

        $params = [];
        foreach ($met->getParameters() as $currentParam) {
            array_push($params, $currentParam->name);
        }

        return $params;
    }


    public function getParent(): ?IObject
    {
        return $this->parent;
    }

    public function addChild(IObject $child)
    {
        $this->children[$child->getId()] = $child;
    }

    public function removeChild(IObject $child): void
    {
        unset($this->children[$child->getId()]);
    }

    public function getChildById($id): ?object
    {
        $result = null;

        if (array_key_exists($id, $this->children)) {
            $result = $this->children[$id];
        }

        return $result;
    }

    public function getChildrenIds(): ?array
    {
        return array_keys($this->children);
    }

    public function getFullType(): string
    {
        return get_class($this);
    }

    public function getNamespace(): string
    {
        $typeParts = explode('\\', $this->getFQClassName());
        array_pop($typeParts);
        return (count($typeParts) > 0) ? implode('\\', $typeParts) : '';
    }

    public function getFQClassName(): string
    {
        if ($this->fqClassName == '') {
            $this->fqClassName = get_class($this);
        }
        return $this->fqClassName;
    }

    public function getType(): string
    {
        $typeParts = explode('\\', $this->getFQClassName());
        return array_pop($typeParts);
    }

    public function getBaseType(): string
    {
        return get_parent_class($this);
    }

    public function getFileName(): string
    {
        $reflection = $this->getReflection();
        return $reflection->getFileName();
    }


    public static function arraysToObjects(array $value): array
    {
        $result = array();

        foreach ($value as $array) {
            array_push($result, (object) $array);
        }

        return $result;
    }

    
    public static function userClassNameToFilename($className): string
    {
        $translated = '';
        $l = strlen($className);
        for ($i = 0; $i < $l; $i++) {
            if (ctype_upper($className[$i])) {
                $translated .= '_' . strtolower($className[$i]);
            } else {
                $translated .= $className[$i];
            }
        }

        $translated = substr($translated, 1);

        return $translated;
    }

    public static function innerClassNameToFilename($className): string
    {
        $translated = '';
        $className = substr($className, 1);
        $l = strlen($className);
        for ($i = 0; $i < $l; $i++) {
            if (ctype_upper($className[$i])) {
                $translated .= '_' . strtolower($className[$i]);
            } else {
                $translated .= $className[$i];
            }
        }

        $translated = substr($translated, 1);

        return $translated;
    }
    public static function getClassDefinition(string $filename): array
    {
        $classText = file_get_contents($filename);

        $namespace = TObject::grabKeywordName('namespace', $classText, ';');
        $className = TObject::grabKeywordName('class', $classText, ' ');

        return [$namespace, $className, $classText];
    }

    public static function grabKeywordName(string $keyword, string $classText, $delimiter): string
    {
        $result = '';

        $start = strpos($classText, $keyword);
        if ($start > -1) {
            $start += \strlen($keyword) + 1;
            $end = strpos($classText, $delimiter, $start);
            $result = substr($classText, $start, $end - $start);
        }

        return $result;
    }

    public static function getDefaultNamespace(): string
    {
        $sa = explode('.', SERVER_NAME);
        array_pop($sa);
        if (count($sa) == 2) {
            array_shift($sa);
        }

        return ucfirst(str_replace('-', '_', $sa[0])) . '\\Controllers';
    }


     /**
     * Load the controller file, parse it in search of namespace and classname.
     * Alternatively execute the code if the class is not already declared
     *
     * @param string $filename The controller filename
     * @param int $params The bitwise constants values that determine the behavior
     *                    INCLUDE_FILE : execute the code
     *                    RETURN_CODE : ...
     * @return boolean
     */
    public static function includeClass(string $filename, $params = 0): ?array
    {
        $classFilename = SRC_ROOT . $filename;
        if (!file_exists($classFilename)) {
            $classFilename = SITE_ROOT . $filename;
        }
        if (!file_exists($classFilename)) {
            return null;
        }

        list($namespace, $className, $code) = TObject::getClassDefinition($classFilename);

        $fqClassName = trim($namespace) . "\\" . trim($className);

        $file = str_replace('\\', '_', $fqClassName) . '.php';

        if (isset($params) && ($params && RETURN_CODE === RETURN_CODE)) {
            $code = substr(trim($code), 0, -2) . PHP_EOL . CONTROL_ADDITIONS;
            TRegistry::setCode($filename, $code);
        }

        TObject::getLogger()->debug(__METHOD__ . '::' . $filename, __FILE__, __LINE__);

        if ((isset($params) && ($params && INCLUDE_FILE === INCLUDE_FILE)) && !class_exists('\\' . $fqClassName)) {
            if (\Phar::running() != '') {
                include pathinfo($filename, PATHINFO_BASENAME);
            } else {
                //include $classFilename;
            }
        }

        return [$classFilename, $fqClassName, $code];
    }

    


}
