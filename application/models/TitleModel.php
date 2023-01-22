<?php

class TitleModel
{

	/**
	 * Gets any title name when supplied titleID
	 */
	public function getTitleName($titleID)
	{
		$db = Zend_Registry::get('database');
		$data = $db->fetchOne('SELECT titleName FROM cis_titles WHERE titleID = ? LIMIT 1', $titleID);
		return $data;
	}


	/**
	 * Function to validate title addition variables
	 */
	public function validateAdd(& $values)
	{
		$errors = array();
		$db = Zend_Registry::get('database');

		//Set a titleID for name check safety
		if (! isset($values['titleID']))
		{
			$values['titleID'] = 0;
		}

		//Name errors
		if (! strlen(trim($values['titleName'])))
		{
			$errors['titleName'][] = 'A title name is required!';
		}
		if ($db->fetchOne('SELECT titleName FROM cis_titles WHERE titleName = ? AND titleID != ?', array($values['titleName'], $values['titleID']))
			|| $db->fetchOne('SELECT titleName FROM cis_title_alternates WHERE titleName = ? AND titleID != ?',
			array($values['titleName'], $values['titleID'])))
		{
			$errors['titleName'][] = 'Title name is already registered.';
		}
		//Description errors
		if (! strlen(trim($values['titleDescription'])))
		{
			$errors['titleDescription'][] = 'A description is required!';
		}
		//Creator errors
		if (! strlen(trim($values['titleCreator'])))
		{
			$errors['titleCreator'][] = 'A creator is required!';
		}
		//Year errors
		if (! empty($values['titleYear']))
		{
			if (! ctype_digit($values['titleYear']) || strlen($values['titleYear']) != 4)
			{
				$errors['titleYear'][] = 'This is not a valid year.';
			}
			if (ctype_digit($values['titleYear']) && strlen($values['titleYear']) == 4 && $values['titleYear'] < 1985)
			{
				$errors['titleYear'][] = 'Year is too early for a valid release.';
			}
			if (ctype_digit($values['titleYear']) && $values['titleYear'] > strftime('%Y'))
			{
				$errors['titleYear'][] = 'Future releases are not accepted.';
			}
		}
		//Type errors
		if (! isset($values['titleType']) || 'false' === $values['titleType'])
		{
			$errors['titleType'][] = 'The release type is required!';
		}
		//Plot errors
		if (! isset($values['titlePlot']) || 'false' === $values['titlePlot'])
		{
			$errors['titlePlot'][] = 'The plot type is required!';
		}
		//Availablity errors
		if (! isset($values['titleAvailable']) || 'false' === $values['titleAvailable'])
		{
			$errors['titleAvailable'][] = 'The availability is required!';
		}
		//Eroge errors
		if (! isset($values['titleAdult']))
		{
			$errors['titleAdult'][] = 'The eroge content is required!';
			$values['titleAdult'][] = null;
		}
		//Image errors
		if (is_uploaded_file($_FILES['image']['tmp_name'])) {
			if ('image/jpeg' != $_FILES['image']['type'] && 'image/gif' != $_FILES['image']['type']
				&& 'image/png' != $_FILES['image']['type'])
			{
				$errors['image'][] = 'Image must be of type JPEG, GIF or PNG.';
			}
			if ($_FILES['image']['size'] > 51200)
			{
				$errors['image'][] = 'Image must be less than 50kb in size.';
			}
			//Special case to laugh at certain people
			if (stristr($_FILES['image']['name'], '.php'))
			{
				$log = Zend_Registry::get('logger');
				$log->alert('Genuis at IP:' . $_SERVER['REMOTE_HOST'] . ' tied to upload a php file as an image.');
				die('WTF is this, amature hour? Give me more credit than that. Your IP has been logged, have a nice day!');
			}
		}
		//Token errors
		if ($values['auth'] != $_SESSION['cisvtoken'])
		{
			$errors['generic'][] = 'This function has been called indirectly. Please try again.';
		}

		return $errors;
	}


	/**
	 * Function to process a title addition
	 */
	public function processAdd($values)
	{
		//We'll need the database and config files
		$db = Zend_Registry::get('database');
		$config = Zend_Registry::get('config');
		
		//Setup the main entry
		$titleTableName = 'cis_titles';
		$titleTableData = array(
			'titleName'		=> strip_tags($values['titleName']),
			'titleDescription'	=> nl2br(strip_tags($values['titleDescription'])),
			'titlePros'		=> (0 != strlen(trim($values['titlePros'])))? strip_tags($values['titlePros']) : null,
			'titleCons'		=> (0 != strlen(trim($values['titleCons'])))? strip_tags($values['titleCons']) : null,
			'titleCreator'		=> strip_tags($values['titleCreator']),
			'titleYear'		=> (0 != strlen(trim($values['titleYear'])))? $values['titleYear'] : null,
			'titleComments'		=> (0 != strlen(trim($values['titleComments'])))? nl2br(strip_tags($values['titleComments'])) : null,
			'titleAdult'		=> $values['titleAdult'],
			'titleType'		=> $values['titleType'],
			'titlePlot'		=> $values['titlePlot'],
			'titleAvailable'	=> $values['titleAvailable'],
			'titleStatus'		=> $values['titleStatus'],
			'ID_MEMBER'		=> $values['userID']
		);
		
		//Setup alternates data is applicable
		if (isset($values['alternateNames']))
		{
			//Split the alternates into an array using the newline delimiter
			$alternates = preg_split('/\n/', $values['alternateNames'], -1, PREG_SPLIT_NO_EMPTY);
		}

		//If we have an uploaded file, create new name
		if (is_uploaded_file($_FILES['image']['tmp_name']))
		{
			$fileName = str_replace('.', '', microtime(true));

			if ('image/jpeg' == $_FILES['image']['type']) {
				$imageName = $fileName . '.jpg';
			} elseif ('image/gif' == $_FILES['image']['type']) {
				$imageName = $fileName . '.gif';
			}  elseif ('image/png' == $_FILES['image']['type']) {
				$imageName = $fileName . '.png';
			}

			//Create database info
			$imageTableName = 'cis_images';
			$imageTableData = array(
				'imageFile'	=> $imageName,
				'ID_MEMBER'	=> $values['userID']
			);
		}

		//Execute
		$db->beginTransaction();
		try {
			//Main database insert
			$db->insert($titleTableName, $titleTableData);
			//Alternates insert
			$titleID = $db->lastInsertId();
			if (isset($alternates))
			{
				$alternateTableName = 'cis_title_alternates';
				foreach ($alternates as $alt)
				{
					$alternateTableData = array(
						'titleID'	=> $titleID,
						'titleName'	=> $alt
					);
					$db->insert($alternateTableName, $alternateTableData);
				}
			}
			//Move image file
			if (is_uploaded_file($_FILES['image']['tmp_name']))
			{
				$newFile = $config->image->path . '/pending/' . $imageName;
				move_uploaded_file($_FILES['image']['tmp_name'], $newFile);
				chmod($newFile, 0666);
				//Image database insert
				$imageTableData['titleID'] = $titleID;
				$db->insert($imageTableName, $imageTableData);
			}
			//Commit!
			$db->commit();
		} catch (Zend_Exception $e) {
			$db->rollBack();
			$mssg = $e->getMessage();
			$logger = Zend_Registry::get('logger');
			$logger->err('Title insert failure for member ID #' . $values['userID'] . ': ' . $mssg . '.');
			return 'FAIL';
		}
		
		//Log the successfull add
		$logger = Zend_Registry::get('logger');
		$logger->info('Member ID #' . $values['userID'] . ' submitted title ID #' . $titleID . ': "' . $values['titleName'] . '" for review.');
		
		return 'SUCCESS';		
	}


	/**
	 * Function to get basic title data
	 */
	public function getSingleBasic($titleID)
	{
		$db = Zend_Registry::get('database');
		$data = $db->fetchRow('SELECT * FROM cis_titles WHERE titleID = ? AND titleStatus = \'active\' LIMIT 1', $titleID);

		return $data;
	}


	/**
	 * Function to get an array of alternate names for a title
	 */
	public function getAlternates($titleID)
	{
		$db = Zend_Registry::get('database');
		$data = $db->fetchAll('SELECT * FROM cis_title_alternates WHERE titleID = ? AND titleStatus = \'active\'', $titleID);

		return $data;
	}


	/**
	 * Function to compute a title's average
	 */
	public function getBasicRating($titleID)
	{
		//Retrieve data
		$db = Zend_Registry::get('database');
		$data = $db->fetchRow('SELECT COUNT(ratingWeight) AS totalVotes, AVG(ratingWeight) AS averageVote FROM cis_title_ratings
			WHERE titleID = ?', $titleID);
		$data['globalAverage'] = $db->fetchOne('SELECT AVG(ratingweight) FROM cis_title_ratings');
		$minimum = 2;

		if ($data['totalVotes'] >= $minimum)
		{
			//Calculate
			$vpm = $data['totalVotes'] + $minimum;
			$avg = ($data['totalVotes'] / ($vpm)) * $data['averageVote'] + ($minimum / ($vpm)) * $data['globalAverage'];
		}
		else
		{
			$avg = '--Not Enough Ratings Recorded--';
		}

		$result = array(
			'weighted'	=> $avg,
			'total'		=> $data['totalVotes'],
			'average'	=> $data['averageVote']
		);

		return $result;
	}


	/**
	 * Function to return all rating data
	 */
	public function getDetailedRating($titleID)
	{
		$db = Zend_Registry::get('database');
		
		//Basic rating data
		$data = $this->getBasicRating($titleID);

		//Median rating
		$median = $db->fetchOne('SELECT AVG(t.ratingWeight) FROM
			(SELECT e.ratingWeight FROM cis_title_ratings AS e, cis_title_ratings AS d WHERE e.titleID = d.titleID AND e.titleID = ?
			GROUP BY e.ratingWeight HAVING SUM(CASE WHEN e.ratingWeight = d.ratingWeight THEN 1 ELSE 0 END)
			>= ABS(SUM(SIGN(e.ratingWeight - d.ratingWeight)))) AS t', $titleID);

		//Basic and batch counts and averages
		$basic = $db->fetchRow('SELECT COUNT(ratingWeight) AS count, AVG(ratingWeight) AS average
			FROM cis_title_ratings WHERE titleID = ? AND ratingMethod = \'basic\'', $titleID);
		$batch = $db->fetchRow('SELECT COUNT(ratingWeight) AS count, AVG(ratingWeight) AS average
			FROM cis_title_ratings WHERE titleID = ? AND ratingMethod = \'batch\'', $titleID);

		//Batch categories
		$batchTypes = array(
			'ratingStory'		=> 'story',
			'ratingCharacter'	=> 'character',
			'ratingArt'		=> 'art',
			'ratingMusic'		=> 'music',
			'ratingVoice'		=> 'voice'
		);
		foreach ($batchTypes as $key => $value)
		{
			$batchCats[$value] = $db->fetchOne('SELECT AVG(' . $key . ') FROM cis_title_ratings_batch WHERE titleID = ' . $titleID);
		}

		//Batch true average
		if (0 != $batchCats['voice'])
		{
			//Voice ratings
			$batch['trueAverage'] = $db->fetchOne('SELECT (SELECT AVG(ratingStory) + AVG(ratingCharacter) + AVG(ratingArt)
				+ AVG(ratingMusic) + AVG(ratingVoice) FROM cis_title_ratings_batch WHERE titleID = ?) / 5', $titleID);
		}
		else
		{
			//No voice ratings
			$batch['trueAverage'] = $db->fetchOne('SELECT (SELECT AVG(ratingStory) + AVG(ratingCharacter) + AVG(ratingArt)
				+ AVG(ratingMusic) FROM cis_title_ratings_batch WHERE titleID = ?) / 4', $titleID);
		}

		//Format and put into data array
		$data['weighted'] = round($data['weighted'], 3);
		$data['average'] = round($data['average'], 3);
		$data['median'] = round($median);
		$data['basicCount'] = $basic['count'];
		$data['basicAverage'] = round($basic['average'], 3);
		$data['batchCount'] = $batch['count'];
		$data['batchAverage'] = round($batch['average'], 3);
		$data['batchTrue'] = round($batch['trueAverage'], 3);
		$data = array_merge($data, $batchCats);

		return $data;		
	}


	/**
	 * Function to create rating detail graph stats
	 */
	public function getGraph($titleID)
	{
		$db = Zend_Registry::get('database');

		$batchTypes = array('ratingStory','ratingCharacter','ratingArt','ratingMusic','ratingVoice');

		$ratingOverall = $db->fetchAll('SELECT ratingWeight as rating, COUNT(ratingWeight) as count
			FROM cis_title_ratings WHERE titleID = ? GROUP BY ratingWeight', $titleID);

		//Initialize empty data array for overall
		for ($n = 1; $n <= 5; $n++)
		{
			$data['ratingOverall']['count'][$n] = 0;
		}

		//Separate overall rating counts
		$max = 1;
		foreach ($ratingOverall as $value)
		{
			$data['ratingOverall']['count'][$value['rating']] = $value['count'];
			//If this is the maxiumum so far, store it as such
			if ($max < $value['count'])
			{
				$max = $value['count'];
			}
		}
		
		//Calculate height multiplier (150px - 15px added later = 135px)
		$multiplier = 135 / $max;
		
		//Calculate graph bar height
		for ($n = 1; $n <= 5; $n++)
		{
			$data['ratingOverall']['height'][$n] = round($data['ratingOverall']['count'][$n] * $multiplier + 15);
		}

		//Generate the batch graphs
		foreach ($batchTypes as $batch)
		{
			$data[$batch] = $this->_generateBatchGraph($titleID, $batch);
		}

		return $data;
	}


	/**
	 * Function to generate graph data for a single batch category
	 * @param int titleID: Title ID number of the visual novel
	 * @param string batchType: Batch category to create for
	 */
	private function _generateBatchGraph($titleID, $batchType)
	{
		$db = Zend_Registry::get('database');

		//For some unknown reason, the Zend_Db prepare statement fucks up if you use it here
		//TODO: Replace with Zend_Db_Select query instead for security
		$result = $db->fetchAll('SELECT ' . $batchType . ' as rating, COUNT(' . $batchType . ') as count
			FROM cis_title_ratings_batch WHERE titleID = ' . addslashes($titleID) . ' GROUP BY ' . $batchType);

		//Initialize empty data array for overall
		for ($n = 1; $n <= 5; $n++)
		{
			$data['count'][$n] = 0;
		}

		//Separate overall rating counts
		$max = 1;
		foreach ($result as $value)
		{
			$data['count'][$value['rating']] = $value['count'];
			//If this is the maxiumum so far, store it as such
			if ($max < $value['count'])
			{
				$max = $value['count'];
			}
		}

		//Calculate height multiplier (150px - 15px added later = 135px)
		$multiplier = 135 / $max;
		
		//Calculate graph bar height
		for ($n = 1; $n <= 5; $n++)
		{
			$data['height'][$n] = round($data['count'][$n] * $multiplier + 15);
		}

		return $data;
	}


	/**
	 * Function to get a list of pending titles
	 * @return: array of title information
	 */
	public function getPending()
	{
		$db = Zend_Registry::get('database');

		$data = $db->fetchAll('SELECT t.titleName, t.titleLastUpdate, m.memberName
			FROM cis_titles AS t
			INNER JOIN smf_members AS m
			USING(ID_MEMBER)
			WHERE t.titleStatus = \'pending\'
			');

		return $data;
	}
}

?>
