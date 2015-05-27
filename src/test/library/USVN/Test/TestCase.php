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
require_once "PHPUnit/Framework/TestCase.php";
require_once "PHPUnit/Framework/TestSuite.php";

require_once 'app/install/install.includes.php';

define('USVN_URL_SEP', ':');
define('CONFIG_FILE', 'tests/test.ini');

abstract class USVN_Test_TestCase extends PHPUnit_Framework_TestCase {
    private $_path;
	
	const TESTING_DIR = "tests";
	const USVN_PATH = "tests/usvn";
	const REPOS_PATH = "tests/usvn/svn";
	const SVN_URL = "http://localhost/";

    protected function setUp() {
        error_reporting(E_ALL | E_STRICT);
        date_default_timezone_set('UTC');
        $this->_path = getcwd();
		$this->setConsoleLocale();
		USVN_Translation::initTranslation('en_US', 'app/locale');
		
		USVN_DirectoryUtils::removeDirectory(self::TESTING_DIR.'/');
		mkdir(self::TESTING_DIR);
		mkdir(self::USVN_PATH);
		mkdir(self::REPOS_PATH);
		
		file_put_contents(CONFIG_FILE, '[general]
subversion.path = "' . getcwd() . '/' . self::USVN_PATH . '"
subversion.passwd = "' . getcwd() . '/' . self::USVN_PATH . '/htpasswd"
subversion.authz = "' . getcwd() . '/' . self::USVN_PATH . '/authz"
subversion.url = "' . self::SVN_URL . '"
version = "0.8.4"
translation.locale = "en_US"
');
		$config = new USVN_Config_Ini( CONFIG_FILE, USVN_CONFIG_SECTION );
		Zend_Registry::set( 'config', $config );
    }

    protected function tearDown() {
        chdir($this->_path);
    }

	private function setConsoleLocale()
	{
		if (PHP_OS == "Linux") {
			USVN_ConsoleUtils::setLocale("en_US.utf8");
		}
		else {
			USVN_ConsoleUtils::setLocale("en_US.UTF-8");
		}
	}
}

?>
