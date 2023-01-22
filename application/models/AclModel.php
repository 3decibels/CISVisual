<?php

/**
 * AclModel.php
 * Access Control List
 * Provides user permission role classifications
 */

class AclUserModel
{
	//The ACL object
	protected $_acl;
	//User ACL role
	protected $_role;

	/**
	 * Function to setup ACL for use
	 * Must be called before all other ACL functions
	 */
	public function aclSetup($role = 'guest')
	{
		$this->_acl = new Zend_Acl();

		//==================================================
		//Setup roles

		//The standard role, the guest
		$this->_acl->addRole(new Zend_Acl_Role('guest'));

		//Members inherit from guests
		$this->_acl->addRole(new Zend_Acl_Role('member'), 'guest');

		//Moderators inherit from customers
		$this->_acl->addRole(new Zend_Acl_Role('moderator'), 'customer');

		//Administrators and the founder are gods
		$this->_acl->addRole(new Zend_Acl_Role('admin'));
		$this->_acl->addRole(new Zend_Acl_Role('founder'));


		//==================================================
		//Setup resources

		$this->_acl->add(new Zend_Acl_Resource('adminConsole'));


		//==================================================
		//Mix until useful...

		//Guests can view anything
		$this->_acl->allow('guest', null, 'view');

		//Well, not the admin console
		$this->_acl->deny('guest', 'adminConsole', 'view'); 

		//Mods and up can see the admin console
		$this->_acl->allow('moderator', 'adminConsole', 'view');

		//Members can submit on all
		$this->_acl->allow('member', null, 'submit');

		//Admins and the founder can do it all
		$this->_acl->allow('admin');
		$this->_acl->allow('founder');


		//==================================================
		//Now we store the current user's role
		$this->_role = $role;
		
	}


	/**
	 * Function to check current user permission
	 */
	public function checkPermission($privilege, $resource = null)
	{
		return $this->_acl->isAllowed($this->_role, $resource, $privilege);
	}
}
?>
