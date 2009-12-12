<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * ----------------------------------------------------------------------------
 * This is ZonesCI configuration file
 * author: mpeshev @ http://peshev.net
 * ----------------------------------------------------------------------------
 */

	/**
	 *  Users and groups tables in database
	 **/

	$config['z_groups'] = 'groups';
	$config['z_users'] = 'users';
	$config['z_user_groups'] = 'user_groups';
	
	// ID, login name and password column names
	$config['z_user_id'] = 'user_id';
	$config['z_user_login'] = 'email';
	$config['z_user_password'] = 'pwd';
	
/* End of file zonesci.php */
/* Location: ./system/application/config/zonesci.php */