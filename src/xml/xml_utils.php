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


/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reed\Xml;

/**
 * Description of xml
 *
 * @author David
 */
class TXmlUtils
{
        //put your code here
        public static function convertArray(array $array, string $node_block = 'nodes', string $node_name = 'node'): string
        {
                $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";

                $xml .= '<' . $node_block . '>' . "\n";
                $xml .= self::convertArrayEx($array, $node_name);
                $xml .= '</' . $node_block . '>' . "\n";

                return $xml;
        }

        private static function convertArrayEx(array $array, string $node_name): string
        {
                $xml = '';

                if (is_array($array) || is_object($array)) {
                        foreach ($array as $key => $value) {
                                if (is_numeric($key)) {
                                        $key = $node_name;
                                }

                                $xml .= '<' . $key . '>' . "\n" . self::convertArrayEx($value, $node_name) . '</' . $key . '>' . "\n";
                        }
                } else {
                        $xml = htmlspecialchars($array, ENT_QUOTES) . "\n";
                }

                return $xml;
        }
}
