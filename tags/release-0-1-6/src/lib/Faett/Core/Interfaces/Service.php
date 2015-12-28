<?php

/**
 * Faett_Core_Interfaces_Service
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
interface Faett_Core_Interfaces_Service
{
    /**
     * The PEAR install command.
     * @var string
     */
    const COMMAND_INSTALL = 'install';

    /**
     * The PEAR uninstall command.
     * @var string
     */
    const COMMAND_UNINSTALL = 'uninstall';

    /**
     * The PEAR upgrade command.
     * @var string
     */
    const COMMAND_UPGRADE = 'upgrade';

    /**
     * The PEAR channel-discover command.
     * @var string
     */
    const COMMAND_CHANNEL_DISCOVER = 'channel-discover';

    /**
     * Installs a package.
     *
     * @param array $opts The commandline options, e. g. the -f option
     * @param array $params The commandline params, the package name by default
     * @return string The result as string
     */
    public function install(array $opts = array(), array $params = array());

    /**
     * Uninstalls a package.
     *
     * @param array $opts The commandline options, e. g. the -f option
     * @param array $params The commandline params, the package name by default
     * @return string The result as string
     */
    public function uninstall(array $opts = array(), array $params = array());

    /**
     * Upgrades a package.
     *
     * @param array $opts The commandline options, e. g. the -f option
     * @param array $params The commandline params, the package name by default
     * @return string The result as string
     */
    public function upgrade(array $opts = array(), array $params = array());

    /**
     * Tries to discover the channel.
     *
     * @param array $opts The commandline options, e. g. the -f option
     * @param array $params The commandline params, the channel name by default
     * @return string The result as string
     */
    public function channelDiscover(array $opts = array(), array $params = array());

    /**
     * Checks if the channel with the passed name exists.
     *
     * @param string $channelName The channel's name to check
     * @return boolean TRUE if the channel exists, else FALSE
     */
    public function channelExists($channelName);

    /**
     * Returns an array with all packages of the channel
     * with the passed name.
     *
     * @param string $channelName The channel's name to return the packages for
     * @return array The package list
     */
    public function listPackages($channelName);

    /**
     * Checks if the package with the passed name exists for
     * the channel with the also passed alias.
     *
     * @param string $packageName The package name to check
     * @param string $alias The channel's alias of the package
     * @return boolean TRUE if the package exists, else FALSE
     */
    public function packageExists($packageName, $alias);

	/**
	 * Returns the information for a remote package
	 * by using REST webservices.
	 *
	 * @param string $packageName
	 * 		The name of the package to return the information for
	 * @param string $channel
	 * 		The channel (URL) of the package to return the information for
	 * @return array The package information as array
	 */
	public function packageInfo($packageName, $channel);

	/**
	 * Returns the initialize PEAR_PackageFile_v2 instance.
	 *
	 * @param string $package
	 * 		The path to the package file
	 * @return PEAR_PackageFile_v2 The initialized PEAR package file
	 */
	public function packageFile($package);

	/**
     * Sets username for a channel in Pear config.
     *
	 * @param string $username
	 * 		The name of the user to set in config
	 * @param string $channel
	 * 		The channel (URL) of the package to set in config
	 * @param string $layer
	 * 		The layer to set the username for
     * @return string The result as string
     */
    public function setUsername($username, $channel, $layer = 'user');

    /**
     * Sets password for a channel in Pear config.
     *
	 * @param string $password
	 * 		The password of the user to set in config
	 * @param string $channel
	 * 		The channel (URL) of the package to set in config
	 * @param string $layer
	 * 		The layer to set the password for
     * @return string The result as string
     */
    public function setPassword($password, $channel, $layer = 'user');

	/**
     * Sets username for a channel in Pear config
     *
	 * @param string $channel
	 * 		The channel (URL) of the package to set in config
	 * @param string $layer
	 * 		The layer to return the password for
     * @return string The result as string
     */
    public function getUsername($channel, $layer = 'user');

    /**
     * Sets password for a channel in Pear config
     *
	 * @param string $channel
	 * 		The channel (URL) of the package to set in config
	 * @param string $layer
	 * 		The layer to return the password for
     * @return string The result as string
     */
    public function getPassword($channel, $layer = 'user');

}