<?php
/**
 * Base class for test USVN
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package test
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id: Test.php 1536 2008-11-01 16:08:37Z duponc_j $
 */

require_once 'test/TestSetup.php';

define( 'UTEST_SVN_URL_SEP',   ':' );
define( 'TEST_USVN_PATH',      TESTING_DIR."/usvn" );
define( 'TEST_REPOS_PATH',     TEST_USVN_PATH."/svn" );
define( 'TEST_SVN_URL',        "http://localhost/" );

abstract class USVN_Test_TestCase extends PHPUnit_Framework_TestCase 
{
    private $_path;
	
    protected function setUp()
	{
        error_reporting(E_ALL | E_STRICT);
        date_default_timezone_set('UTC');
        $this->_path = getcwd();
		$this->setConsoleLocale();
		
		USVN_DirectoryUtils::removeDirectory( TESTING_DIR.'/' );
		mkdir( TESTING_DIR );
		mkdir( USVN_CONFIG_DIR );
		mkdir( TEST_USVN_PATH );
		mkdir( TEST_REPOS_PATH );
		
		file_put_contents( USVN_CONFIG_FILE, '['.USVN_CONFIG_SECTION.']
subversion.path = "' . getcwd() . '/' . TEST_USVN_PATH . '"
subversion.passwd = "' . getcwd() . '/' . TEST_USVN_PATH . '/htpasswd"
subversion.authz = "' . getcwd() . '/' . TEST_USVN_PATH . '/authz"
subversion.url = "' . TEST_SVN_URL . '"
version = "0.8.4"
translation.locale = "en_US"
');
		$config = new USVN_Config_Ini( USVN_CONFIG_FILE, USVN_CONFIG_SECTION );
		Zend_Registry::set( 'config', $config );

		USVN_Translation::initTranslation( null, 'app/locale' ); // Don't translate
    }

    protected function tearDown()
	{
        chdir($this->_path);
    }

	private function setConsoleLocale()
	{
		if (PHP_OS == "Linux")
		{
			USVN_ConsoleUtils::setLocale("en_US.utf8");
		}
		else
		{
			USVN_ConsoleUtils::setLocale("en_US.UTF-8");
		}
	}
}

