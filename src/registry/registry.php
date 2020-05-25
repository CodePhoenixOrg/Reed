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

/**
 * Description of registry
 *
 * @author david
 */

class TRegistry extends TStaticObject
{
    private static $_classRegistry = null;
    private static $_code = [];
    private static $_items = [];
    private static $_isInit = false;

    public static function init(): bool
    {
        if (self::$_isInit) {
            return true;
        }

        self::importClasses(Reed_VENDOR_WIDGETS);

        self::write(
            'classes',
            'TPluginRenderer',
            [
                'alias' => 'pluginrenderer',
                'path' => 'web' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR,
                'namespace' => ROOT_NAMESPACE . '\Web\UI',
                'hasTemplate' => false,
                'canRender' => true,
                'isAutoloaded' => true
            ]
        );
        self::write(
            'classes',
            'TPlugin',
            [
                'alias' => 'plugin',
                'path' => 'web' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'widget' . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR,
                'namespace' => ROOT_NAMESPACE . '\Web\UI\Widget\Plugin',
                'hasTemplate' => true,
                'canRender' => true,
                'isAutoloaded' => true
            ]
        );
        self::write(
            'classes',
            'TPluginChild',
            [
                'alias' => 'pluginchild',
                'path' => 'web' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'widget' . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR,
                'namespace' => ROOT_NAMESPACE . '\Web\UI\Widget\Plugin',
                'hasTemplate' => false,
                'canRender' => false,
                'isAutoloaded' => true
            ]
        );
        self::write(
            'classes',
            'TUserComponent',
            [
                'alias' => 'component',
                'path' => 'web' . DIRECTORY_SEPARATOR . 'ui' . DIRECTORY_SEPARATOR . 'widget' . DIRECTORY_SEPARATOR . 'user_component' . DIRECTORY_SEPARATOR,
                'namespace' => ROOT_NAMESPACE . '\Web\UI\Widget\UserComponent',
                'hasTemplate' => false,
                'canRender' => true,
                'isAutoloaded' => true
            ]
        );

        self::$_isInit = true;

        return self::$_isInit;
    }

    public static function importClasses(string $dirName): void
    {
        $localRegistryFilename = SITE_ROOT . $dirName . '/app/registry.json';

        if (!file_exists($localRegistryFilename)) {
            $localRegistryFilename = SITE_ROOT . $dirName . '/registry.json';
        }

        if (!file_exists($localRegistryFilename)) {
            return;
        }

        // self::getLogger()->debug('LOCAL REGISTRY::' . $localRegistryFilename);

        $localRegistryContents = \file_get_contents($localRegistryFilename);
        // self::getLogger()->dump('LOCAL REGISTRY CONTENTS', $localRegistryContents);

        $registry = (!empty($localRegistryContents)) ? json_decode($localRegistryContents, true) : [];
        // self::getLogger()->dump('LOCAL REGISTRY ARRAY', $registry);

        if ($registry === null) {
            return;
        }

        $classes = count($registry['classes']) > 0 ? $registry['classes'][0] : [];

        foreach ($classes as $type => $class) {
            $info = new TClassInfo([$type => $class]);
            if ($info->isValid()) {
                self::registerClass($info);
            }
        }
    }

    public static function registerClass(TClassInfo $info)
    {
        self::write('classes', $info->getType(), [
            'alias' => $info->getAlias(),
            'path' => $info->getPath(),
            'namespace' => $info->getNamespace(),
            'hasTemplate' => $info->hasTemplate(),
            'canRender' => $info->canRender(),
            'isAutoloaded' => $info->isAutoloaded()
        ]);
        self::write('classes', $info->getAlias(), ['type' => $info->getType()]);
    }

    public static function classInfo(string &$className = '')
    {
        $result = null;

        if (self::init() && isset(self::$_items['classes'][$className])) {
            $result = self::$_items['classes'][$className];
            if (isset($result['type'])) {
                $className = $result['type'];
                $result = self::$_items['classes'][$result['type']];
            }

            $result = (object) $result;
        }

        return $result;
    }

    public static function widgetPath($className): string
    {
        $result = TRegistry::classPath($className);
        $result = \str_replace("~/", Reed_WIDGETS_ROOT, $result);

        return $result;
    }

    public static function classPath($className = ''): string
    {
        $classInfo = self::classInfo($className);
        return ($classInfo) ? $classInfo->path : '';
    }

    public static function classNamespace($className = ''): bool
    {
        $classInfo = self::classInfo($className);
        return ($classInfo) ? $classInfo->namespace : '';
    }

    public static function classHasTemplate($className = ''): bool
    {
        $classInfo = self::classInfo($className);
        return ($classInfo) ? $classInfo->hasTemplate : false;
    }

    public static function classCanRender($className = ''): bool
    {
        $classInfo = self::classInfo($className);
        return ($classInfo) ? $classInfo->canRender : '';
    }

    public static function getCode($id): string
    {
        return self::$_items['code'][$id];
    }

    public static function setCode($id, $value): void
    {
        self::write('code', $id, $value);
        //$id = str_replace(DIRECTORY_SEPARATOR, '_', $id);
        //file_put_contents(RUNTIME_DIR . $id . PREHTML_EXTENSION, $value);
        //$keys = array_keys(self::$_code);
        //self::$logger->debug('CODE REGISTRY : ' . print_r($keys, true));
    }

    public static function getHtml($id): string
    {
        return self::$_items['html'][$id];
    }

    public static function setHtml($id, $value): void
    {
        
        self::write('html', $id, $value);
    }

    /**
     * @param mixed $item Name of the key
     * @param array $params May one key/value pair or an array of pairs
     * @return void 
     */
    public static function write($item, ...$params): void
    {
        if (!isset(self::$_items[$item])) {
            self::$_items[$item] = [];
        }
        if (count($params) === 2) {
            $key = $params[0];
            $value = $params[1];
            self::$_items[$item][$key] = $value;
        }
        if (count($params) === 1 && is_array($params)) {
            if (count($params[0]) > 0 && is_array($params[0])) {
                foreach ($params[0] as $key => $value) {
                    self::$_items[$item][$key] = $value;
                }
            }
        }
    }

    public static function push($item, $key, $value): void
    {
        if (!isset(self::$_items[$item])) {
            self::$_items[$item] = [];
        }

        if (!isset(self::$_items[$item][$key])) {
            self::$_items[$item][$key] = $value;
        }

        if (isset(self::$_items[$item][$key]) && !is_array(self::$_items[$item][$key])) {
            $tmp = self::$_items[$item][$key];
            self::$_items[$item][$key] = [];
            self::$_items[$item][$key][] = $tmp;
        }

        array_push(self::$_items[$item][$key], $value);
    }

    public static function unshift($item, $key, $value): void
    {
        if (!isset(self::$_items[$item])) {
            self::push($item, $key, $value);
        }

        if (!isset(self::$_items[$item][$key])) {
            self::$_items[$item][$key] = $value;
        }
        
        if (isset(self::$_items[$item][$key]) && !is_array(self::$_items[$item][$key])) {
            $tmp = self::$_items[$item][$key];
            self::$_items[$item][$key] = [];
            self::$_items[$item][$key][] = $tmp;
        }

        array_unshift(self::$_items[$item][$key], $value);
    }

    public static function read($item, $key, $defaultValue = null)
    {
        $result = null;

        if (self::$_items[$item] !== null) {
            $result = isset(self::$_items[$item][$key]) ? self::$_items[$item][$key] : (($defaultValue !== null) ? $defaultValue : null);
        }

        return $result;
    }

    public static function ini($section, $key = null)
    {
        $section = self::read('ini', $section);
        $value = null;

        if ($key === null) {
            return $section;
        }

        if (is_array($section)) {
            $value = isset($section[$key]) ? $section[$key] : $value;
        }

        return $value;
    }

    public static function remove($item): void
    {
        if (array_key_exists($item, self::$_items)) {
            unset(self::$_items[$item]);
        }
    }

    public static function keys($item = null): array
    {
        if ($item === null) {
            return array_keys(self::$_items);
        } elseif (is_array(self::$_items)) {
            return array_keys(self::$_items[$item]);
        } else {
            return [];
        }
    }

    public static function item($item, $value = null): ?array
    {
        if ($item === '' || $item === null) {
            return $item;
        }

        if (isset(self::$_items[$item])) {
            if ($value != null) {
                self::$_items[$item] = $value;
            } else {
                return self::$_items[$item];
            }
        }
        if (!isset(self::$_items[$item])) {
            self::$_items[$item] = [];
            // self::$_items[$item][] = $value;
            return self::$_items[$item];
        }
    }

    public static function exists($item, $key = null): bool
    {
        return isset(self::$_items[$item][$key]);
    }

    public static function clear(): void
    {
        TRegistry::$_items = [];
    }

    public static function dump(string $key): void
    {
        self::getLogger()->dump('Registry key ' . $key, TRegistry::item($key));
    }
}
