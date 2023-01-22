<?php

class EditModel
{
	/**
	 * Function to grab transaction info from the database
	 * Returns an array of transaction information
	 */
	public function getTransactions()
	{
		$db = Zend_Registry::get('database');
		//Qry to return all rows
		$data = $db->fetchAll('SELECT * FROM cis_edit_transactions');

		return $data;
	}


	/**
	 * Function to return a count of total transaction numbers
	 */
	public function getTransactionCount()
	{
		$db = Zend_Registry::get('database');
		$data = $db->fetchOne('SELECT COUNT(*) FROM cis_edit_transactions');
		return $data;
	}


	/**
	 * Function to return the count of total edits
	 */
	public function getEditCount()
	{
		$db = Zend_Registry::get('database');
		$data = $db->fetchOne('SELECT COUNT(*) FROM cis_edits');
		return $data;
	}
}
?>
