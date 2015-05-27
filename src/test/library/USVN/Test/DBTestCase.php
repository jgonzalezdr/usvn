<?php
/**
 * Base class for test database
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package test
 * @subpackage db
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id: DB.php 1536 2008-11-01 16:08:37Z duponc_j $
 */
require_once "PHPUnit/Framework/TestCase.php";
require_once "PHPUnit/Framework/TestSuite.php";

require_once 'app/install/install.includes.php';

abstract class USVN_Test_DBTestCase extends USVN_Test_TestCase {
	
	const DB_INI_FILE = 'tests/db.ini';
	const DB_HOST = 'localhost';
	const DB_NAME = 'usvn-test';
	const DB_USERNAME = 'usvn-test';
	const DB_PASSWORD = 'usvn-test';
	const DB_SQLITE_FILE = 'tests/usvn-test.db';
	const DB_PREFIX = 'usvn_';
	
	protected $db;

    protected function setUp() 
	{
		parent::setUp();
		
		$params = array ( 'host' => self::DB_HOST,
						  'username' => self::DB_USERNAME,
						  'password' => self::DB_PASSWORD );
		
		if (getenv('DB') == "PDO_SQLITE" || getenv('DB') === false) 
		{
			$params['dbname'] = self::DB_SQLITE_FILE;
			$config_dbname = getcwd() . '/' . self::DB_SQLITE_FILE;
			$dbtype = "PDO_SQLITE";
		}
		else
		{
			$params['dbname'] = self::DB_NAME;
			$config_dbname = self::DB_NAME;
			$dbtype = getenv('DB');
		}
			
		$this->db = Zend_Db::factory( $dbtype, $params );
		$this->_clean();
		Install::installDb( self::DB_INI_FILE, dirname(__FILE__) . '/../../../../library/SQL/', 
							$params['host'], $params['username'], $params['password'], 
							$params['dbname'], self::DB_PREFIX, $dbtype, false );
		Zend_Db_Table::setDefaultAdapter($this->db);
		USVN_Db_Table::$prefix = self::DB_PREFIX;

		file_put_contents( CONFIG_FILE, '
database.adapterName = "PDO_SQLITE"
database.prefix = "' . self::DB_PREFIX . '"
database.options.host = "' . $params['host'] . '"
database.options.username = "' . $params['username'] . '"
database.options.password = "' . $params['password'] . '"
database.options.dbname = "' . $config_dbname . '"
', FILE_APPEND);

		$config = new USVN_Config_Ini( CONFIG_FILE, USVN_CONFIG_SECTION );
		Zend_Registry::set( 'config', $config );
    }

	protected function _clean()
	{
		if (getenv('DB') == "PDO_SQLITE" || getenv('DB') === false) {
			if (file_exists( self::DB_SQLITE_FILE )) {
				@unlink( self::DB_SQLITE_FILE );
			}
		}
		else {
			USVN_Db_Utils::deleteAllTables($this->db);
		}
	}

    protected function tearDown() 
	{
		if( $this->db !== null )
		{
			$this->_clean();
			$this->db->closeConnection();
			$this->db = null;
		}
		parent::tearDown();
    }

    public function __destruct() {
        if ($this->db != null) {
            $this->db->closeConnection();
        }
    }

	/**
	 * Create and save a group
	 *
	 * @return USVN_Db_Table_Row_Group
	 */
    public function createGroup($name)
    {
		$table = new USVN_Db_Table_Groups();
		try {
			$group = $table->createRow(
				array(
					"groups_name"        => "$name",
					"groups_description" => "$name's description"
				)
			);
			$group->save();
			return $group;
		}
		catch (Exception $e) {
			$this->fail($name . " : " . $e->getMessage());
		}
    }

	/**
	 * Create and save a user
	 *
	 * @return USVN_Db_Table_Row_User
	 */
    protected function createUser($login, $password = "test")
    {
		$table = new USVN_Db_Table_Users();
		try {
			$user = $table->insert(
				array(
					"users_login"       => $login,
					"users_password"    => USVN_Crypt::crypt($password),
					'users_firstname' 	=> 'firstname',
					'users_lastname' 	=> 'lastname',
					'users_email' 		=> 'email@email.fr',
				)
			);
			$user = $table->find($user)->current();
			return $user;
		}
		catch (Exception $e) {
			$this->fail($login . " : " . $e->getMessage());
		}
    }


	/**
	 * Generate and save a project
	 *
	 * @param string $name
	 * @return USVN_Db_Table_Row_Project
	 */
	protected function createProject($name)
	{
		$table = new USVN_Db_Table_Projects();
		try {
			$obj = $table->fetchNew();
			$obj->setFromArray(array('projects_name' => $name));
			$obj->save();
			return $obj;
		}
		catch (Exception $e) {
			$this->fail($name . " : " . $e->getMessage());
		}
	}
}
