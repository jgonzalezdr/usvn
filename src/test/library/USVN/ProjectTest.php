<?php

/**
 * Usefull methods for project management
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.6
 * @package usvn
 * @subpackage Table
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id$
 */
// Call USVN_ProjectsTest::main() if this source file is executed directly.
if( !defined( "PHPUnit_MAIN_METHOD" ) )
{
	define( "PHPUnit_MAIN_METHOD", "USVN_ProjectsTest::main" );
}

require_once 'test/TestSetup.php';

/**
 * @coversDefaultClass USVN_Projects
 */
class USVN_ProjectsTest extends USVN_Test_DBTestCase
{
	private $_user;

	public static function main()
	{
		$suite = new PHPUnit_Framework_TestSuite( "USVN_ProjectsTest" );
		$result = PHPUnit_TextUI_TestRunner::run( $suite );
	}

	public function setUp()
	{
		parent::setUp();
		$table = new USVN_Db_Table_Users();

		$this->_user = $table->fetchNew();
		$this->_user->setFromArray( array(
			'users_login' => 'test',
			'users_password' => 'password',
			'users_firstname' => 'firstname',
			'users_lastname' => 'lastname',
			'users_email' => 'email@email.fr' ) );
		$this->_user->save();
	}

	private function _checkSVNRepositoryExists( $project_name )
	{
		$this->assertTrue( USVN_SVNUtils::isSVNRepository( TEST_REPOS_PATH . '/' . $project_name ), "The SVN repository hasn't been created" );
	}

	private function _checkReturnedProject( $project_name, $project )
	{
		$this->assertEquals( $project_name, $project->projects_name, "Returned project does not correspond to the expected one" );
	}

	private function _checkCreateProjectWithGroup( $project_name, $user_in_group, $user_is_admin )
	{
		$table_projects = new USVN_Db_Table_Projects();
		$this->assertTrue( $table_projects->isAProject( $project_name ), "The project hasn't been created" );
		$project = $table_projects->findByName( $project_name );

		$this->_checkSVNRepositoryExists( $project_name );

		$table_groups = new USVN_Db_Table_Groups();
		$this->assertTrue( $table_groups->isAGroup( $project_name ), "The project group hasn't been created" );
		$group = $table_groups->findByGroupsName( $project_name );

		$table_grp2prj = new USVN_Db_Table_GroupsToProjects();
		$groups_to_projects = $table_grp2prj->fetchRow( array( "projects_id = ?" => $project->id, "groups_id = ?" => $group->id ) );
		$this->assertNotNull( $groups_to_projects, "The group is not associated to the project" );

		$this->assertEquals( $user_in_group, $group->hasUser( $this->_user ),
							 "The user is " . ($user_in_group ? "not " : "") . "member of the project group" );
		$this->assertEquals( $user_in_group, $group->userIsGroupLeader( $this->_user ),
							 "The user is " . ($user_in_group ? "not " : "") . "leader of the project group" );
		$this->assertEquals( $user_is_admin, $project->userIsAdmin( $this->_user ),
							 "The user is " . ($user_is_admin ? "not " : "") . " admin of the project" );
	}

	private function _checkCreateProjectWithoutGroup( $project_name, $user_is_admin )
	{
		$table_projects = new USVN_Db_Table_Projects();
		$this->assertTrue( $table_projects->isAProject( $project_name ), "The project hasn't been created" );
		$project = $table_projects->findByName( $project_name );

		$this->_checkSVNRepositoryExists( $project_name );

		$table_groups = new USVN_Db_Table_Groups();
		$this->assertFalse( $table_groups->isAGroup( $project_name ), "The project group has been created" );

		$this->assertEquals( $user_is_admin, $project->userIsAdmin( $this->_user ),
							 "The user is " . ($user_is_admin ? "not " : "") . " admin of the project" );
	}

	private function _checkRightsOnPathForGroup( $project_name, $group_name, $path, $read, $write )
	{
		$table_projects = new USVN_Db_Table_Projects();
		$project = $table_projects->findByName( $project_name );

		$table_groups = new USVN_Db_Table_Groups();
		$group = $table_groups->findByGroupsName( $group_name );

		$table_groups_rights = new USVN_Db_Table_GroupsToFilesRights();
		$table_rights = new USVN_Db_Table_FilesRights();

		$right = $table_rights->fetchRow( array( "files_rights_path = ?" => $path, "projects_id = ?" => $project->id ) );
		$this->assertNotNull( $right, "Rights on $path not found" );

		$group_rights = $table_groups_rights->fetchRow( array( "files_rights_id = ?" => $right->id, "groups_id = ?" => $group->id ) );
		$this->assertNotNull( $group_rights, "Groups rights not found on $path" );

		$this->assertEquals( $read, $group_rights->files_rights_is_readable, "Wrong read rights for $path" );
		$this->assertEquals( $write, $group_rights->files_rights_is_writable, "Wrong write rights for $path" );
	}

	private function _checkRepoStructureEmptyDir( $project_name )
	{
		$this->assertEquals( 0, count( USVN_SVNUtils::listSVN( TEST_REPOS_PATH . '/' . $project_name, '/' ) ) );
	}

	private function _checkRepoStructureStdDir( $project_name )
	{
		$expected_svn_list = array(
			array(
				'name' => 'branches',
				'isDirectory' => true,
				'path' => '/branches/'
			),
			array(
				'name' => 'tags',
				'isDirectory' => true,
				'path' => '/tags/'
			),
			array(
				'name' => 'trunk',
				'isDirectory' => true,
				'path' => '/trunk/'
			) );
		$this->assertEquals( $expected_svn_list, USVN_SVNUtils::listSVN( TEST_REPOS_PATH . '/' . $project_name, '/' ) );
	}

	public function test_createProject_WithMultiDirectory()
	{
		// Setup
		$project_name = 'ok/TestProject';

		// Exercise
		$project = USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, false, false, false, false );

		// Verify
		$this->_checkSVNRepositoryExists( $project_name );
	}

	public function test_createProject_WithGroupWithAdmin()
	{
		// Setup
		$project_name = 'TestProject';

		// Exercise
		$project = USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, true, true, true, false );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithGroup( $project_name, true, true );

		$this->_checkRightsOnPathForGroup( $project_name, $project_name, '/', 1, 1 );

		$this->_checkRepoStructureEmptyDir( $project_name );
	}

	public function test_createProject_WithGroupWithAdminWithStdDir()
	{
		// Setup
		$project_name = 'TestProject';

		// Exercise
		$project = USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, true, true, true, true );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithGroup( $project_name, true, true );

		$this->_checkRightsOnPathForGroup( $project_name, $project_name, '/', 1, 0 );
		$this->_checkRightsOnPathForGroup( $project_name, $project_name, '/branches', 1, 1 );
		$this->_checkRightsOnPathForGroup( $project_name, $project_name, '/tags', 1, 1 );
		$this->_checkRightsOnPathForGroup( $project_name, $project_name, '/trunk', 1, 1 );

		$this->_checkRepoStructureStdDir( $project_name );
	}

	public function test_createProject_WithGroupButNotGroupMemberWithAdmin()
	{
		// Setup
		$project_name = 'TestProject';

		// Exercise
		$project = USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, true, false, true, false );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithGroup( $project_name, false, true );

		$this->_checkRightsOnPathForGroup( $project_name, $project_name, '/', 1, 1 );

		$this->_checkRepoStructureEmptyDir( $project_name );
	}

	public function test_createProject_WithGroupWithoutAdmin()
	{
		// Setup
		$project_name = 'TestProject';

		// Exercise
		$project = USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, true, true, false, false );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithGroup( $project_name, true, false );

		$this->_checkRightsOnPathForGroup( $project_name, $project_name, '/', 1, 1 );

		$this->_checkRepoStructureEmptyDir( $project_name );
	}

	public function test_CreateProject_WithoutGroupWithAdmin()
	{
		// Setup
		$project_name = 'TestProject';

		// Exercise
		$project = USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, false, false, true, false );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithoutGroup( $project_name, true );

		$this->_checkRepoStructureEmptyDir( $project_name );
	}

	public function test_createProject_WithoutGroupWithAdminButWithStdDir()
	{
		// Setup
		$project_name = 'TestProject';

		// Exercise
		$project = USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, false, false, true, true );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithoutGroup( $project_name, true );

		$this->_checkRepoStructureStdDir( $project_name );
	}

	public function test_createProject_WithoutGroupWithoutAdmin()
	{
		// Setup
		$project_name = 'TestProject';

		// Exercise
		$project = USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, false, false, false, false );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithoutGroup( $project_name, false );

		$this->_checkRepoStructureEmptyDir( $project_name );
	}

	public function test_createProject_WithoutGroupWithoutAdminButWithStdDir()
	{
		// Setup
		$project_name = 'TestProject';

		// Exercise
		$project = USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, false, false, false, true );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithoutGroup( $project_name, false );

		$this->_checkRepoStructureStdDir( $project_name );
	}

	public function test_createProject_WithGroupWithInvalidAdmin()
	{
		// Setup
		$project_name = 'TestProject';
		$admin_name = "fake";

		// Exercise
		try
		{
			USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ), 
					$admin_name, true, true, true, true );

		}

		// Verify
		catch( USVN_Exception $e )
		{
			// Exception thrown as expected
			$this->assertEquals( "Login $admin_name not found", $e->getMessage(), "Wrong exception thrown" );
			
			$table = new USVN_Db_Table_Projects();
			$this->assertFalse( $table->isAProject( $project_name ), "The project shouldn't have been created" );
			
			return;
		}
		$this->fail( "The project creation should have failed" );
	}
	
	public function test_createProject_WithGroupButGroupAlreadyExisting()
	{
		// Setup
		$project_name = 'TestProject';

		$table = new USVN_Db_Table_Groups();
		$group = $table->createRow( array( "groups_name" => $project_name ) );
		$group->save();

		// Exercise
		try
		{
			USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ), 
					$this->_user->users_login, true, true, true, false );
		}

		// Verify
		catch( USVN_Exception $e )
		{
			// Exception thrown as expected
			$this->assertEquals( "Group $project_name already exists.", $e->getMessage(), "Wrong exception thrown" );
			return;
		}
		$this->fail( "The project creation should have failed" );
		
	}
	
	/* public function testCreateProjectSubDirectorySVNAlreadyExist()
	  {
	  USVN_Project::createProject(array('projects_name' => 'TestProject',  'projects_start_date' => '1984-12-03 00:00:00'), "test", true, true, true, true);
	  $new_project = 'TestProject/OtherProject';

	  try {
	  USVN_Project::createProject(array('projects_name' => $new_project,  'projects_start_date' => '1984-12-03 00:00:00'), "test", true, true, true, true);
	  }
	  catch (USVN_Exception $e) {
	  $this->assertContains("Can't create subversion repository", $e->getMessage());
	  return;
	  }
	  $this->fail("Il n'y a pas eu d'exception pour la creation d'un projet dans une mauvaise arborescence...");
	  } */

	public function test_deleteProject()
	{
		// Setup
		$project_name = 'TestProject';
		$non_related_group_name = 'TestGroup';
		
		$table_groups = new USVN_Db_Table_Groups();
		$non_related_group = $table_groups->createRow( array( "groups_name" => $non_related_group_name ) );
		$non_related_group->save();

		USVN_Project::createProject( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
				$this->_user->users_login, true, true, true, false );

		// Exercise
		USVN_Project::deleteProject( $project_name );

		// Verify
		$table_projects = new USVN_Db_Table_Projects();
		$this->assertFalse( $table_projects->isAProject( $project_name ), "The project hasn't been deleted" );

		$this->assertFalse( $table_groups->isAGroup( $project_name ), "The project group hasn't been deleted" );
		$this->assertTrue( $table_groups->isAGroup( $non_related_group_name ), "The non-related group has been deleted" );

		$this->assertFalse( USVN_SVNUtils::isSVNRepository( TEST_REPOS_PATH . '/' . $project_name ), "The SVN repository hasn't been deleted" );
	}

	public function test_deleteProject_NotExisting()
	{
		// Setup
		$project_name = 'TestProject';

		// Exercise
		try
		{
			USVN_Project::deleteProject( $project_name );
		}

		// Verify
		catch( USVN_Exception $e )
		{
			// Exception thrown as expected
			$this->assertEquals( "Project $project_name doesn't exist.", $e->getMessage(), "Wrong exception thrown" );
			return;
		}
		$this->fail( "The project deletion should have failed" );
	}

	/**
	 * Import file(s) into subversion repository
	 *
	 * @param string $repository path to server repository
	 * @param string path to directory to import
	 */
    private function _svnImport( $repository, $path )
    {
        $repository_url = USVN_SVNUtils::getRepositoryFileUrl( TEST_REPOS_PATH . "/" . $repository );
        $escaped_path = escapeshellarg( $path );
        $cmd = USVN_SVNUtils::svnCommand( "import --non-interactive --username USVN -m \"" . T_("Commit by USVN") ."\" $escaped_path $repository_url" );
		$message = USVN_ConsoleUtils::runCmdCaptureMessage($cmd, $return);
		if( $return )
		{
			throw new USVN_Exception( T_("Can't import into subversion repository.\nCommand:\n%s\n\nError:\n%s"), $cmd, $message );
		}
    }
	
	private function _createAccessFile( $wc_path, $access_file_extra )
	{
		file_put_contents( $wc_path . "/Template1/svnaccess.json", 
'{
	"groups": [ "group1", "group2", "group3" ],
	"access": {
		"": {
			"group1": "rw",
			"group2": "r",
			"group3": "r"' . ($access_file_extra != NULL ? $access_file_extra : '') . '
		},
		"/dir1": {
			"group2": "rw",
			"group3": "-"
		}
	}
}' );
	}

	private function _setupTemplateRepo( $create_access_file, $access_file_extra = NULL )
	{
		$templates_repo_name = 'RepoTemplates';
		
		USVN_Project::createProject( array( 'projects_name' => $templates_repo_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
				$this->_user->users_login, true, true, true, false );

		$wc_path = TESTING_DIR . "/" . $templates_repo_name . "WC";
		mkdir( $wc_path );
		mkdir( $wc_path . "/Template1" );
		mkdir( $wc_path . "/Template1/dir1" );
		mkdir( $wc_path . "/Template1/dir2" );
		mkdir( $wc_path . "/Template1/dir3" );
		mkdir( $wc_path . "/Template2" );
		mkdir( $wc_path . "/Template2/dir7" );
		mkdir( $wc_path . "/Template2/dir8" );
		mkdir( $wc_path . "/Template2/dir9" );
		
		if( $create_access_file )
		{
			$this->_createAccessFile( $wc_path, $access_file_extra );
		}
		
		$this->_svnImport( $templates_repo_name, $wc_path );

		file_put_contents( USVN_CONFIG_FILE, 'projectTemplates.repoName = "' . $templates_repo_name . '"', FILE_APPEND );
		$config = new USVN_Config_Ini( USVN_CONFIG_FILE, USVN_CONFIG_SECTION );
		Zend_Registry::set( 'config', $config );
	}

	private function _checkRepoStructureTemplate1( $project_name )
	{
		$expected_svn_list = array(
			array(
				'name' => 'dir1',
				'isDirectory' => true,
				'path' => '/dir1/'
			),
			array(
				'name' => 'dir2',
				'isDirectory' => true,
				'path' => '/dir2/'
			),
			array(
				'name' => 'dir3',
				'isDirectory' => true,
				'path' => '/dir3/'
			) );
		$this->assertEquals( $expected_svn_list, USVN_SVNUtils::listSVN( TEST_REPOS_PATH . '/' . $project_name, '/' ) );
	}

	public function test_createProjectFromTemplate_WithAdmin()
	{
		// Setup
		$project_name = 'TestProject';
		$template_name = "Template1";
		
		$this->_setupTemplateRepo( false );

		// Exercise
		$project = USVN_Project::createProjectFromTemplate( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, $template_name, true, true );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithoutGroup( $project_name, true );

		$this->_checkRepoStructureTemplate1( $project_name );
	}

	public function test_createProjectFromTemplate_WithoutAdmin()
	{
		// Setup
		$project_name = 'TestProject';
		$template_name = "Template1";
		
		$this->_setupTemplateRepo( false );

		// Exercise
		$project = USVN_Project::createProjectFromTemplate( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, $template_name, false, true );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithoutGroup( $project_name, false );

		$this->_checkRepoStructureTemplate1( $project_name );
	}
	
	public function test_createProjectFromTemplate_WithAdminWithAccessFile()
	{
		// Setup
		$project_name = 'TestProject';
		$template_name = "Template1";
		
		$this->_setupTemplateRepo( true );

		// Exercise
		$project = USVN_Project::createProjectFromTemplate( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, $template_name, true, true );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithoutGroup( $project_name, true );
		
		$table_groups = new USVN_Db_Table_Groups();
		$created_groups = $table_groups->allGroupsLike( $project_name )->toArray();
		$this->assertCount( 3, $created_groups, "Wrong number of created project groups" );
		
		$this->assertEquals( $project_name . "-group1", $created_groups[0]['groups_name'], "Unexpected group name" );
		$this->assertEquals( $project_name . "-group2", $created_groups[1]['groups_name'], "Unexpected group name" );
		$this->assertEquals( $project_name . "-group3", $created_groups[2]['groups_name'], "Unexpected group name" );

		$this->_checkRightsOnPathForGroup( $project_name, $project_name . "-group1", '/', 1, 1 );
		$this->_checkRightsOnPathForGroup( $project_name, $project_name . "-group2", '/', 1, 0 );
		$this->_checkRightsOnPathForGroup( $project_name, $project_name . "-group3", '/', 1, 0 );

		$this->_checkRightsOnPathForGroup( $project_name, $project_name . "-group2", '/dir1', 1, 1 );
		$this->_checkRightsOnPathForGroup( $project_name, $project_name . "-group3", '/dir1', 0, 0 );

		$this->_checkRepoStructureTemplate1( $project_name );
	}

	public function test_createProjectFromTemplate_WithAdminWithAccessFileButUndefinedProjectGroup()
	{
		// Setup
		$project_name = 'TestProject';
		$template_name = "Template1";
		$extra_group = "group4";
		
		$this->_setupTemplateRepo( true, ', "'.$extra_group.'": "rw"' );

		// Exercise
		try
		{
			$project = USVN_Project::createProjectFromTemplate( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, $template_name, true, true );
		}
		
		// Verify
		catch( USVN_Exception $e )
		{
			// Exception thrown as expected
			$this->assertEquals( "Project group $extra_group hasn't been defined in access file.", $e->getMessage(), "Wrong exception thrown" );
			return;
		}
		$this->fail( "The project creation should have failed" );
	}

	public function test_createProjectFromTemplate_WithAdminWithAccessFileWithPredefinedGroup()
	{
		// Setup
		$project_name = 'TestProject';
		$template_name = "Template1";
		$extra_group = "TestGroup";
		
		$this->_setupTemplateRepo( true, ', "#'.$extra_group.'": "rw"' );

		$table_groups = new USVN_Db_Table_Groups();
		$new_group = $table_groups->createRow( array( "groups_name" => $extra_group ) );
		$new_group->save();
		
		// Exercise
		$project = USVN_Project::createProjectFromTemplate( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, $template_name, true, true );

		// Verify
		$this->_checkReturnedProject( $project_name, $project );

		$this->_checkCreateProjectWithoutGroup( $project_name, true );
		
		$created_groups = $table_groups->allGroupsLike( $project_name )->toArray();
		$this->assertCount( 3, $created_groups, "Wrong number of created project groups" );
		
		$this->assertEquals( $project_name . "-group1", $created_groups[0]['groups_name'], "Unexpected group name" );
		$this->assertEquals( $project_name . "-group2", $created_groups[1]['groups_name'], "Unexpected group name" );
		$this->assertEquals( $project_name . "-group3", $created_groups[2]['groups_name'], "Unexpected group name" );

		$this->_checkRightsOnPathForGroup( $project_name, $project_name . "-group1", '/', 1, 1 );
		$this->_checkRightsOnPathForGroup( $project_name, $project_name . "-group2", '/', 1, 0 );
		$this->_checkRightsOnPathForGroup( $project_name, $project_name . "-group3", '/', 1, 0 );
		$this->_checkRightsOnPathForGroup( $project_name, $extra_group, '/', 1, 1 );

		$this->_checkRightsOnPathForGroup( $project_name, $project_name . "-group2", '/dir1', 1, 1 );
		$this->_checkRightsOnPathForGroup( $project_name, $project_name . "-group3", '/dir1', 0, 0 );

		$this->_checkRepoStructureTemplate1( $project_name );
	}
	
	public function test_createProjectFromTemplate_WithAdminWithAccessFileButUnexistingPredefinedGroup()
	{
		// Setup
		$project_name = 'TestProject';
		$template_name = "Template1";
		$extra_group = "TestGroup";
		
		$this->_setupTemplateRepo( true, ', "#'.$extra_group.'": "rw"' );

		// Exercise
		try
		{
			$project = USVN_Project::createProjectFromTemplate( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
						$this->_user->users_login, $template_name, true, true );
		}
		
		// Verify
		catch( USVN_Exception $e )
		{
			// Exception thrown as expected
			$this->assertEquals( "Pre-defined group $extra_group doesn't exist.", $e->getMessage(), "Wrong exception thrown" );
			return;
		}
		$this->fail( "The project creation should have failed" );
	}
	
	public function test_deleteProject_CreatedFromTemplate()
	{
		// Setup
		$project_name = 'TestProject';
		$template_name = "Template1";
		$non_related_group_name = 'TestGroup';
		
		$this->_setupTemplateRepo( false );

		$table_groups = new USVN_Db_Table_Groups();
		$non_related_group = $table_groups->createRow( array( "groups_name" => $non_related_group_name ) );
		$non_related_group->save();

		USVN_Project::createProjectFromTemplate( array( 'projects_name' => $project_name, 'projects_start_date' => '1984-12-03 00:00:00' ),
				$this->_user->users_login, $template_name, true, true );

		// Exercise
		USVN_Project::deleteProject( $project_name );

		// Verify
		$table_projects = new USVN_Db_Table_Projects();
		$this->assertFalse( $table_projects->isAProject( $project_name ), "The project hasn't been deleted" );

		$this->assertCount( 0, $table_groups->allGroupsLike( $project_name ), "The project groups haven't been deleted" );
		$this->assertTrue( $table_groups->isAGroup( $non_related_group_name ), "The non-related group has been deleted" );

		$this->assertFalse( USVN_SVNUtils::isSVNRepository( TEST_REPOS_PATH . '/' . $project_name ), "The SVN repository hasn't been deleted" );
	}
}

// Call USVN_ProjectsTest::main() if this source file is executed directly.
if( PHPUnit_MAIN_METHOD == "USVN_ProjectsTest::main" )
{
	USVN_ProjectsTest::main();
}
?>
