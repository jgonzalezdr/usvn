<?php
/**
 * Model for configuration pages
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package admin
 * @subpackage config
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id: ConfigTest.php 1309 2007-11-13 14:16:50Z duponc_j $
 */

// Call USVN_ConfigTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "USVN_ConfigTest::main");
}

require_once 'test/TestSetup.php';

/**
 * Test class for USVN_Config.
 * Generated by PHPUnit_Util_Skeleton on 2007-03-26 at 17:42:45.
 */
class USVN_ConfigTest extends USVN_Test_TestCase
{
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
	{
        $suite  = new PHPUnit_Framework_TestSuite("USVN_ConfigTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

	protected function setUp()
	{
		parent::setUp();
		
		USVN_Translation::initTranslation('en_US', 'app/locale');
	}

    public function test_setLanguage()
	{
		USVN_Config::setLanguage('fr_FR');
		$config = new USVN_Config_Ini(USVN_CONFIG_FILE, USVN_CONFIG_SECTION);
		$this->assertEquals('fr_FR', $config->translation->locale);
    }

    public function test_setTimezone()
	{
		USVN_Config::setTimezone('Europe/Paris');
		$config = new USVN_Config_Ini(USVN_CONFIG_FILE, USVN_CONFIG_SECTION);
		$this->assertEquals('Europe/Paris', $config->timezone);
    }

    public function test_setCheckForUpdate()
	{
		USVN_Config::setCheckForUpdate(true);
		$config = new USVN_Config_Ini(USVN_CONFIG_FILE, USVN_CONFIG_SECTION);
		$this->assertEquals(1, $config->update->checkforupdate);
		$this->assertEquals(0, $config->update->lastcheckforupdate);
	}

    public function test_Unset()
	{
		$config = new USVN_Config_Ini(USVN_CONFIG_FILE, USVN_CONFIG_SECTION);
		unset($config->translation->locale);
		unset($config->version);
		$this->assertFalse(isset($config->translation->locale), "Ca serait pas le patch sur le framework pour supprimer un champ par hasard ?");
		$this->assertFalse(isset($config->version));
    }

    public function test_setLanguage_Invalid()
	{
		try
		{
			USVN_Config::setLanguage('tutu');
		}
		catch( Exception $e )
		{
			$this->assertEquals( "Invalid language", $e->getMessage(), "Wrong exception thrown" );
			$config = new USVN_Config_Ini( USVN_CONFIG_FILE, USVN_CONFIG_SECTION );
			$this->assertEquals( 'en_US', $config->translation->locale, "Locate has changed" );
			return;
		}
		$this->fail( "Language change should have failed" );
    }
}

// Call USVN_ConfigTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "USVN_ConfigTest::main") {
    USVN_ConfigTest::main();
}
?>
