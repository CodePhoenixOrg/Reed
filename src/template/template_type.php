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

use Reed\Core\TEnumerator;

class ETemplateType extends TEnumerator
{
    public const NON_PHINK_TEMPLATE = 0;
    public const PHINK_SERVER_TEMPLATE = 1;
    public const PHINK_CLIENT_TEMPLATE = 2;
    public const PHINK_WIDGET_TEMPLATE = 4;
    public const PHINK_PARTIAL_TEMPLATE = 8;
    public const PHINK_INNER_TEMPLATE = 16;
}
