<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ZonesCI library
 * 
 * Allows integrating user authentication to existing project.
 * 
 * ZonesCI allows you to:
 * 
 * -add user roles to existing projects
 * -skip database alterations
 * -add different roles 
 * -check pages for logged groups of users
 * 
 * Groups are defined as permissions - each page
 * can be viewed by list of groups. Every user could
 * participate in more than one groups. This does
 * adding and removing rights to users easier and
 * many-to-many relation doesn't require group_id alteration
 * in 'users' table
 * 
 * 
 */
class Zonesci
{
	var $CI;
	var $users;
	var $groups;
	var $user_groups;
	
	/**
	 *  username and password columns as defined in DB
	 */
	var $user_id;
	var $user_login;
	var $user_password;

	/**
	 * Library constructor - 
	 * load CI object and database meta data
	 * 
	 */
	function Zonesci()
	{
		$this->CI =& get_instance();
		$this->CI->load->config('zonesci.php');
		
		$this->users = $this->CI->config->item('z_users');
		$this->groups = $this->CI->config->item('z_groups');
		$this->user_groups = $this->CI->config->item('z_user_groups');
		$this->user_login = $this->CI->config->item('z_user_login');
		$this->user_password = $this->CI->config->item('z_user_password');
		$this->user_id = $this->CI->config->item('z_user_id');
	}

	/**
	 * Create a user account
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	function create($user = '', $password = '', $auto_login = true) {
		//Make sure account info was sent
		if($user == '' OR $password == '') {
			return false;
		}
		
		//Check against user table
		$this->CI->db->where($this->user_login, $user); 
		$query = $this->CI->db->getwhere($this->users);
		
		if ($query->num_rows() > 0) {
			//username already exists
			return false;
			
		} else {
			//Encrypt password
			$password = md5($password);
			
			//Insert account into the database
			$data = array(
						$this->user_login => $user,
						$this->user_password => $password
					);
			$this->CI->db->set($data); 
			if(!$this->CI->db->insert($this->users)) {
				//There was a problem!
				return false;						
			}
			$user_id = $this->CI->db->insert_id();
			
			//Automatically login to created account
			if($auto_login) {		
				//Destroy old session
				$this->CI->session->sess_destroy();
				
				//Create a fresh, brand new session
				$this->CI->session->sess_create();
				
				//Set session data
				$this->CI->session->set_userdata(array($this->user_id => $user_id, $this->user_login => $user));
				
				//Set logged_in to true
				$this->CI->session->set_userdata(array('logged_in' => true));			
			
			}
			
			//Login was successful			
			return true;
		}

	}

	/**
	 * Delete user
	 *
	 * @access	public
	 * @param integer
	 * @return	bool
	 */
	function delete($user_id) {
		if(!is_numeric($user_id)) {
			//There was a problem
			return false;			
		}

		if($this->CI->db->delete($this->users, array($this->user_id => $user_id))) {
			//Database call was successful, user is deleted
			return true;
		} else {
			//There was a problem
			return false;
		}
	}


	/**
	 * Login and sets session variables
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	function login($user = '', $password = '') {
		//Make sure login info was sent
		if($user == '' OR $password == '') {
			return false;
		}

		/*
		 * Check against user table
		 * join Many-to-Many groups table and Groups table, too
		 * and check permissions (add to session)
		 */
		
		// DEBUG ON
		
		// TODO: improper select statement!!!! fix joins
		$this->CI->db->_compile_select();
		
		$this->CI->db->where($this->user_login, $user);
		$this->CI->db->join($this->user_groups, $this->users. '.'. $this->user_id. '='. $this->user_groups. '.'.$this->user_id  , 'left');
		$this->CI->db->join($this->groups, $this->groups. '.group_id = '. $this->user_groups. '.group_id', 'left');
		 
		$query = $this->CI->db->getwhere($this->users);
		
		// DEBUG OFF
		$sql_joins = $this->CI->db->last_query();
		
		if ($query->num_rows() > 0) {
			$row = $query->row_array(); 
			
			//Check against password
			if(md5($password) != $row[$this->user_password]) {
				return false;
			}
			
			//Destroy old session
			$this->CI->session->sess_destroy();
			
			//Create a fresh, brand new session
			$this->CI->session->sess_create();
			
			// extract groups in array
			$groups = array();
			foreach($query->result_array() as $res) {
				$groups[] = $res['group_name'];
			}
			
			//Set session data
			$this->CI->session->set_userdata(array('id' => $row[$this->user_id]));
			$this->CI->session->set_userdata(array('username' => $row[$this->user_login]));
			$this->CI->session->set_userdata(array('group' => $groups));
			
			// DEBUG ON
			$this->CI->session->set_userdata(array('sql' => $sql_joins));
			$this->CI->session->set_userdata(array('resarray' => $query->result_array()));
			
			// DEBUG OFF
			$this->CI->session->set_userdata(array('logged_in' => true));			
			
			//Login was successful			
			return true;
		} else {
			//No database result found
			return false;
		}	

	}

	/**
	 * Logout user
	 *
	 * @access	public
	 * @return	void
	 */
	function logout() {
		//Destroy session
		$this->CI->session->sess_destroy();
	}
	
	function check($groups = '') 
	{
		if( ! $this->CI->session->userdata('logged_in')) 
		{
			show_404('page');
			return;
		}
		else 
		{
			return $this->does_allow($groups);
		}
	}
	
	private function does_allow($groups = '') {
		if($groups == '') {
			return false;
		}
		$sess_groups = $this->CI->session->userdata('group');
		if(is_array($groups)) {
				foreach($groups as $group) {
					if(in_array($group, $sess_groups)) {
						return true;
					}
				}
		}
		else {
			if(in_array($groups, $sess_groups)) {
				return true;
			}
		}
		return false;
	}
}

/* End of file Zonesci.php */
/* Location: ./system/application/libraries/Zonesci.php */
