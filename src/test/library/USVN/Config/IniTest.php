<?php
/**
 * Class to manipulate config file
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package usvn
 * @subpackage config
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id: IniTest.php 1188 2007-10-06 12:03:17Z crivis_s $
 */

// Call USVN_Config_IniTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "USVN_Config_IniTest::main");
}

require_once 'test/TestSetup.php';

/**
 * Test class for USVN_Config_Ini.
 * Generated by PHPUnit_Util_Skeleton on 2007-03-21 at 16:49:21.
 */
class USVN_Config_IniTest extends USVN_Test_TestCase
{
	private $config;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
	{
        $suite  = new PHPUnit_Framework_TestSuite("USVN_Config_IniTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    public function setUp() {
		parent::setUp();
		
		file_put_contents(TESTING_DIR."/test.ini",'[global]
test1 = "Test 1"
test2.value = "Test 2"
test2.key = "Test key 2"
test3.subvalue.value = "Test 3"
test4 = "Test 4"
		');
		$this->config = new USVN_Config_Ini(TESTING_DIR."/test.ini", "global");
    }

    public function tearDown()
	{
		unlink(TESTING_DIR."/test.ini");
		if (file_exists(TESTING_DIR."/test2.ini")) {
			unlink(TESTING_DIR."/test2.ini");
		}
		parent::tearDown();
    }

    public function test_save_NoChange() {
		$this->test1 = "Test 1 Modif";
		$this->config->save();
		$config2 = new Zend_Config_Ini(TESTING_DIR."/test.ini", "global");
		$this->assertEquals("Test 1", $config2->test1);
		$this->assertEquals("Test 2", $config2->test2->value);
		$this->assertEquals("Test key 2", $config2->test2->key);
		$this->assertEquals("Test 3", $config2->test3->subvalue->value);
		$this->assertEquals("Test 4", $config2->test4);
    }

    public function test_save() {
		$this->config->test1 = "Test 1 Modif";
		$this->assertEquals("Test 1 Modif", $this->config->test1);
		$this->config->save();
		$config2 = new Zend_Config_Ini(TESTING_DIR."/test.ini", "global");
		$this->assertEquals("Test 1 Modif", $config2->test1);
		$this->assertEquals("Test 1 Modif", $config2->test1);
    }

	public function test_Create() {
		$config = new USVN_Config_Ini(TESTING_DIR."/test2.ini", "global", array("create" => true));
		$this->assertFileExists(TESTING_DIR."/test2.ini");
		$config->test = array("valeur" => "tutu");
		$config->save();
		$config2 = new Zend_Config_Ini(TESTING_DIR."/test2.ini", "global");
		$this->assertEquals("tutu", $config2->test->valeur);
	}

	public function test_NotCreate() {
		try
		{
			$config = new USVN_Config_Ini(TESTING_DIR."/test2.ini", "global");
		}
		catch (Exception $e)
		{
			// Exception thrown as expected
			$this->assertEquals( "Can't open config file ".TESTING_DIR."/test2.ini.", $e->getMessage(), "Wrong exception thrown" );
			$this->assertFileNotExists(TESTING_DIR."/test2.ini");
			return;
		}
		$this->fail( "The config file creation should have failed" );
	}

	public function test_CreateNotPossible() {
		try
		{
			$config = new USVN_Config_Ini(TESTING_DIR."/fake/test2.ini", "global", array("create" => true));
		}
		catch (Exception $e)
		{
			// Exception thrown as expected
			$this->assertEquals( "Can't write config file ".TESTING_DIR."/fake/test2.ini.", $e->getMessage(), "Wrong exception thrown" );
			$this->assertFileNotExists(TESTING_DIR."/fake/test2.ini");
			return;
		}
		$this->fail( "The config file creation should have failed" );
	}
}

// Call USVN_Config_IniTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "USVN_Config_IniTest::main") {
    USVN_Config_IniTest::main();
}
?>
