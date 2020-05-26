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

class TTemplateLoader extends TObject
{
    protected $templatePath = '';
    protected $templateType = ETemplateType::NON_PHINK_TEMPLATE;
    protected $componentIsInternal = false;
    protected $isAJAX = false;
    protected $isPartial = false;

    public function getTemplatePath()
    {
        return $this->templatePath;
    }

    public function getTemplateType()
    {
        return $this->templatePath;
    }

    public function __construct(string $templatePath, ?ETemplateType $templateType = null)
    {
        if($templateType !== null) {
            $this->templateType = $templateType::enum();
        }

        if($this->templateType == ETemplateType::PHINK_CLIENT_TEMPLATE) {
            $this->isAJAX = true;   
        }
        elseif($this->templateType == ETemplateType::PHINK_CLIENT_PARTIAL_TEMPLATE) {
            $this->isAJAX = true;   
            $this->isPartial = true;
        }
        elseif($this->templateType == ETemplateType::PHINK_WIDGET_TEMPLATE) {
            $this->isPartial = true;
            $this->componentIsInternal = true;
        }

        $this->templatePath = $templatePath;
        $this->try();
    }

    private function try(): void
    {
        if (!file_exists(SITE_ROOT . $this->templatePath)) {
            throw new Exception('No template can be found on path ' . $this->templatePath . '.');
        }
    }
}
