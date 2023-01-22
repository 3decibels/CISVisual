<?php

class RateModel
{
	/**
	 * Function to grab stored rating data for a single user/title combo
	 */
	public function getRating($userID, $titleID)
	{
		//Get our basic rating data
		$db = Zend_Registry::get('database');
		$data = $db->fetchRow('SELECT ratingWeight, ratingMethod FROM cis_title_ratings WHERE titleID = ? AND ID_MEMBER = ?',
			array($titleID, $userID));

		if (is_array($data))
		{
			if ('batch' == $data['ratingMethod'])
			{
				//Get batch data and combine
				$secondaryData = $db->fetchRow('SELECT ratingStory, ratingCharacter, ratingArt, ratingMusic, ratingVoice
					FROM cis_title_ratings_batch WHERE titleID = ? AND ID_MEMBER = ?',
					array($titleID, $userID));
				$data = array_merge($data, $secondaryData);
				$data['ratingTotal'] = $data['ratingWeight'];
			}
			else
			{
				//Supply empty batch data to appease the PHP empty argument notices
				$data = array_merge($data, array(
					'ratingStory'		=> null,
					'ratingCharacter'	=> null,
					'ratingArt'		=> null,
					'ratingMusic'		=> null,
					'ratingVoice'		=> null,
					'ratingTotal'		=> null
				));
			}
		}

		return $data;
	}


	/**
	 * Function to validate a basic rating
	 */
	public function validateBasic(& $values)
	{
		$errors = array();
		$db = Zend_Registry::get('database');
		
		$titleCheck = $db->fetchOne('SELECT COUNT(*) FROM cis_titles WHERE titleID = ? LIMIT 1', $values['titleID']);
		//Title errors
		if (empty($titleCheck))
		{
			$errors['ratingBasic'][] = 'Title does not exist.';
		}
		//Rating errors
		if (! ctype_digit($values['ratingBasic']) || $values['ratingBasic'] > 5 || $values['ratingBasic'] < 1)
		{
			$errors['ratingBasic'][] = 'Not a valid rating.';
		}
		//Auth error
		if ($values['auth'] != $_SESSION['cisvtoken'])
		{
			$errors['generic'][] = 'This function has been called indirectly. Please try again.';
		}

		return $errors;
	}


	/**
	 * Function to validate a batch rating
	 */
	public function validateBatch(& $values)
	{
		$errors = array();
		$db = Zend_Registry::get('database');

		//Ratings to be checked for submission
		$ratingTypes = array('ratingStory','ratingCharacter','ratingArt','ratingMusic');
		
		$titleCheck = $db->fetchOne('SELECT COUNT(*) FROM cis_titles WHERE titleID = ? LIMIT 1', $values['titleID']);
		//Title errors
		if (empty($titleCheck))
		{
			$errors['ratingBasic'][] = 'Title does not exist.';
		}
		//Rating errors
		foreach ($ratingTypes as $type)
		{
			if (! ctype_digit($values[$type]) || $values[$type] > 5 || $values[$type] < 1)
			{
				$errors[$type][] = 'The following must be completed for a batch rating.';
			}
		}
		//Rating total is a special case
		if ((! ctype_digit($values['ratingTotal']) && 'auto' != $values['ratingTotal']))
		{
			$errors['ratingTotal'][] = 'The following must be completed for a batch rating.';
		}
		//Auth error
		if ($values['auth'] != $_SESSION['cisvtoken'])
		{
			$errors['generic'][] = 'This function has been called indirectly. Please try again.';
		}

		return $errors;
	}


	/**
	 * Function to validate a removal
	 */
	public function validateRemove(& $values)
	{
		$errors = array();
		$db = Zend_Registry::get('database');

		$titleCheck = $db->fetchOne('SELECT COUNT(*) FROM cis_titles WHERE titleID = ? LIMIT 1', $values['titleID']);
		//Title errors
		if (empty($titleCheck))
		{
			$errors['removeRating'][] = 'Title does not exist.';
		}
		//Check the damn box error
		if ('true' != $values['removeRating'])
		{
			$errors['removeRating'][] = 'The box must be checked to prevent accidental removal.';
		}
		//Auth error
		if ($values['auth'] != $_SESSION['cisvtoken'])
		{
			$errors['generic'][] = 'This function has been called indirectly. Please try again.';
		}

		return $errors;
	}



	/**
	 * Function to process a basic rating
	 */
	public function processBasic($values)
	{
		$db = Zend_Registry::get('database');

		//Assemble data
		$ratingTable = 'cis_title_ratings';
		$ratingData = array(
			'titleID'	=> $values['titleID'],
			'ID_MEMBER'	=> $values['userID'],
			'ratingWeight'	=> $values['ratingBasic'],
			'ratingMethod'	=> 'basic'
		);
		$ratingWhere[] = 'titleID = ' . $values['titleID'];
		$ratingWhere[] = 'ID_MEMBER = ' . $values['userID'];
		$batchTable = 'cis_title_ratings_batch';

		//Have we submitted a rating?
		$priorMethod = $db->fetchOne('SELECT ratingMethod FROM cis_title_ratings WHERE titleID = ? AND ID_MEMBER = ? LIMIT 1',
			array($values['titleID'], $values['userID']));

		if (! $priorMethod)
		{
			//Just insert the new rating
			try {
				$db->insert($ratingTable, $ratingData);
				return 'SUCCESS';
			} catch (Zend_Exception $e) {
				$mssg = $e->getMessage();				
				$logger = Zend_Registry::get('logger');
				$logger->err('Falure to insert title rating (none -> basic): ' . $mssg);
				return 'FAIL';
			}
		}
		elseif ('basic' == $priorMethod)
		{
			//Update the prior rating
			try {
				$db->update($ratingTable, $ratingData, $ratingWhere);
				return 'SUCCESS';
			} catch (Zend_Exception $e) {
				$mssg = $e->getMessage();				
				$logger = Zend_Registry::get('logger');
				$logger->err('Falure to insert title rating (basic -> basic): ' . $mssg);
				return 'FAIL';
			}
		}
		elseif ('batch' == $priorMethod)
		{
			//Update the rating and remove the batch entry
			try {
				$db->beginTransaction();
				$db->update($ratingTable, $ratingData, $ratingWhere);
				$db->delete($batchTable, $ratingWhere);
				$db->commit();
				return 'SUCCESS';
			} catch (Zend_Exception $e) {
				$db->rollBack();
				$mssg = $e->getMessage();				
				$logger = Zend_Registry::get('logger');
				$logger->err('Falure to insert title rating (batch -> basic): ' . $mssg);
				return 'FAIL';
			}
		}
		//Should never get here
		$logger = Zend_Registry::get('logger');
		$logger->err('Title rating insert (basic) passed through model processing function without transacting. Member #' . $values['userID'] . ', Title #' . $values['titleID'] . '.');
		return 'FAIL';
	}


	/**
	 * Function to process a basic rating
	 */
	public function processBatch(& $values)
	{
		$db = Zend_Registry::get('database');

		//Auto-compute overall rating if necessary
		if ('auto' == $values['ratingTotal'])
		{
			$count = 4;
			$total = $values['ratingStory'] + $values['ratingCharacter'] + $values['ratingArt'] + $values['ratingMusic'];
			if (! empty($values['ratingVoice']))
			{
				$total += $values['ratingVoice'];
				$count ++;
			}
			//Compute the rounded average
			$values['ratingTotal'] = round($total / $count);
		}
		
		//Assign voice to null if not present
		if (empty($values['ratingVoice']))
		{
			$values['ratingVoice'] = null;
		}

		//Assemble data
		$basicTable = 'cis_title_ratings';
		$basicData = array(
			'titleID'	=> $values['titleID'],
			'ID_MEMBER'	=> $values['userID'],
			'ratingWeight'	=> $values['ratingTotal'],
			'ratingMethod'	=> 'batch'
		);
		$batchTable = 'cis_title_ratings_batch';
		$batchData = array(
			'titleID'		=> $values['titleID'],
			'ID_MEMBER'		=> $values['userID'],
			'ratingStory'		=> $values['ratingStory'],
			'ratingCharacter'	=> $values['ratingCharacter'],
			'ratingArt'		=> $values['ratingArt'],
			'ratingMusic'		=> $values['ratingMusic'],
			'ratingVoice'		=> $values['ratingVoice']
		);
		$ratingWhere[] = 'titleID = ' . $values['titleID'];
		$ratingWhere[] = 'ID_MEMBER = ' . $values['userID'];


		//Have we submitted a rating?
		$priorMethod = $db->fetchOne('SELECT ratingMethod FROM cis_title_ratings WHERE titleID = ? AND ID_MEMBER = ? LIMIT 1',
			array($values['titleID'], $values['userID']));

		if (! $priorMethod)
		{
			//Just insert the new rating
			try {
				$db->beginTransaction();
				$db->insert($basicTable, $basicData);
				$db->insert($batchTable, $batchData);
				$db->commit();
				return 'SUCCESS';
			} catch (Zend_Exception $e) {
				$mssg = $e->getMessage();				
				$logger = Zend_Registry::get('logger');
				$logger->err('Falure to insert title rating (none -> batch): ' . $mssg);
				return 'FAIL';
			}
		}
		elseif ('basic' == $priorMethod)
		{
			//Update the basic rating and insert the batch
			try {
				$db->beginTransaction();
				$db->update($basicTable, $basicData, $ratingWhere);
				$db->insert($batchTable, $batchData);
				$db->commit();
				return 'SUCCESS';
			} catch (Zend_Exception $e) {
				$mssg = $e->getMessage();				
				$logger = Zend_Registry::get('logger');
				$logger->err('Falure to insert title rating (basic -> batch): ' . $mssg);
				return 'FAIL';
			}
		}
		elseif ('batch' == $priorMethod)
		{
			//Update both the basic and batch entries
			try {
				$db->beginTransaction();
				$db->update($basicTable, $basicData, $ratingWhere);
				$db->update($batchTable, $batchData, $ratingWhere);
				$db->commit();
				return 'SUCCESS';
			} catch (Zend_Exception $e) {
				$db->rollBack();
				$mssg = $e->getMessage();				
				$logger = Zend_Registry::get('logger');
				$logger->err('Falure to insert title rating (batch -> batch): ' . $mssg);
				return 'FAIL';
			}
		}
		//Should never get here
		$logger = Zend_Registry::get('logger');
		$logger->err('Title rating insert (batch) passed through model processing function without transacting. Member #' . $values['userID'] . ', Title #' . $values['titleID'] . '.');
		return 'FAIL';
	}


	/**
	 * Function to remove a rating
	 */
	public function processRemove($values)
	{
		$db = Zend_Registry::get('database');
		$basicTable = 'cis_title_ratings';
		$batchTable = 'cis_title_ratings_batch';
		$where[] = 'titleID = ' . $values['titleID'];
		$where[] = 'ID_MEMBER = ' . $values['userID'];

		try {
			$db->beginTransaction();
			$db->delete($basicTable, $where);
			$db->delete($batchTable, $where);
			$db->commit();
			return 'SUCCESS';
		} catch (Zend_Exception $e) {
			$db->rollBack();
			$mssg = $e->getMessage();				
			$logger = Zend_Registry::get('logger');
			$logger->err('Falure to remove title rating: ' . $mssg);
			return 'FAIL';
		}
	}
}

?>
