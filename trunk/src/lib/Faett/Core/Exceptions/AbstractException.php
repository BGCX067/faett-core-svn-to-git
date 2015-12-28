<?php

/**
 * Faett_Core_Exceptions_AbstractException
 *
 * NOTICE OF LICENSE
 * 
 * Faett_Core is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Faett_Core is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Faett_Core.  If not, see <http://www.gnu.org/licenses/>.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Faett_Core to newer
 * versions in the future. If you wish to customize Faett_Core for your
 * needs please refer to http://www.faett.net for more information.
 *
 * @category   Faett
 * @package    Faett_Core
 * @copyright  Copyright (c) 2009 <tw@faett.net> Tim Wagner
 * @license    <http://www.gnu.org/licenses/> 
 * 			   GNU General Public License (GPL 3)
 */

/**
 * @category   	Faett
 * @package    	Faett_Core
 * @copyright  	Copyright (c) 2009 <tw@faett.net> Tim Wagner
 * @license    	<http://www.gnu.org/licenses/> 
 * 				GNU General Public License (GPL 3)
 * @author      Tim Wagner <tw@faett.net>
 */
abstract class Faett_Core_Exceptions_AbstractException extends Exception
{
    /**
     * The key for I18N.
     * @var string
     */
    private $_key = '';

    /**
     * Sets the key for I18N.
     *
     * @param string $key The message key for I18N
     * @return void
     */
    protected function _setKey($key)
    {
        $this->_key = $key;
    }

    /**
     * Returns the key for I18N.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->_key;
    }
}