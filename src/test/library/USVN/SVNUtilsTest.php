<?php
/**
 * Usefull static method to manipulate an svn repository
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package client
 * @subpackage utils
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id: SVNUtilsTest.php 1536 2008-11-01 16:08:37Z duponc_j $
 */

// Call USVN_SVNUtilsTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "USVN_SVNUtilsTest::main");
}

require_once 'test/TestSetup.php';

/**
 * @coversDefaultClass USVN_SVNUtils
 */
class USVN_SVNUtilsTest extends USVN_Test_TestCase
{
	private $savedHome;
	
	private $fakeHome;
	private $testRepoPath;
	private $fakeRepoPath;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main() 
	{
        $suite  = new PHPUnit_Framework_TestSuite("USVN_SVNUtilsTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

	/**
	* This method simulates "svnadmin create $path"
	*
	* @param string Path to create directory structs
	*/
	protected function createSvnDirectoryStruct( $path )
	{
		@mkdir( $path);
		@mkdir( $path . "/hooks" );
		@mkdir( $path . "/locks" );
		@mkdir( $path . "/conf" );
		@mkdir( $path . "/dav" );
		@mkdir( $path . "/db" );
	}

    protected function setUp()
    {
		parent::setUp();

		$this->fakeHome = TESTING_DIR . '/fakehome';
		$this->testRepoPath = TEST_REPOS_PATH . '/TestRepo';
		$this->fakeRepoPath = TEST_REPOS_PATH . '/FakeRepo';

		mkdir( $this->fakeHome );
		chmod( $this->fakeHome, 0000 );
		$this->savedHome = getenv("HOME");
		putenv( 'HOME=' . $this->fakeHome );
		
        $this->createSvnDirectoryStruct( $this->testRepoPath );
		
        mkdir( $this->fakeRepoPath );
    }

	protected function tearDown()
	{
		putenv( 'HOME=' . $this->savedHome );
		parent::tearDown();
	}
/*
    public function test_isSVNRepository()
    {
        $this->assertTrue(USVN_SVNUtils::isSVNRepository('tests/tmp/test repository'));
        $this->assertFalse(USVN_SVNUtils::isSVNRepository('tests/tmp/fakerepository'));
    }

	public function test_changedFiles()
	{
        $this->assertEquals(array(array("U", "tutu")), USVN_SVNUtils::changedFiles("U tutu\n"));
        $this->assertEquals(array(array("U", "tutu"), array("U", "tata")), USVN_SVNUtils::changedFiles("U tutu\nU tata\n"));
        $this->assertEquals(array(array("U", "tutu"), array("U", "U")), USVN_SVNUtils::changedFiles("U tutu\nU U\n"));
        $this->assertEquals(array(array("U", "tutu"), array("U", "hello world"), array("U", "toto")), USVN_SVNUtils::changedFiles("U tutu\nU hello world\nU toto\n"));
	}

	public function test_createsvndirectoryStruct()
	{
		USVN_SVNUtils::createsvndirectoryStruct('tests/tmp/svn directorystruct');
		 $this->assertTrue(file_exists('tests/tmp/svn directorystruct'));
		 $this->assertTrue(file_exists('tests/tmp/svn directorystruct/hooks'));
	}

	public function test_createSvn()
	{
		USVN_SVNUtils::createSvn('tests/tmp/svn directory');
		$this->assertTrue(file_exists('tests/tmp/svn directory'));
		$this->assertTrue(USVN_SVNUtils::isSVNRepository('tests/tmp/svn directory'));
	}

	public function test_createStandardDirectories()
	{
		USVN_SVNUtils::createSvn('tests/tmp/svn directory');
		USVN_SVNUtils::createStandardDirectories('tests/tmp/svn directory');
		USVN_SVNUtils::checkoutSvn('tests/tmp/svn directory', 'tests/tmp/out');
		$this->assertTrue(file_exists('tests/tmp/out'));
		$this->assertTrue(file_exists('tests/tmp/out/trunk'));
		$this->assertTrue(file_exists('tests/tmp/out/branches'));
		$this->assertTrue(file_exists('tests/tmp/out/tags'));
	}

	public function test_createSvnFR()
	{
		putenv("LANG=C");
		USVN_Translation::initTranslation('fr_FR', 'app/locale');
		USVN_SVNUtils::createSvn('tests/tmp/svn directory');
		$this->assertTrue(file_exists('tests/tmp/svn directory'));
		$this->assertTrue(USVN_SVNUtils::isSVNRepository('tests/tmp/svn directory'));
	}

	public function test_createSvnBadDir()
	{
		try {
			USVN_SVNUtils::createSvn('tests/tmp/svn directory/test/tutu');
		}
		catch (USVN_Exception $e) {
			return ;
		}
		$this->fail();
	}

	public function test_checkoutSvn()
	{
		USVN_SVNUtils::createSvn('tests/tmp/svn directory');
		USVN_SVNUtils::checkoutSvn('tests/tmp/svn directory', 'tests/tmp/out');
		 $this->assertTrue(file_exists('tests/tmp/out'));
		 $this->assertTrue(file_exists('tests/tmp/out/.svn'));
	}

	public function test_checkoutSvnBadDir()
	{
		try {
            USVN_SVNUtils::checkoutSvn('tests/tmp/svn directory2', 'tests/tmp/out');
		}
		catch (USVN_Exception $e) {
			return ;
		}
		$this->fail();
	}

	public function test_listSvn()
	{
		if (!(substr(php_uname(), 0, 7) == "Windows")) {
			USVN_SVNUtils::createSvn('tests/tmp/svn directory');
			USVN_SVNUtils::createStandardDirectories('tests/tmp/svn directory');
			USVN_SVNUtils::checkoutSvn('tests/tmp/svn directory', 'tests/tmp/out');
			$path = getcwd();
			chdir('tests/tmp/out');
			mkdir('trunk/testdir');
			`svn add trunk/testdir`;
			touch('trunk/testfile');
			`svn add trunk/testfile`;
			`svn commit --non-interactive -m Test`;
			chdir($path);
			$res = USVN_SVNUtils::listSvn('tests/tmp/svn directory', '/');
			$this->assertEquals(3, count($res));
			$this->assertContains(array("name" => "trunk", "isDirectory" => true, "path" => "/trunk/"), $res);
			$this->assertContains(array("name" => "branches", "isDirectory" => true, "path" => "/branches/"), $res);
			$this->assertContains(array("name" => "tags", "isDirectory" => true, "path" => "/tags/"), $res);
			$res = USVN_SVNUtils::listSvn('tests/tmp/svn directory', '/trunk');
			$this->assertEquals(2, count($res));
			$this->assertContains(array("name" => "testdir", "isDirectory" => true, "path" => "/trunk/testdir/"), $res);
			$this->assertContains(array("name" => "testfile", "isDirectory" => false, "path" => "/trunk/testfile"), $res);
		}
	}

	public function test_listSvnSpecialCharDirectory()
	{
		if (!(substr(php_uname(), 0, 7) == "Windows")) {
			USVN_SVNUtils::createSvn('tests/tmp/svn directory');
			USVN_SVNUtils::createStandardDirectories('tests/tmp/svn directory');
			USVN_SVNUtils::checkoutSvn('tests/tmp/svn directory', 'tests/tmp/out');
			$path = getcwd();
			chdir('tests/tmp/out');
			mkdir('trunk/c++');
			`svn add trunk/c++`;
			mkdir('trunk/test space');
			`svn add trunk/test\ space`;
			`svn commit --non-interactive -m Test`;
			chdir($path);
			$res = USVN_SVNUtils::listSvn('tests/tmp/svn directory', '/trunk');
			$this->assertEquals(2, count($res));
			$this->assertContains(array("name" => "c++", "isDirectory" => true, "path" => "/trunk/c++/"), $res);
			$this->assertContains(array("name" => "test space", "isDirectory" => true, "path" => "/trunk/test space/"), $res);
		}
	}

	public function test_listSvnSpaceDirectory()
	{
		if (!(substr(php_uname(), 0, 7) == "Windows")) {
			USVN_SVNUtils::createSvn('tests/tmp/svn directory');
			USVN_SVNUtils::createStandardDirectories('tests/tmp/svn directory');
			USVN_SVNUtils::checkoutSvn('tests/tmp/svn directory', 'tests/tmp/out');
			$path = getcwd();
			chdir('tests/tmp/out');
			mkdir('trunk/test space');
			`svn add trunk/test\ space`;
			`touch trunk/test\ space/tutu`;
			`svn add trunk/test\ space/tutu`;
			`svn commit --non-interactive -m Test`;
			chdir($path);
			$res = USVN_SVNUtils::listSvn('tests/tmp/svn directory', '/trunk/test space');
			$this->assertEquals(1, count($res));
			$this->assertContains(array("name" => "tutu", "isDirectory" => false, "path" => "/trunk/test space/tutu"), $res);
		}
	}

	public function test_listSvnOrdAlpha()
	{
		if (!(substr(php_uname(), 0, 7) == "Windows")) {
			USVN_SVNUtils::createSvn('tests/tmp/svn directory');
			USVN_SVNUtils::createStandardDirectories('tests/tmp/svn directory');
			USVN_SVNUtils::checkoutSvn('tests/tmp/svn directory', 'tests/tmp/out');
			$path = getcwd();
			chdir('tests/tmp/out');
			mkdir('trunk/a');
			`svn add trunk/a`;
			mkdir('trunk/b');
			`svn add trunk/b`;
			mkdir('trunk/A');
			`svn add trunk/A`;
			mkdir('trunk/B');
			`svn add trunk/B`;
			`svn commit --non-interactive -m Test`;
			chdir($path);
			$res = USVN_SVNUtils::listSvn('tests/tmp/svn directory', '/trunk');
			$this->assertEquals(4, count($res));
			$this->assertEquals(
				array(
					array(
						"name" => "a",
						"isDirectory" => true,
						"path" => "/trunk/a/"),
					array(
						"name" => "A",
						"isDirectory" => true,
						"path" => "/trunk/A/"),
					array(
						"name" => "b",
						"isDirectory" => true,
						"path" => "/trunk/b/"),
					array(
						"name" => "B",
						"isDirectory" => true,
						"path" => "/trunk/B/")
				), $res);
		}
	}

	public function test_listSvnBadDir()
	{
		try {
            USVN_SVNUtils::listSvn('tests/tmp/svn directory2', 'tests/tmp/out');
		}
		catch (USVN_Exception $e) {
			return ;
		}
		$this->fail();
    }

	public function test_parseSvnVersion()
	{
		$this->assertEquals(array(1, 1, 4), USVN_SVNUtils::parseSvnVersion("1.1.4\n"));
	}

	public function test_getSvnVersion()
	{
		$version = USVN_SVNUtils::getSvnVersion();
		$this->assertEquals("1", $version[0]);
	}

	public function test_getRepositoryPath()
	{
		if(strtoupper(substr(PHP_OS, 0,3)) == 'WIN' ) {
			$this->assertEquals('"file:///' . str_replace('\\', '/', getcwd()) . '/tata/tutu"', USVN_SVNUtils::getRepositoryPath("./tata/tutu"));
		}
		else {
			$this->assertEquals("'file:///tata/tutu'", USVN_SVNUtils::getRepositoryPath("//.././tata/tutu"));
			$this->assertEquals("'file://" . getcwd() . "/tata/tutu'", USVN_SVNUtils::getRepositoryPath("./tata/tutu"));
			$this->assertEquals("'file:///tutu'", USVN_SVNUtils::getRepositoryPath("//tata/../tutu"));
			$this->assertEquals("'file:///'", USVN_SVNUtils::getRepositoryPath("//.."));
		}
	}
*/

	public function test_getSubversionUrl()
	{
		$this->assertEquals("http://localhost/test/toto/tutu", USVN_SVNUtils::getSubversionUrl("test", "/toto/tutu"));
	}
}

// Call USVN_SVNUtilsTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "USVN_SVNUtilsTest::main") {
    USVN_SVNUtilsTest::main();
}
?>
