<?php

class LuceneModel
{
	/**
	 * Function to index all parts of a title
	 * @param int titleID: ID of the title to be indexed
	 * @param bool delete: Deletes all previous records if true
	 */
	public function indexTitleAll($titleID, $delete = 'true')
	{
		//TODO: Deletion of old records not built yet

		//Setup some variables
		$db = Zend_Registry::get('database');
		$config = Zend_Registry::get('config');
		$indexPath = $config->search->path;

		//Screw hackers
		if (!ctype_digit($titleID))
		{
			$log = Zend_Registry::get('logger');
			$log->alert('HACKING ATTEMPT: SQL injection in ' . __CLASS__ . '->' . __FUNCTION__ . ' from IP ' . $_SERVER[REMOTE_HOST]);
			die('HACKING ATTEMPT DETECTED. HALTING EXECUTION. YOUR IP HAS BEEN LOGGED.');
		}

		//Fetch the main data
		$primary = $db->fetchRow('SELECT titleName, titleDescription, titleCreator, titleYear, titlePlot, titleType, titleAvailable, titleComments
					FROM cis_titles WHERE titleID = ? LIMIT 1', $titleID);
		//Fetch all the alternates
		$alternates = $db->fetchAll('SELECT titleName FROM cis_title_alternates WHERE titleID = ?', $titleID);

		//Let's index!
		//Open the index...
		try {
			$index = Zend_Search_Lucene::open($indexPath);
		} catch (Zend_Search_Lucene_Exception $e) {
			try {
				//Can't open? Try create...
				$index = Zend_Search_Lucene::create($indexPath);
			} catch (Zend_Search_Lucene_Exception $e) {
				$mssg = $e->getMessage();
				$log = Zend_Registry::get('logger');
				$log->err('Failure to open search index for writing TID#' . $titleID . ': ' . $mssg);
				return false;
			}
		}

		//Create a new document
		$doc = new Zend_Search_Lucene_Document();
		//Create index fields and populate
		$doc->addField(Zend_Search_Lucene_Field::UnIndexed('tid', $titleID));
		$doc->addField(Zend_Search_Lucene_Field::Text('name', $primary['titleName'], 'utf-8'));
		$doc->addField(Zend_Search_Lucene_Field::Text('creator', $primary['titleCreator'], 'utf-8'));
		$doc->addField(Zend_Search_Lucene_Field::UnStored('description', $primary['titleDescription'], 'utf-8'));
		$doc->addField(Zend_Search_Lucene_Field::UnStored('comments', $primary['titleComments'], 'utf-8'));
		$doc->addField(Zend_Search_Lucene_Field::Keyword('type', $primary['titleType']));
		$doc->addField(Zend_Search_Lucene_Field::Keyword('plot', $primary['titlePlot']));
		$doc->addField(Zend_Search_Lucene_Field::Keyword('available', $primary['titleAvailable']));
		$doc->addField(Zend_Search_Lucene_Field::Keyword('year', $primary['titleYear']));
		//Add to the index
		$index->addDocument($doc);
		unset($doc);

		//Now to do all the alternate names...
		foreach ($alternates as $alt)
		{
			//Create a new document
			$doc = new Zend_Search_Lucene_Document();
			//Create index fields and populate
			$doc->addField(Zend_Search_Lucene_Field::UnIndexed('tid', $titleID));
			$doc->addField(Zend_Search_Lucene_Field::Text('name', $alt['titleName'], 'utf-8'));
			//Add to the index
			$index->addDocument($doc);
			unset($doc);
		}

		//Commit the index
		$index->commit();

		return true;
	}
}
