<?php
/**
 * Class to test project's model
 *
 * @author Team USVN <contact@usvn.info>
 * @link http://www.usvn.info
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2-en.txt CeCILL V2
 * @copyright Copyright 2007, Team USVN
 * @since 0.5
 * @package Db
 * @subpackage Table
 *
 * This software has been written at EPITECH <http://www.epitech.net>
 * EPITECH, European Institute of Technology, Paris - FRANCE -
 * This project has been realised as part of
 * end of studies project.
 *
 * $Id$
 */

// Call USVN_Db_Table_ProjectsTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
	define("PHPUnit_MAIN_METHOD", "USVN_Db_Table_ProjectsTest::main");
}

require_once 'test/TestSetup.php';

/**
 * @coversDefaultClass USVN_Db_Table_Projects
 */
class USVN_Db_Table_ProjectsTest extends USVN_Test_DBTestCase
{
	public static function main() 
	{
		$suite  = new PHPUnit_Framework_TestSuite("USVN_Db_Table_ProjectsTest");
		$result = PHPUnit_TextUI_TestRunner::run($suite);
	}

	public function test_InsertProject_Ok()
	{
		$table = new USVN_Db_Table_Projects();
		$project = $table->fetchNew();
		$project->setFromArray(array('projects_name' => 'InsertProjectOk',  'projects_start_date' => '1984-12-03 00:00:00'));
		$project->save();

		$this->assertTrue( $table->isAProject('InsertProjectOk'), "The project has not been created" );
	}

	public function test_InsertProject_WithMultiDirectory_Ok()
	{
		$table = new USVN_Db_Table_Projects();
		$project = $table->fetchNew();
		$project->setFromArray(array('projects_name' => 'test/ok/InsertProjectOk',  'projects_start_date' => '1984-12-03 00:00:00'));
		$project->save();

		$this->assertTrue( $table->isAProject('test/ok/InsertProjectOk'), "The project has not been created" );
	}

	public function test_InsertUserToProjects()
	{
		$table = new USVN_Db_Table_Users();
		$obj = $table->fetchNew();
		$obj->setFromArray(array(
		'users_login' 		=> 'TestOk',
		'users_password' 	=> 'password',
		'users_firstname' 	=> 'firstname',
		'users_lastname' 	=> 'lastname',
		'users_email' 		=> 'email@email.fr'));
		$obj->save();
		$users = $table->findByName("TestOk");

		$table = new USVN_Db_Table_Projects();
		$project = $table->fetchNew();
		$project->setFromArray(array('projects_name' => 'InsertProjectOk',  'projects_start_date' => '1984-12-03 00:00:00'));
		$project->save();
		$projects = $table->findByName("InsertProjectOk");

		$table->AddUserToProject($users, $projects);
		$UserToProject = new USVN_Db_Table_UsersToProjects();
		$this->assertEquals(count($UserToProject->fetchRow(array('users_id = ?' => $users->users_id, 'projects_id = ?' => $projects->projects_id ))), 1);

		$table->DeleteUserToProject($users, $projects);
		$this->assertEquals(count($UserToProject->fetchRow(array('users_id = ?' => $users->users_id, 'projects_id = ?' => $projects->projects_id ))), 0);
	}

	public function test_InsertProject_SVNAlreadyExist_Ok()
	{
		USVN_SVNUtils::createSVN( TEST_REPOS_PATH . '/InsertProjectOk' );

		$table = new USVN_Db_Table_Projects();
		$project = $table->fetchNew();
		$project->setFromArray(array('projects_name' => 'InsertProjectOk',  'projects_start_date' => '1984-12-03 00:00:00'));
		$project->save();

		$this->assertTrue( $table->isAProject('InsertProjectOk'), "The project has not been created" );
		$this->assertTrue( USVN_SVNUtils::isSVNRepository( TEST_REPOS_PATH . '/InsertProjectOk'), "The SVN repository has not been created" );
	}

	public function test_InsertProject_NoName()
	{
		$table = new USVN_Db_Table_Projects();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('projects_name' => ''));
		try {
			$_id = $obj->save();
		}
		catch (USVN_Exception $e) {
			$this->assertContains("The project's name is empty", $e->getMessage());
			return;
		}
		$this->fail("Il n'y a pas eu d'exception pour un mauvais nom...");
	}

	public function test_InsertProject_NoName2()
	{
		$table = new USVN_Db_Table_Projects();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('projects_name' => "   \t    "));
		try {
			$id = $obj->save();
		}
		catch (USVN_Exception $e) {
			$this->assertContains("The project's name is empty", $e->getMessage());
			return;
		}
		$this->fail("Il n'y a pas eu d'exception pour un mauvais nom...");
	}

	public function test_InsertProject_InvalidName()
	{
		$table = new USVN_Db_Table_Projects();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('projects_name' => "   !!!    "));
		try {
			$id = $obj->save();
		}
		catch (USVN_Exception $e) {
			$this->assertContains("The project's name is invalid", $e->getMessage());
			return;
		}
		$this->fail("Il n'y a pas eu d'exception pour un mauvais nom...");
	}

	public function test_UpdateProject()
	{
		$table = new USVN_Db_Table_Projects();
		$obj = $table->fetchNew();
		$obj->name = 'UpdateProjectOk';
		$obj->save();
		$obj->name = 'UpdateProjectOk2';
		try {
			$obj->save();
		}
		catch (Exception $e) {
			$this->assertTrue($table->isAProject('UpdateProjectOk'), "Le projet UpdateProjectOk n'existe plus");
			return ;
		}
		$this->fail("Le projet a ete renomme");
	}

	public function test_DeleteProject()
	{
		$table = new USVN_Db_Table_Projects();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('projects_name' => 'InsertProjectOk',  'projects_start_date' => '1984-12-03 00:00:00'));
		$obj->save();

		$this->assertTrue($table->isAProject('InsertProjectOk'), "Le projet n'est pas correctement cree");

		$obj->delete();

		$this->assertFalse($table->isAProject('InsertProjectOk'), "Le projet n'est pas supprime");
	}

	public function test_fetchAllAssignedTo()
	{
		$table_user = new USVN_Db_Table_Users();
		$user = $table_user->fetchNew();
		$user->setFromArray(array('users_login' 		=> 'test',
		'users_password' 	=> 'password',
		'users_firstname' 	=> 'firstname',
		'users_lastname' 	=> 'lastname',
		'users_email' 		=> 'email@email.fr'));
		$user->save();

		$table_project = new USVN_Db_Table_Projects();
		$project = $table_project->fetchNew();
		$project->setFromArray(array('projects_name' => 'InsertProjectOk',  'projects_start_date' => '1984-12-03 00:00:00'));
		$project->save();
		$project2 = $table_project->fetchNew();
		$project2->setFromArray(array('projects_name' => 'Project2',  'projects_start_date' => '1984-12-03 00:00:00'));
		$project2->save();

		$group_table = new USVN_Db_Table_Groups();
		$group_table->insert(array("groups_id" => 2, "groups_name" => "toto"));
		$group = $group_table->find(2)->current();

		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 0);
		$project->addUser($user);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 1);
		$project2->addUser($user);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 2);
		$project->deleteUser($user);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 1);
		$project->addGroup($group);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 1);
		$group->addUser($user);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 2);
		$project->addUser($user);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 2);
		$project->deleteUser($user);
		$project2->deleteUser($user);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 1);
		$project->deleteGroup($group);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 0);
	}


	public function test_fetchAllAssignedToUserInTwoGroup()
	{
		$table_user = new USVN_Db_Table_Users();
		$user = $table_user->fetchNew();
		$user->setFromArray(array('users_login' 			=> 'test',
		'users_password' 	=> 'password',
		'users_firstname' 	=> 'firstname',
		'users_lastname' 	=> 'lastname',
		'users_email' 		=> 'email@email.fr'));
		$user->save();

		$table_project = new USVN_Db_Table_Projects();
		$project = $table_project->fetchNew();
		$project->setFromArray(array('projects_name' => 'InsertProjectOk',  'projects_start_date' => '1984-12-03 00:00:00'));
		$project->save();
		$project2 = $table_project->fetchNew();
		$project2->setFromArray(array('projects_name' => 'Project2',  'projects_start_date' => '1984-12-03 00:00:00'));
		$project2->save();

		$group_table = new USVN_Db_Table_Groups();
		$group_table->insert(array("groups_id" => 2, "groups_name" => "toto"));
		$group = $group_table->find(2)->current();

		$group_table = new USVN_Db_Table_Groups();
		$group_table->insert(array("groups_id" => 3, "groups_name" => "titi"));
		$group2 = $group_table->find(3)->current();

		$project->addGroup($group);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 0);
		$group->addUser($user);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 1);
		$group2->addUser($user);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 1);
		$project->addGroup($group2);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 1);
	}

	public function test_fetchAllAssignedTwoUserInGroup()
	{
		$table_user = new USVN_Db_Table_Users();
		$user = $table_user->fetchNew();
		$user->setFromArray(array('users_login' 		=> 'test',
		'users_password' 	=> 'password',
		'users_firstname' 	=> 'firstname',
		'users_lastname' 	=> 'lastname',
		'users_email' 		=> 'email@email.fr'));
		$user->save();
		$user2 = $table_user->fetchNew();
		$user2->setFromArray(array('users_login' 		=> 'test2',
		'users_password' 	=> 'password',
		'users_firstname' 	=> 'firstname',
		'users_lastname' 	=> 'lastname',
		'users_email' 		=> 'email@email.fr'));
		$user2->save();

		$table_project = new USVN_Db_Table_Projects();
		$project = $table_project->fetchNew();
		$project->setFromArray(array('projects_name' => 'InsertProjectOk',  'projects_start_date' => '1984-12-03 00:00:00'));
		$project->save();
		$project2 = $table_project->fetchNew();
		$project2->setFromArray(array('projects_name' => 'Project2',  'projects_start_date' => '1984-12-03 00:00:00'));
		$project2->save();

		$group_table = new USVN_Db_Table_Groups();
		$group_table->insert(array("groups_id" => 2, "groups_name" => "toto"));
		$group = $group_table->find(2)->current();

		$group_table = new USVN_Db_Table_Groups();
		$group_table->insert(array("groups_id" => 3, "groups_name" => "titi"));
		$group = $group_table->find(3)->current();

		$project->addGroup($group);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 0);
		$group->addUser($user);
		$group->addUser($user2);
		$this->assertEquals(count($table_project->fetchAllAssignedTo($user)), 1);
	}

	public function test_fetchAllAssignedToAll()
	{
		$users = array('stem', 'noplay', 'crivis_s', 'duponc_j', 'dolean_j', 'billar_m', 'attal_m', 'joanic_g', 'guyoll_o');
		foreach ($users as $user) {
			${$user} = $this->createUser($user, "password");
		}

		/* @var $stem USVN_Db_Table_Row_User */
		/* @var $noplay USVN_Db_Table_Row_User */
		/* @var $crivis_s USVN_Db_Table_Row_User */
		/* @var $duponc_j USVN_Db_Table_Row_User */
		/* @var $dolean_j USVN_Db_Table_Row_User */
		/* @var $billar_m USVN_Db_Table_Row_User */
		/* @var $attal_m USVN_Db_Table_Row_User */
		/* @var $joanic_g USVN_Db_Table_Row_User */
		/* @var $guyoll_o USVN_Db_Table_Row_User */

		$projects = array('usvn', 'private', 'website', 'proj4', 'proj5', 'proj6', 'proj7', 'proj8', 'proj9', 'proj10', 'proj11');
		foreach ($projects as $project) {
			${$project} = $this->createProject($project);
			${$project}->addUser($stem);
			if ($project != 'private' && $project != 'website') {
				$group = "group_{$project}";
				${$group} = $this->createGroup($group);
				${$project}->addGroup(${$group});
				if ($project == 'usvn') {
					$crivis_s->addGroup(${$group});
					$dolean_j->addGroup(${$group});
					$guyoll_o->addGroup(${$group});
					$billar_m->addGroup(${$group});
					$attal_m->addGroup(${$group});
					$joanic_g->addGroup(${$group});
					$duponc_j->addGroup(${$group});
				} else {
					$stem->addGroup(${$group});
				}
			} else {
				${$project}->addGroup(${"group_usvn"});
			}
		}

		/* @var $usvn USVN_Db_Table_Row_Project */
		/* @var $private USVN_Db_Table_Row_Project */
		/* @var $website USVN_Db_Table_Row_Project */
		/* @var $ETNA USVN_Db_Table_Row_Project */
		/* @var $toeic USVN_Db_Table_Row_Project */
		/* @var $conf USVN_Db_Table_Row_Project */
		/* @var $dotNET USVN_Db_Table_Row_Project */
		/* @var $generic USVN_Db_Table_Row_Project */

		$table = new USVN_Db_Table_Projects();
		$this->assertEquals(3, count($table->fetchAllAssignedTo($crivis_s)));
		$this->assertEquals(count($projects), count($table->fetchAllAssignedTo($stem)));
	}
}

// Call USVN_Db_Table_ProjectsTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "USVN_Db_Table_ProjectsTest::main") {
	USVN_Db_Table_ProjectsTest::main();
}
?>
