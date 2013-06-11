<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  PHP
 * @author    V.Krishn <vkrishn4@gmail.com>
 * @copyright Copyright (c) 2012-2013 V.Krishn <vkrishn4@gmail.com>
 * @license   GPL
 * @link      http://github.com/insteps/phputils
 * @version   0.1.0
 *
 */

/**
 * Parse a CSV string into an array for php 4+.
 * @param string $input String
 * @param string $delimiter String
 * @param string $enclosure String
 * @return array
 */
function str_getcsv4($input, $delimiter = ',', $enclosure = '"') {

    if( ! preg_match("/[$enclosure]/", $input) ) {
        return (array)preg_replace(array("/^\\s*/", "/\\s*$/"), '', explode($delimiter, $input));
    }

    $token = "##"; $token2 = "::";
    //alternate tokens "\034\034", "\035\035", "%%";
    $t1 = preg_replace(array("/\\\[$enclosure]/", "/$enclosure{2}/",
            "/[$enclosure]\\s*[$delimiter]\\s*[$enclosure]\\s*/", "/\\s*[$enclosure]\\s*/"),
        array($token2, $token2, $token, $token), trim(trim(trim($input), $enclosure)));

    $a = explode($token, $t1);
    foreach($a as $k=>$v) {
        if ( preg_match("/^{$delimiter}/", $v) || preg_match("/{$delimiter}$/", $v) ) {
            $a[$k] = trim($v, $delimiter); $a[$k] = preg_replace("/$delimiter/", "$token", $a[$k]);
        }
    }
    $a = explode($token, implode($token, $a));
    return (array)preg_replace(array("/^\\s/", "/\\s$/", "/$token2/"), array('', '', $enclosure), $a);

}

if ( ! function_exists('str_getcsv')) {
    function str_getcsv($input, $delimiter = ',', $enclosure = '"') {
        return str_getcsv4($input, $delimiter, $enclosure);
    }
}