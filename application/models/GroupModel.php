<?php

class GroupModel
{
	/**
	 * Function to validate a group addition
	 */
	public function validateAdd(&$values)
	{
		//Setup variables
		$db = Zend_Registry('database');
		$errors = array();

		//Group name errors
		if (!isset($values['groupName']) || 0 == strlen(trim($values['groupName'])))
		{
			$errors['groupName'][] = 'Please enter a name for this group.';
			unset($values['groupName']);
		}
		elseif ($db->fetchOne('SELECT groupName FROM cis_groups WHERE groupName = ? LIMIT 1', $values['groupName']))
		{
			$errors['groupName'][] = 'Group name has already been submitted.';
			unset($values['groupName']);
		}
		//Description errors
		if (!isset($values['groupDescription']) || 0 == strlen(trim($values['groupDescription'])))
		{
			$errors['groupDescription'][] = 'Please enter a description.';
			unset($values['groupDescription']);
		}
		//Leader errors
		if (!isset($values['groupLeader']) || 1 < $values['groupLeader'] || 0 > $values['groupLeader'])
		{
			$errors['groupLeader'][] = 'Please specify your group position.';
			unset($values['groupLeader']);
		}

		return $errors;
	}
}
