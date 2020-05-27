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
use Reed\Core\TObject;

class TTemplateLoader extends TObject implements ITemplate
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
        return $this->templateType;
    }

    public function isClientTemplate()
    {
        return $this->isAJAX;
    }

    public function isPartialTemplate()
    {
        return $this->isPartial;
    }

    public function isInnerTemplate()
    {
        return $this->componentIsInternal;
    }

    public function __construct(string $templatePath, ?ETemplateType $templateType = null)
    {
        if ($templateType !== null) {
            $this->templateType = $templateType::enum();
        }

        $this->isAJAX = ($this->templateType & ETemplateType::PHINK_CLIENT_TEMPLATE) === ETemplateType::PHINK_CLIENT_TEMPLATE;
        $this->isPartial = ($this->templateType & ETemplateType::PHINK_PARTIAL_TEMPLATE) === ETemplateType::PHINK_PARTIAL_TEMPLATE;
        $this->componentIsInternal = ($this->templateType & ETemplateType::PHINK_INNER_TEMPLATE) === ETemplateType::PHINK_INNER_TEMPLATE;

        $this->templatePath = $templatePath;
        $this->try();
    }

    private function try(): void
    {
        if (!file_exists(dirname($this->templatePath))) {
            throw new Exception('No template can be found on path ' . $this->templatePath . '.');
        }
    }
}
