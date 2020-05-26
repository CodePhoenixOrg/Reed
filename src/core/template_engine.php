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

use Exception;
use Phink\Web\IWebObject;
use Reed\Web\TWebObject;

class TTemplateEngine extends TWebObject implements IWebObject
{

    use TWebObject;
    protected $loader = '';
    protected $templateContents = '';


    public function getTemplate(): string
    {
        return $this->templateContents;
    }

    public function __construct(TTemplateLoader $loader)
    {
        $this->loader = $loader;
    }

    private function load(): void
    {
        if (!file_exists(SITE_ROOT . $this->templatePath)) {
            throw new Exception('No template can be found on path ' . $this->templatePath . '.');
        }

        
        $info = (object) \pathinfo($this->path);
        $this->viewName = $info->filename;
        $this->dirName = $info->dirname;
        $this->bootDirName = $info->dirname;

        if ($this->componentIsInternal) {
            $this->dirName = dirname($this->dirName, 2);
        }

        $this->className = ucfirst($this->viewName);

        $this->setNamespace();
        $this->setNames();

    }
}
