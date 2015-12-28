<?php

/**
 * Faett_Core_Frontend_PEAR
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

require_once 'PEAR.php';
require_once 'PEAR/Frontend.php';

/**
 * This class implements the functionality to be
 * used as a PEAR frontend.
 *
 * @category   	Faett
 * @package    	Faett_Core
 * @copyright  	Copyright (c) 2009 <tw@faett.net> Tim Wagner
 * @license    	<http://www.gnu.org/licenses/> 
 * 				GNU General Public License (GPL 3)
 * @author      Tim Wagner <tw@faett.net>
 */
class Faett_Core_Frontend_PEAR extends PEAR_Frontend
{

    /**
     * The array with the formatted logging statements.
     * @var array
     */
    protected $_log = array();

    /**
     * The log stream to write all messages to.
     * @var unknown_type
     */
    protected $_logStream = null;

    /**
     * The EOL code to use either for HTML or text mode.
     * @var string
     */
    protected $_eol = Faett_Core_Frontend_PEAR::MODE_HTML;

    /**
     * The key for the HTML mode.
     * @var string
     */
    const MODE_HTML = 'mode/html';

    /**
     * The key for the HTML mode.
     * @var string
     */
    const MODE_TEXT = 'mode/text';

    /**
     * Array with the available line endings of the modes HTML or text.
     * @var array
     */
    protected $_lineEndings = array(
        Faett_Core_Frontend_PEAR::MODE_HTML => '<br/>',
        Faett_Core_Frontend_PEAR::MODE_TEXT => PHP_EOL
    );

    /**
     * Set the logstream and the mode to use.
     *
     * @param string|resource $stream 'stdout' or open php stream
     * @param string $mode
     * 		The mode to render the log messages, can be 'html' or 'text'
     * @return Faett_Core_Frontend_PEAR The PEAR_Frontend instance itself
     */
    public function setLogStream(
        $stream,
        $mode = Faett_Core_Frontend_PEAR::MODE_HTML) {
        $this->_logStream = $stream;

        if (array_key_exists($mode, $this->_lineEndings)) {
            $this->_eol = $this->_lineEndings[$mode];
        }
        return $this;
    }

    /**
     * Returns the log stream.
     *
     * @return string|resource $stream 'stdout' or open php stream
     */
    public function getLogStream()
    {
        return $this->_logStream;
    }

    /**
     * The log method itself.
     *
     * @param string $msg The message to log
     * @param $append_crlf If TRUE append the EOL after each message
     * @return void
     */
    public function log($msg, $appendCrlf = true)
    {
        // check the passed message is not empty
        if (is_null($msg) || false === $msg || '' === $msg) {
            return;
        }
        // check if the passed message contains an PEAR error
        if (PEAR::isError($msg)) {
            $msg = $msg->getMessage();
        }
        // check if EOL has to be appended
        if ($appendCrlf) {
            $msg .= $this->_eol;
        }
        // append empty space if message only contains a dot
        if ($msg === '.') {
            $msg .= ' ';
        }
        // attach the message to the array containing all messages
        $this->_log[] = $msg;
        // check if stdout or an open stream resource as stream is defined
        if ('stdout' === $this->_logStream) {
            echo $msg;
        } elseif (is_resource($this->_logStream)) {
            fwrite($this->_logStream, $msg);
        }
    }

    /**
     * Render the output data.
     *
     * @param string|array $data The data to render.
     * @param unknown_type $command The command (not implemented)
     * @return void
     */
    public function outputData($data, $command = '_default')
    {
        if (is_string($data)) {
            $this->log($data);
        } elseif (is_array($data)) {
            if (!empty($data['message']) && is_string($data['message'])) {
                $this->log($data['message']);
            } elseif (!empty($data['data'])) {
                if (is_string($data['data'])) {
                    $this->log($data['data']);
                } elseif (!empty($data['headline']) && is_array($data['data'])) {
                    $this->log($data['headline']);
                    foreach ($data['data'] as $msg) {
                        $this->log($msg[0]);
                    }
                } else {
                    $this->log(print_r($data, true));
                }
            } else {
                $this->log(print_r($data, true));
            }
        } else {
            $this->log(print_r($data, true));
        }
    }

    /**
     * Requests user confirmation (not implemented).
     *
     * @return void
     */
    public function userConfirm()
    {
    }

    /**
     * Clears the array with the log messages.
     *
     * @return void
     */
    public function clear()
    {
        $this->_log = array();
    }

    /**
     * Returns the array with the log messags.
     *
     * @return array Contains the log messages
     */
    public function getLog()
    {
        return $this->_log;
    }

    /**
     * Returns the contents of the array with the
     * log messages concatenated as string.
     *
     * @return string The content as string
     */
    public function getLogText()
    {
        $text = '';
        foreach ($this->getLog() as $log) {
            $text .= $log;
        }
        return $text;
    }

    /**
     * Processes, formats and returns the data passed as array.
     *
     * @param array $data The data to process
     * @return array The processed data
     */
    public function processOutputData($data)
    {
        if (isset($data['headline'])) {
            $rows = array();
            foreach ($data['data'] as $i=>$fields) {
                foreach ($fields as $f => $v) {
                    $rows[$i][$data['headline'][$f]] = $v;
                }
            }
            return $rows;
        } else {
            $data = $data['data'];
            while (is_array($data)) {
                $data = $data[0];
            }
            return $data;
        }
    }
}