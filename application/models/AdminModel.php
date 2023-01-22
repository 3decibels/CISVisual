<?php

class AdminModel
{
	/**
	 * Function to get admin console main stats
	 */
	public function getIndexStats()
	{
		//Setup variables
		$db = Zend_Registry::get('database');
		$edits = new EditModel;

		//Get data
		$data['pending'] = $db->fetchOne('SELECT COUNT(*) FROM cis_titles WHERE titleStatus = \'pending\'');
		$data['users'] = $db->fetchOne('SELECT COUNT(*) FROM cis_user_acl');
		$data['transactions'] = $edits->getTransactionCount();
		$data['edits'] = $edits->getEditCount();

		//Check data
		if (empty($data['pending']))
		{
			$data['pending'] = 0;
		}
		if (empty($data['users']))
		{
			$data['users'] = 0;
		}

		//Return data
		return $data;
	}


	/**
	 * Function to grab a list of title to be approved
	 */
	public function getPendingTitles($limit = null)
	{
		$db = Zend_Registry::get('database');

		$qry = 'SELECT t.titleID, t.titleName, t.titleLastUpdate, m.memberName
				FROM cis_titles AS t INNER JOIN smf_members AS m ON t.ID_MEMBER = m.ID_MEMBER
				WHERE t.titleStatus = \'pending\' ORDER BY t.titleID';
		if (null != $limit)
		{
			$qry .= ' LIMIT ' . addslashes($limit);
		}

		$data = $db->fetchAll($qry);

		return $data;
	}


	/**
	 * Function to process title reviews
	 */
	public function getReviewData($titleID)
	{
		$db = Zend_Registry::get('database');

		//Initialize array
		$data = array();
		//Get basic data
		$data = $db->fetchRow('SELECT * FROM cis_titles WHERE titleID = ? LIMIT 1', $titleID);
		//Get alternate titles
		$data['alternates'] = $db->fetchAll('SELECT titleName, alternateID FROM cis_title_alternates
			WHERE titleStatus = \'pending\' AND titleID = ?', $titleID);
		//Get submitted image data, if any
		$data['image'] = $db->fetchOne('SELECT imageFile FROM cis_images WHERE titleID = ? ORDER BY imageFile LIMIT 1', $titleID);

		return $data;
	}


	/**
	 * Function to verify that a titleID is actually in existance and pending
	 */
	public function checkPending($titleID)
	{
		$db = Zend_Registry::get('database');
		$data = $db->fetchOne('SELECT COUNT(*) FROM cis_titles WHERE titleID = ? AND titleStatus = \'pending\'', $titleID);

		//Return true for yes, false for no
		if (1 == $data)
		{
			return true;
		}
		return false;
	}


	/**
	 * Function to validate a mod review
	 */
	public function validateReview(&$values)
	{
		$errors = array();

		//Image errors
		if (isset($values['image']) && !isset($values['imageApprove']) && 'approve' == $values['titleApprove'])
		{
			$errors['imageApprove'][] = 'Please review the image.';
		}
		//Denial reason errors
		if (isset($values['titleApprove']) && 'deny' == $values['titleApprove'] && !isset($values['reason']))
		{
			$errors['reason'][] = 'Please give a reason for denying the title.';
		}
		//Review action
		if (!isset($values['titleApprove']))
		{
			$errors['titleApprove'][] = 'Please select a review action.';
		}

		if (!empty($errors))
		{
			$errors['generic'][] = 'Form submitted incomplete. Sections are highlighted below.';
		}

		return $errors;
	}


	/**
	 * Function to process a title review
	 */
	public function processReview($values)
	{
		//Main title approval switch
		switch ($values['titleApprove'])
		{
		case 'approve':
			$data['result'] = $this->approveTitle($values);
			if ('SUCCESS' == $data['result'])
			{
				$data['message'] = 'The title has been approved and processed under the normal method.';
			}	
			break;
		case 'deny':
			//TODO: Mail the submitting member the reason
			$data['result'] = $this->denyTitle($values);
			if ('SUCCESS' == $data['result'])
			{
				$data['message'] = 'The title has been denied and removed from the database.';
			}
			break;
		case 'hold':
			//Setting the result to 'HOLD' results in an image processing bypass
			$data['result'] = 'HOLD';
			$data['message'] = 'The title has been held in the queue for review by another mod.';
		}

		//Title image approval
		if ('SUCCESS' == $data['result'] && isset($values['image']))
		{
			if ('approve' == $values['titleApprove'] && 'accept' == $values['imageApprove'])
			{
				//Approve image if both title and image are accepted
				$data['result'] = $this->approveImage($values['image'], $values['titleID']);
			}
			elseif ('delete' == $values['imageApprove'] || 'deny' == $values['titleApprove'])
			{
				//Delete image if either image or title are denied
				$data['result'] = $this->deleteImage($values['image']);
			}

			//Replace message if image processing failed
			if ('SUCCESS' != $data['result'])
			{
				$data['message'] = 'The title has been processed, but an error occured while trying to process the image.';
			}
		}

		//Unset image processing bypass if title held
		if ('HOLD' == $data['result'])
		{
			$data['result'] = 'SUCCESS';
		}

		return $data;
	}


	/**
	 * Function to approve a title
	 */
	public function approveTitle($values)
	{
		$db = Zend_Registry::get('database');

		//Update array for titles
		$update = array('titleStatus' => 'active');
		$titleWhere = 'titleID = ' . $values['titleID'];

		try {		
			$db->beginTransaction();
			//Start by changing the title status
			$db->update('cis_titles', $update, $titleWhere);
			//Alternates
			foreach($values['alt'] as $alt)
			{
				$altWhere = 'alternateID = ' . $db->quote($alt);
				//Same "titleStatus = active" update will suffice
				$db->update('cis_title_alternates', $update, $altWhere);
				unset($altWhere);
			}
			//Remove all unapproved alternates
			$delete[] = 'titleID = ' . $values['titleID'];
			$delete[] = 'titleStatus = \'pending\'';
			$db->delete('cis_title_alternates', $delete);
			$db->commit();
		} catch (Zend_Db_Exception $e) {
			$db->rollBack();
			$mssg = $e->getMessage();
			$logger = Zend_Registry::get('logger');
			$logger->err('Title approval failure on TID#' . $values['titleID'] . ': ' . $mssg . '.');
			return 'FAIL';
		}
		
		//We'll index the title here
//		$indexer = new LuceneModel;			//TODO: WTF is up with this?
//		$indexer->indexTitleAll($values['titleID']);

		return 'SUCCESS';
	}


	/**
	 * Function to deny a title
	 */
	public function denyTitle($values)
	{
		//Set up variables
		$db = Zend_Registry::get('database');
		$where = 'titleID = ' . $db->quote($values['titleID']);

		try {
			$db->delete('cis_titles', $where);
			$db->delete('cis_images', $where);
			$db->delete('cis_title_alternates', $where);
		} catch (Zend_Db_Exception $e) {
			$mssg = $e->getMessage();
			$log = Zend_Registry::get('logger');
			$log->err('Failure to remove TID#' . $values['titleID'] . ' from database: ' . $mssg);
			return 'FAIL';
		}

		return 'SUCCESS';
	}

	/**
	 * Function to approve an image
	 * Deletes all other images for a title as well
	 */
	public function approveImage($image, $titleID)
	{
		//Set up variables
		$db = Zend_Registry::get('database');
		$config = Zend_Registry::get('config');
		$basePath = $config->image->path;

		//Rename image and move to title_images
		$extension = substr($image, -3, 3);
		$newImage = $titleID . '.' . $extension;
		$old = $basePath . '/pending/' . $image;
		$new = $basePath . '/' . $newImage;

		try {
			rename($old, $new);
		} catch (Exception $e) {
			$log = Zend_Registry::get('logger');
			$log->err('Failure to move image file for TID#' . $titleID);
			return 'FAIL';
		}

		//Update the database
		$update = array(
			'titleImage'		=> true,
			'titleImageType'	=> $extension
		);
		$where = 'titleID = ' . $db->quote($titleID);

		try {
			//Updates main title entry
			$db->update('cis_titles', $update, $where);
			//Fetches all the other file names for submitted images for a titleID
			$extImages = $db->fetchAll('SELECT imageFile FROM cis_images WHERE titleID = ?', $titleID);
			//Removes all other entries of same titleID from the images table
			$db->delete('cis_images', $where);
		} catch (Zend_Db_Exception $e) {
			$mssg = $e->getMessage();
			$log = Zend_Registry::get('logger');
			$log->err('Failure to process database update for image file approval for TID#' . $titleID . ': ' . $mssg);
			return 'FAIL';
		}

		//Destroy all other submitted images for the title
		foreach ($extImages as $ext)
		{
			//Make sure we're not dealing with the already-processed accepted image
			if ($ext['imageFile'] != $image)
			{
				try {
					unlink($basePath . '/pending/' . $ext['imageFile']);
				} catch (Exception $e) {
					$log = Zend_Registry::get('logger');
					$log->err('Failure to unlink pending image "' . $ext['imageFile'] . '"');
					return 'FAIL';
				}
			}
		}

		return 'SUCCESS';
	}


	/**
	 * Function to deny an image
	 */
	public function deleteImage($image)
	{
		//Set up variables
		$db = Zend_Registry::get('database');
		$config = Zend_Registry::get('config');
		$basePath = $config->image->path;

		//Unlink image
		try {
			unlink($basePath . '/pending/' . $image);
		} catch (Exception $e) {
			$log = Zend_Registry::get('logger');
			$log->err('Failure to unlink pending image "' . $ext['imageFile'] . '"');
			return 'FAIL';
		}
		
		//Remove entry from database
		$where = 'imageFile = ' . $db->quote($image);
		try {
			$db->delete('cis_images', $where);
		} catch (Zend_Db_Exception $e) {
			$log = Zend_Registry::get('logger');
			$log->err('Failure to remove pending image entry from database: "' . $ext['imageFile'] . '"');
			return 'FAIL';
		}

		return 'SUCCESS';
	}

}
