<?php

class WalkthroughModel
{
	/**
	 * Function to validate a walkthrough addition
	 */
	public function validateAdd(&$values)
	{

		//Create empty error array
		$errors = array();
		
		//Walkthrough attribution
		if ('self' != $values['walkAttrib'] && 'anon' != $values['walkAttrib'])
		{
			$errors['walkAttrib'][] = 'Please indicate if you would like to be credited with this submission.';
		}

		//Submission errors
		switch ($values['walkSubmit']) {
		case 'text':
			$textLength = strlen(trim($values['walkText']));
			if (100 > $textLength)
			{
				$errors['walkText'][] = 'Walkthrough must be at least 100 characters in length.';
			}
			if (65000 < $textLength)
			{
				$errors['walkText'][] = 'Walkthrough must be less than 65,000 characters in length.';
			}
			break;
		case 'file':
			if (!is_uploaded_file($_FILES['walkFile']['tmp_name']))
			{
				$errors['walkFile'][] = 'Please select a file to upload.';
			}
			elseif ('text/plain' != mime_type($_FILES['walkFile']['tmp_name']))
			{
				$errors['walkFile'][] = 'File must be plaintext (.txt) only.';
			}
			elseif (65000 < $_FILES['walkFile']['size'])
			{
				$errors['walkFile'][] = 'File size must be less than 65KB';
			}
			break;
		default:
			$errors['walkSubmit'][] = 'Please select a valid submission method.';
			break;
		}

		return $errors;
	}


	/**
	 * Function to add a walkthrough to the database
	 */
	public function processAdd($values)
	{
		//Retrieve database object
		$db = Zend_Registry::get('database');

		//Determine how to get the walkthrough text and execute
		switch ($values['walkSubmit']) {
		case 'text':
			$text = $values['walkText'];
			$type = 'text';
			break;
		case 'file':
			$text = file_get_contents($_FILES['walkFile']['tmp_name']);
			$type = 'text';
			break;
		default:
			$log = Zend_Registry::get('logger');
			$log->err('Walkthrough add command passed through switch construct without trasacting.');
			return 'FAIL';
			break;
		}

		//Do we have a title or de we need to create one
		if (empty($values['walkTitle']))
		{
			$title = $db->fetchOne('SELECT titleName FROM cis_titles WHERE titleID = ? LIMIT 1', array($values['titleID']));
			$values['walkTitle'] = $title . ' Walkthrough';
		}

		//Setup add data
		$walkTable = 'cis_title_walkthroughs';
		$walkData = array(
			'titleID'		=> $values['titleID'],
			'ID_MEMBER'		=> $values['userID'],
			'walkTitle'		=> $values['walkTitle'],
			'walkAnonSubmission'	=> ('anon' == $values['walkAttrib'])? '1' : '0',
			'walkType'		=> $type,
			'walkText'		=> $text
		);

		//Execute insertion
		try {
			$db->insert($walkTable, $walkData);
		} catch (Zend_Db_Exception $e) {
			$mssg = $e->getMessage();
			$log = Zend_Registry::get('logger');
			$log->err('Failure to insert new walkthrough into database: ' . $mssg);
			return 'FAIL';
		}

		return 'SUCCESS';
	}


	/**
	 * Function to retrieve walkthrough list data
	 * Returns data on successful query.
	 * Returns false if no data was fetched.
	 */
	public function getList($titleID, $userID = null)
	{
		$db = Zend_Registry::get('database');
		//Build the SELECT qry
		$select = $db->select();
		$select->from(array('w' => 'cis_title_walkthroughs'),
			array('walkID', 'walkTitle', 'walkAccessed','walkAnonSubmission'));
		$select->join(array('m' => 'smf_members'),
			'w.ID_MEMBER = m.ID_MEMBER',
			array('memberName'));
		$select->where('titleID = ?', $titleID);
		if (isset($userID))
		{
			$select->where('ID_MEMBER = ?', $userID);
		}
		$select->order('walkDate DESC');

		//Perform query
		$stmt = $db->query($select);
		$data = $stmt->fetchAll();

		//If no result was generated, return false
		if (!$data)
		{
			return false;
		}

		//Return data
		return $data;
	}


	/**
	 * Function to get data on a single walkthrough
	 * Returns an array of data on success
	 * Returns 'FALSE' on failure
	 */
	public function getWalkthrough($walkID)
	{
		$db = Zend_Registry::get('database');
		$data = $db->fetchRow('SELECT walkText, walkTitle FROM cis_title_walkthroughs WHERE walkID = ? LIMIT 1', array($walkID));

		if (!empty($data))
		{
			return $data;
		}
		else
		{
			return false;
		}
	}


	/**
	 * Function to increment a walkthrough's access counter
	 */
	public function incrementCounter($walkID)
	{
		$db = Zend_Registry::get('database');
		$db->query('UPDATE cis_title_walkthroughs SET walkAccessed = walkAccessed + 1 WHERE walkID = ? LIMIT 1', $walkID);
	}
}
