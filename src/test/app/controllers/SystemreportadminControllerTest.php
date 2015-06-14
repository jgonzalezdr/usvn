<?php
/**
 * Controller for system report admin page
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package admin
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id$
 */

// Call SystemreportControllerTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "SystemadminControllerTest::main");
}

require_once 'test/TestSetup.php';

class SystemadminControllerTest extends USVN_Test_AdminControllerTestCase
{
	protected $controller_name = "systemreportadmin";
	protected $controller_class = "SystemreportadminController";

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
	{
        $suite  = new PHPUnit_Framework_TestSuite("SystemadminControllerTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }
    
	public function test_index()
	{
		// Prepare
		$config = Zend_Registry::get( 'config' );
		$config->url = array ( "base" => "usvn" );

		// Exercise
		$this->runAction('index');

		// Verify
		$this->assertContains( "<h1>System report</h1>", $this->getBody() );
	}
}

// Call SystemreportControllerTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "SystemadminControllerTest::main") {
    SystemadminControllerTest::main();
}
?>
