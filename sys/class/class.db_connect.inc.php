<?php

/**
 * Database actions (DB access, validation, etc.)
 * PHP Version 5
 *
 * LICENSE: This source file is subject to the GPLv2 License, available
 * at http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @author			Hamidreza Soleimani <hamidreza.s@gmail.com>
 * @copyright		2012 MeloBit Co.
 * @license			http://www.gnu.org/licenses/gpl-2.0.html
 */

class DB_Connect
{
	/**
	 * Stores a database object
	 * 
	 * @var object A database object
	 */
	protected $db;
	
	/**
	 * Checks for a DB object or creates one if one is not found
	 *
	 * @param object $db A database object
	 */
	protected function __construct($db = null)
	{
		if (is_object($db))
		{
			$this->db = $db;
		}
		else
		{
			// Constants are defined in /sys/config/db-cred.inc.php
			$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
			try
			{
				$this->db = new PDO($dsn, DB_USER, DB_PASS);
			}
			catch (Exception $e)
			{
				// If the DB connection fails, output the error
				die ($e->getMessage());
			}
		}
	}
}


























