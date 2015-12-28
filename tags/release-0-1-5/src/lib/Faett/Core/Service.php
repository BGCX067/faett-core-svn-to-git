<?php

/**
 * Faett_Manager_Adminhtml_ChannelController
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

// reset the include path
set_include_path(getcwd().DS.'downloader'.DS.'pearlib'.DS.'php'.PS.get_include_path());

// set the error reporting to avoid strict PEAR related warnings
error_reporting(E_ALL & ~E_NOTICE);

// include the necessary libraries
require_once 'PEAR.php';
require_once 'PEAR/Frontend.php';
require_once 'PEAR/Config.php';
require_once 'PEAR/Command.php';
require_once 'PEAR/Command/Remote.php';
require_once 'PEAR/PackageFile/Parser/v2.php';
require_once 'Archive/Tar.php';
require_once 'Faett/Core/Factory.php';

/**
 * @todo
 * 		Bugfix for PEAR version v1.6.2, has to be removed for a new PEAR version
 */
require_once 'PEAR/PackageFile/v2.php';
require_once 'PEAR/PackageFile/v2/Validator.php';

/**
 *
 */
require_once 'Faett/Core/Interfaces/Service.php';
require_once 'Faett/Core/Exceptions/ChannelNotRESTEnabledException.php';
require_once 'Faett/Core/Exceptions/NoPackageListException.php';
require_once 'Faett/Core/Exceptions/PackageInfoException.php';
require_once 'Faett/Core/Exceptions/UnknownChannelException.php';
require_once 'Faett/Core/Exceptions/UnknownChannelStateException.php';

/**
 * @category   	Faett
 * @package    	Faett_Core
 * @copyright  	Copyright (c) 2009 <tw@faett.net> Tim Wagner
 * @license    	<http://www.gnu.org/licenses/> 
 * 				GNU General Public License (GPL 3)
 * @author      Tim Wagner <tw@faett.net>
 */
class Faett_Core_Service implements Faett_Core_Interfaces_Service
{

    /**
     * The PEAR_Registry instance.
     * @var PEAR_Registry
     */
    private $_registry;

    /**
     * The PEAR_Config instance.
     * @var PEAR_Config
     */
    private $_config;

    /**
     * The PEAR_Frontend instance.
     * @var PEAR_Frontend
     */
    private $_ui;

    /**
     * Private constructor to initialize the instance
     * with the necessary data.
     *
     * @param string $baseDir The Magento base directory
     * @return void
     */
    public function __construct($baseDir)
    {
        // check if the passed Magento base directory exists
        if (!is_dir($baseDir)) {
            throw new Exception(
            	'Magento base directory ' . $baseDir . ' doesn\'t exists'
            );
        }
        // load the PEAR directory for the Magento channel
        $pearDir = $baseDir.DS.'downloader'.DS.'pearlib';
        // check if the Magento PEAR directory exists
        if (!is_dir($pearDir)) {
            throw new Exception(
            	'Magento PEAR base directory ' . $pearDir . ' doesn\'t exists'
            );
        }
        // load the registry
        $this->_registry = new PEAR_Registry($pearDir.DS.'php');
        // initialize the configuration with the channels configuration file
        $this->_config = PEAR_Config::singleton($pearDir.DS.'pear.ini', '-');
        // overwrite the configuration values
        $this->_config->set('auto_discover', 1);
        $this->_config->set('cache_ttl', 60);
        $this->_config->set('preferred_state', 'alpha');
        $this->_config->set('bin_dir', $pearDir);
        $this->_config->set('php_dir', $pearDir.DS.'php');
        $this->_config->set('download_dir', $pearDir.DS.'download');
        $this->_config->set('temp_dir', $pearDir.DS.'temp');
        $this->_config->set('data_dir', $pearDir.DS.'data');
        $this->_config->set('cache_dir', $pearDir.DS.'cache');
        $this->_config->set('test_dir', $pearDir.DS.'tests');
        $this->_config->set('doc_dir', $pearDir.DS.'docs');
        // initialize the Magento specific settings
        foreach ($this->_config->getKeys() as $key) {
            if (!(substr($key, 0, 5)==='mage_' && substr($key, -4)==='_dir')) {
                continue;
            }
            $this->_config->set(
                $key,
                preg_replace(
                	'#^\.#',
                    addslashes(
                        Mage::getBaseDir()
                    ),
                    $this->_config->get($key)
                )
            );
        }
        // set the registry
        $this->_config->setRegistry($this->_registry);
        // initialize the dependeny database
        PEAR_DependencyDB::singleton(
            $this->_config,
            $pearDir.DS.'php'.DS.'.depdb'
        );
        // register the commands, including the one for Magento
        PEAR_Command::registerCommands(
            true,
            $pearDir.DS.'php'.DS.'PEAR'.DS.'Command'.DS
        );
        // initialize the PEAR frontend
        PEAR_Frontend::setFrontendClass('Faett_Core_Frontend_PEAR');
	    $this->_ui = PEAR_Command::getFrontendObject();
        $this->_ui->setConfig($this->_config);
        // set the callback for rendering the messages
        PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, array($this->_ui, "log"));
    }

    /**
     * (non-PHPdoc)
     * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#install()
     */
    public function install(array $opts = array(), array $params = array())
    {
        return $this->_command(
            Faett_Core_Interfaces_Service::COMMAND_INSTALL,
            $opts,
            $params
        );
    }

    /**
     * (non-PHPdoc)
     * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#uninstall()
     */
    public function uninstall(array $opts = array(), array $params = array())
    {
        return $this->_command(
            Faett_Core_Interfaces_Service::COMMAND_UNINSTALL,
            $opts,
            $params
        );
    }

    /**
     * (non-PHPdoc)
     * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#upgrade()
     */
    public function upgrade(array $opts = array(), array $params = array())
    {
        return $this->_command(
            Faett_Core_Interfaces_Service::COMMAND_UPGRADE,
            $opts,
            $params
        );
    }

    /**
     * (non-PHPdoc)
     * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#channelDiscover()
     */
    public function channelDiscover(array $opts = array(), array $params = array())
    {
        return $this->_command(
            Faett_Core_Interfaces_Service::COMMAND_CHANNEL_DISCOVER,
            $opts,
            $params
        );
    }

    /**
     * (non-PHPdoc)
     * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#packageExists($packageName, $alias)
     */
    public function packageExists($packageName, $alias) {
        return $this->_registry->packageExists($packageName, $alias);
    }

    /**
     * (non-PHPdoc)
     * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#channelExists($channelName)
     */
    public function channelExists($channelName) {
        return $this->_registry->channelExists($channelName);
    }

    /**
     * (non-PHPdoc)
     * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#listPackages($channelName)
     */
    public function listPackages($channelName)
    {
        // store the default channel
        $savechannel = $this->_config->get('default_channel');
        // check if the cannel already exists
        if ($this->channelExists($channelName)) {
            // if yes, set it as the default channel
            $this->_config->set('default_channel', $channelName);
        } else {
            // initialize the options and the parameters
            $opts = array();
            $params = array($channelName);
            // try to discover channel
            $this->channelDiscover($opts, $params);
            // check if the cannel already exists
            if (!$this->channelExists($channelName)) {
                throw Faett_Core_Exceptions_UnknownChannelException::create(
                    $channelName,
                    '201.error.channel-does-not-exists'
                );
            }
        }
        // load the channel itself from the PEAR registry
	    $chan = $this->_registry->getChannel($channelName);
        // initialize a REST command for checking the channel's state
        $cmd = new PEAR_Command_Remote($this->_ui, $this->_config);
        // check the channel's state
        $e = $cmd->_checkChannelForStatus($channelName, $chan);
        if (PEAR::isError($e)) {
            // if an error occurs, reset to the default channel
            $this->_config->set('default_channel', $savechannel);
            // continue with the next channel
            throw Faett_Core_Exceptions_UnknownChannelStateException::create(
                $e->getMessage()
            );
        }
        // get the channel's base URL
        $base = $chan->getBaseURL(
        	'REST1.0',
            $this->_config->get('preferred_mirror')
        );
        // check if the channel's server is REST enabled
        $restSupport = $chan->supportsREST(
            $this->_config->get('preferred_mirror')
        );
        // check if the channel is REST enabled
        if ($restSupport && $base) {
            // load the channel data and the channel's packages
            $rest = $this->_config->getREST('1.0', array());
            $packages = $rest->listPackages($base);
        } else {
            // if not, reset to the default channel
            $this->_config->set('default_channel', $savechannel);
            // continue with the next channel
            throw Faett_Core_Exceptions_ChannelNotRESTEnabledException::create(
            	$channelName,
            	'201.error.channel-no-rest'
            );
        }
        // check if the channel's packages was loaded successfully
        if (PEAR::isError($packages)) {
            // if not, reset to the default channel
            $this->_config->set('default_channel', $savechannel);
            // continue with the next channel
            throw Faett_Core_Exceptions_NoPackageListException::create(
                $packages->getMessage(),
            	'201.error.no-package-list'
            );
        }
        // set the default channel again
        $this->_config->set('default_channel', $savechannel);
        // return the packages
        return $packages;
    }

    /**
     * (non-PHPdoc)
     * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#packageInfo($packageName, $channel)
     */
	public function packageInfo($packageName, $channel)
	{
        // store the default channel
        $savechannel = $this->_config->get('default_channel');
        // check if the cannel already exists
        if ($this->_registry->channelExists($channel)) {
            $this->_config->set('default_channel', $channel);
        } else {
            // throw a new exception
            throw Faett_Core_Exceptions_UnknownChannelException::create(
            	'Channel ' . $channel . ' does not exist'
            );
        }
        // load the channel from the registry
	    $chan = $this->_registry->getChannel($channel);
        // initialize a REST command for checking the channel's state
        $cmd = new PEAR_Command_Remote($this->_ui, $this->_config);
        if (PEAR::isError($e = $cmd->_checkChannelForStatus($channel, $chan))) {
            // reset the default channel
            $this->_config->set('default_channel', $savechannel);
            // throw a new exception
            throw Faett_Core_Exceptions_UnknownChannelStateException::create(
                $e->getMessage()
            );
        }
        // get the channel's base URL
        $base = $chan->getBaseURL(
        	'REST1.0',
            $this->_config->get('preferred_mirror')
        );
        // check if the channel's server is REST enabled
        $restSupport = $chan->supportsREST(
            $this->_config->get('preferred_mirror')
        );
        // check if the channel is REST enabled
        if ($restSupport && $base) {
            // load the channel data and the package information
            $rest = $this->_config->getREST('1.0', array());
            $info = $rest->packageInfo($base, $packageName);
        } else {
            $r = $this->_config->getRemote();
            $info = $r->call('package.info', $packageName);
        }
        // check if the package information was loaded successfully
        if (PEAR::isError($info)) {
            // reset the default channel
            $this->_config->set('default_channel', $savechannel);
            // throw a new exception
            throw Faett_Core_Exceptions_PackageInfoException::create(
                $info->getMessage()
            );
        }
        // if no packge name was found log an error message
        if (!isset($info['name'])) {
            // reset the default channel
            $this->_config->set('default_channel', $savechannel);
            // throw a new exception
            throw Faett_Core_Exceptions_PackageInfoException::create(
                'Can\'t find a package name'
            );
        }
        // check if the package is installed
        $installed = $this->_registry->packageInfo(
            $info['name'],
            null,
            $channel
        );
        // if yes, set the information
        $info['installed'] = $installed['version'] ? $installed['version'] : '';
        if (is_array($info['installed'])) {
            $info['installed'] = $info['installed']['release'];
        }
        // return the package information
        return $info;
	}

	/**
	 * (non-PHPdoc)
	 * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#packageFile($package)
	 */
	public function packageFile($package)
	{
        // initialize the archive
        $tar = new Archive_Tar($package);
        // try to load the content of the package2.xml file
        $contents = $tar->extractInString(
            $packageFileName = 'package2.xml'
        );
        // if not available, try to load from package.xml file
        if (!$contents) {
            $contents = $tar->extractInString(
                $packageFileName = 'package.xml'
            );
        }
        // initialize the parser for the package file and parse it
        $pkg = new PEAR_PackageFile_Parser_v2();
        $pkg->setConfig($this->getConfig());
        $pf = $pkg->parse($contents, $packageFileName);
        // check if errors occurs and throw an exception if necessary
        if (PEAR::isError($pf)) {
            throw Faett_Core_Exceptions_InvalidPackageFileException::create(
                $pf->getMessage()
            );
        }
        // validate the package
        if (!$pf->validate()) {
            // initialize the string for the errors and warnings
            $errors = '';
            // concatenate the message
            foreach ($pf->getValidationWarnings() as $warning) {
                $errors .= PHP_EOL . ucfirst($warning['level']) . ': ' .
                    $warning['message'];
            }
            // throw an exception with the warnings
            throw Faett_Core_Exceptions_InvalidPackageFileException::create(
                $errors
            );
        }
        // return the package file
        return $pf;
	}

    /**
     * Initializes the
     * @param unknown_type $command
     * @param array $opts
     * @param array $params
     * @return unknown_type
     */
    protected function _command(
        $command,
        array $opts = array(),
        array $params = array()) {
        // initialize the command
        $cmd = PEAR_Command::factory($command, $this->_config);
        if (PEAR::isError($cmd)) {
            throw new Exception($cmd->getMessage());
        }
        // run the command
        return $cmd->run($command, $opts, $params);
    }

    /**
     * Returns the user interface implemented
     * by the PEAR_Frontend instance,
     *
     * @return PEAR_Frontend The user interface
     */
    public function getUI()
    {
        return $this->_ui;
    }

    /**
     * Returns the initialized PEAR configuration
     * for the internal Magento PEAR channel.
     *
     * @return PEAR_Config The PEAR config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Returns the initializes PEAR registry instance.
     *
     * @return PEAR_Registry The PEAR registry
     */
    public function getRegistry()
    {
        return $this->_registry;
    }

    /**
     * (non-PHPdoc)
     * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#setUsername()
     */
    public function setUsername($username, $channel, $layer = 'user')
    {
    	// set username for channel
        $this->_config->set('username', $username, $layer, $channel);
        // write config
    	$this->_config->store($layer);
    }

     /**
      * (non-PHPdoc)
      * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#setPassword()
      */
    public function setPassword($password, $channel, $layer = 'user')
    {
    	// set password for channel
        $this->_config->set('password', $password, $layer, $channel);
        // write config
    	$this->_config->store($layer);
    }

    /**
     * (non-PHPdoc)
     * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#getUsername($channel)
     */
    public function getUsername($channel, $layer = 'user')
    {
    	// return the username for channel
        return $this->_config->get('username', $layer, $channel);
    }

    /**
     * (non-PHPdoc)
     * @see lib/Faett/Core/Interfaces/Faett_Core_Interfaces_Service#getPassword($channel)
     */
    public function getPassword($channel, $layer = 'user')
    {
    	// return the password for channel
        return $this->_config->get('password', $layer, $channel);
    }
}