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

namespace Reed\Core;

use FunCom\Element;
use Reed\Registry\Registry;
use Reed\Web\IWebObject;
use Reed\Web\WebObject;
use Reed\Web\WebObjectInterface;
use Reed\Web\WebObjectTrait;

class Router extends Element implements WebObjectInterface
{
    use WebObjectTrait;

    protected $apiName = '';
    protected $baseNamespace = '';
    protected $apiFileName = '';
    protected $translation = '';
    protected $routes = [];
    protected $requestType = REQUEST_TYPE_WEB;
    private $_isFound = false;

    public function __construct($parent)
    {
        // parent::__construct($parent);
        $this->parent = $parent;
        $this->application = $parent;
        $this->commands = $parent->getCommands();
        $this->request = $parent->getRequest();
        $this->response = $parent->getResponse();
        $this->twigEnvironment = $parent->getTwigEnvironment();
    }

    public function getTranslation(): string
    {
        return $this->translation;
    }

    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function isFound(): bool
    {
        return $this->_isFound;
    }

    public function match(): string
    {
        $result = REQUEST_TYPE_WEB;

        if ($this->routes()) {
            foreach ($this->routes as $key => $value) {
                $result = $key;
                $this->requestType = $key;

                $methods = $value;
                $method = strtolower(REQUEST_METHOD);

                if (!isset($methods[$method])) {
                    continue;
                }

                $routes = $methods[$method];
                $url = REQUEST_URI;
                foreach ($routes as $key => $value) {
                    $matches = \preg_replace('@' . $key . '@', $value, $url);

                    if ($matches === $url) {
                        continue;
                    }

                    $this->_isFound = true;
                    $this->requestType = $key;
                    $this->translation = $matches;

                    $this->componentIsInternal = substr($this->translation, 0, 1) == '@';
                    $this->dirName = pathinfo($this->translation, PATHINFO_DIRNAME);

                    $baseurl = parse_url($this->translation);
                    if ($this->componentIsInternal) {
                        $path = substr($baseurl['path'], 2);
                        $this->path = PHINK_VENDOR_APPS . $path;

                    } else {
                        $this->path = APP_DIR . $baseurl['path'];
                    }

                    $this->parameters = [];
                    if (isset($baseurl['query'])) {
                        parse_str($baseurl['query'], $this->parameters);
                    }

                    $this->getLogger()->dump('IS FOUND', $this->_isFound ? 'TRUE' : 'FALSE');
                    if ($this->_isFound) {
                        $this->getLogger()->debug('URL: ' . $url);
                        $this->getLogger()->debug('MATCHES: key = ' . $key . ', value = ' . $matches);
                        $this->getLogger()->dump('PATH', $this->path);
                        $this->getLogger()->dump('BASEURL', $baseurl);
                        $this->getLogger()->dump('PARAMETERS', $this->parameters);
                    }

                    return $result;

                }

            }
        }

        if ($this->translation === '') {
            $this->requestType = REQUEST_TYPE_WEB;
            $result = REQUEST_TYPE_WEB;
        }

        return $result;
    }

    public function routes(): array
    {
        $routesArray = Registry::item('routes');

        if (count($routesArray) === 0 && file_exists(DOCUMENT_ROOT . 'routes.json')) {
            $routesFile = file_get_contents(DOCUMENT_ROOT . 'routes.json');

            if (strlen($routesFile) === 0) {
                return false;
            }

            $routesArray = json_decode($routesFile, true);
        } elseif (count($routesArray) === 0 && !file_exists(DOCUMENT_ROOT . 'routes.json')) {
            $routesArray = [];
            $routesArray['web'] = [];
            $routesArray['web']['get'] = [];
            $routesArray['web']['post'] = [];
            $routesArray['web']['get']["^/$"] = "@/welcome/app/views/home.phtml";
        }

        $routesArray['web']['get']["^/admin/console$"] = "@/console/app/views/console.phtml";
        $routesArray['web']['get']["^/admin/console/$"] = "@/console/app/views/console.phtml?console=help";
        $routesArray['web']['get']["^/admin/console/([a-z-]+)(/)?$"] = "@/console/app/views/console.phtml?console=$1";
        $routesArray['web']['get']["^/admin/console/([a-z-]+)/([a-z-]+)$"] = "@/console/app/views/console.phtml?console=$1&arg=$2";
        $routesArray['web']['post']["^/admin/console(/)?$"] = "@/console/app/views/console_window.phtml";
        $routesArray['web']['post']["^/admin/console/token/$"] = "@/console/app/views/token.phtml";
        $routesArray['web']['get']["^/tuto(/)?$"] = "@/tuto/app/views/index.phtml";
        $routesArray['web']['get']["^/admin(/)?$"] = "@/admin/app/views/page.phtml?di=mkmain";
        $routesArray['web']['get']["^/admin/(\\?([a-zA-Z0-9\._\-=&]+))?$"] = "@/admin/app/views/page.phtml?$2";
        $routesArray['web']['post']["^/admin/(\\?([a-zA-Z0-9\._\-=&]+))?$"] = "@/admin/app/views/page.phtml?$2";
        $routesArray['web']['get']["^/admin/source/(\\?([a-z0-9\._\-=&]+))?$"] = "@/admin/app/views/source.phtml?$2";
        $routesArray['web']['get']["^/admin/qbe(/)?$"] = "@/qbe/app/views/qbe.phtml";
        $routesArray['web']['get']["^/admin/qbe/([a-z-]+)$"] = "@/qbe/app/views/qbe.phtml?qbe=$1";
        $routesArray['web']['get']["^/admin/qbe/([a-z-]+)/([a-z-]+)$"] = "@/qbe/app/views/qbe.phtml?qbe=$1&arg=$2";
        $routesArray['web']['post']["^/admin/qbe/$"] = "@/qbe/app/views/qbe_window.phtml";
        $routesArray['web']['post']["^/admin/qbe/grid/$"] = "@/qbe/app/views/qbe_grid.phtml";
        
        foreach ($routesArray as $key => $value) {
            Registry::write('routes', $key, $value);
        }

        if(REWRITE_BASE != '/') {
            $this->_translateRoutes($routesArray, REWRITE_BASE);
        }

        $this->routes = $routesArray;

        return $routesArray;
    }

    private function _translateRoutes(array &$routes, $rewritebase) : void
    {
        foreach($routes as $type => $methods) {
            // type = rest / web
            // methods = get / post / put / delete
            foreach ($methods as $method => $entry) {
                foreach($entry as $route => $rule) {
                    unset($routes[$type][$method][$route]);
                    $route = str_replace('^/', '^' . $rewritebase, $route);
                    $routes[$type][$method][$route] = $rule;
                }
            }
        }
    } 

    public function translate()
    {}

    public function dispatch()
    {}
}
