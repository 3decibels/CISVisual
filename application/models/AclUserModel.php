<?php

/**
 * AclModel.php
 * Access Control List
 * Provides user permission role classifications
 */
require_once('SmfUserModel.php');

class AclUserModel extends SmfUserModel
{
	//The ACL object
	protected $_acl;
	//User ACL role
	protected $_role;
	//Is the ACL set up?
	protected $_aclFlag = false;

	/**
	 * Function to setup ACL for use
	 * Must be called before all other ACL functions
	 */
	public function aclSetup()
	{
		$this->_acl = new Zend_Acl();

		//==================================================
		//Setup roles

		//The standard role, the guest
		$this->_acl->addRole(new Zend_Acl_Role('guest'));

		//Members inherit from guests
		$this->_acl->addRole(new Zend_Acl_Role('member'), 'guest');

		//Restricted members are only slightly better than guests
		$this->_acl->addRole(new Zend_Acl_Role('restricted'), 'guest');

		//Banned people are trash
		$this->_acl->addRole(new Zend_Acl_Role('banned'));

		//Staff inherit from members
		$this->_acl->addRole(new Zend_Acl_Role('moderator'), 'member');

		//Administrators are god
		$this->_acl->addRole(new Zend_Acl_Role('admin'));


		//==================================================
		//Setup resources

		$this->_acl->add(new Zend_Acl_Resource('vote'));
		$this->_acl->add(new Zend_Acl_Resource('walkthrough'));
		$this->_acl->add(new Zend_Acl_Resource('title'));
		$this->_acl->add(new Zend_Acl_Resource('group'));
		$this->_acl->add(new Zend_Acl_Resource('adminConsole'));


		//==================================================
		//Mix until useful...

		//Guests can view anything
		$this->_acl->allow('guest', null, 'view');
		//Well, not admin actions
		$this->_acl->deny('guest', 'adminConsole', 'view');

		//Members can submit on all
		$this->_acl->allow('member', null, 'submit');

		//Staff can edit anything
		$this->_acl->allow('moderator', null, 'edit');
		$this->_acl->allow('moderator', 'title', 'approve');
		$this->_acl->allow('moderator', 'adminConsole', 'view');

		//Admins can do it all
		$this->_acl->allow('admin');

		//Restricted members can only submit votes and do what guests can do
		$this->_acl->allow('restricted', 'vote', 'submit');


		//==================================================
		//Now we grab and store the current user's role
		if ($this->checkLogged())
		{
			//Retrieve and set the user role
			$db = Zend_Registry::get('database');
			$this->_role = $db->fetchOne('SELECT userRole FROM cis_user_acl WHERE ID_MEMBER = ? LIMIT 1', $this->getUserID());
		}
		else
		{
			//Set the role as 'guest'
			$this->_role = 'guest';
		}

		$this->_aclFlag = true;
	}


	/**
	 * Function to check current user permission
	 */
	public function checkPermission($privilege = null, $resource = null)
	{
		if (false === $this->_aclFlag)
		{
			$this->aclSetup();
		}
		return $this->_acl->isAllowed($this->_role, $resource, $privilege);
	}
}
?>
