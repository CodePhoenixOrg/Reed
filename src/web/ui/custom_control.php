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

namespace Reed\Web\UI;

use Reed\Core\IObject;
use Reed\Core\TObject;
use Reed\Web\IHttpTransport;
use Reed\Web\IWebObject;

/**
 * Description of custom_control
 *
 * @author David
 */
abstract class TCustomControl extends TObject implements IHttpTransport, IWebObject
{
    use \Reed\Web\TWebObject;

    public function __construct(IObject $parent)
    {
        parent::__construct($parent);

        $this->motherView = $parent->getMotherView();
        $this->motherUID = $parent->getMotherUID();
    }

    protected $isRendered = false;

    public function init(): void
    {
    }

    public function load(): void
    {
    }

    public function view($html)
    {
    }

    public function partialLoad(): void
    {
    }

    public function beforeBinding(): void
    {
    }

    public function afterBinding(): void
    {
    }

    public function parse(): bool
    {
        return false;
    }

    public function renderHtml(): void
    {
    }

    public function displayHtml(): void
    {
    }

    public function renderTwig(): void
    {
    }

    public function render(): void
    {
    }

    public function getHtml(): string
    {
        ob_start();
        $this->render();
        $html = ob_get_clean();
        
        return $html;
    }

    public function unload(): void
    {
    }
}
