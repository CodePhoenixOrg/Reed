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

namespace Reed\Template;

use Exception;
use Reed\Web\TWebObject;
use Reed\Web\UI\TCustomControl;

class TTemplateEngine extends TCustomControl
{

    use TWebObject;
    protected $templateContents = '';

    public function getTemplate(): string
    {
        return $this->templateContents;
    }

    public function __construct(TTemplateLoader $loader)
    {
        $this->path = $loader->getTemplatePath();
        $this->componentIsInternal = $loader->isInnerTemplate();
        $this->isAJAX = $loader->isClientTemplate();
        $this->isPartial = $loader->isPartialTemplate();
        $this->test('home.phtml');

    }

    public function test(string $templateName): void
    {
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

        $parser = new TTemplateParser($this);
        $parser->parse();
        $creations = $parser->getCreations();
        $declarations = $parser->getAdditions();
        $viewHtml = $parser->getViewHtml();
    }
}
