<?php
/**
 * Class to test group's model
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

// Call USVN_Auth_Adapter_DatabaseTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "USVN_Db_Table_GroupsTest::main");
}

require_once 'app/install/install.includes.php';

/**
 * @coversDefaultClass USVN_Db_Table_Groups
 */
class USVN_Db_Table_GroupsTest extends USVN_Test_DBTestCase {

    public static function main() {
        require_once "PHPUnit/TextUI/TestRunner.php";

        $suite  = new PHPUnit_Framework_TestSuite("USVN_Db_Table_GroupsTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    public function testInsertGroupNoName()
	{
		$table = new USVN_Db_Table_Groups();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('groups_name' => ''));
		try {
			$id = $obj->save();
		}
		catch (USVN_Exception $e) {
			$this->assertContains("The group's name is empty", $e->getMessage());
			return;
		}
		$this->fail();
    }

    public function testInsertGroupNoName2()
	{
		$table = new USVN_Db_Table_Groups();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('groups_name' => "   \t    "));
		try {
			$id = $obj->save();
		}
		catch (USVN_Exception $e) {
			$this->assertContains("The group's name is empty", $e->getMessage());
			return;
		}
		$this->fail();
    }

    public function testInsertGroupInvalidName()
	{
		$table = new USVN_Db_Table_Groups();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('groups_name' => "   !!!    "));
		try {
			$id = $obj->save();
		}
		catch (USVN_Exception $e) {
			$this->assertContains("The group's name is invalid", $e->getMessage());
			return;
		}
		$this->fail();
	}

	    public function testInsertGroupInvalidName2()
	{
		$table = new USVN_Db_Table_Groups();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('groups_name' => "test:bad"));
		try {
			$id = $obj->save();
		}
		catch (USVN_Exception $e) {
			$this->assertContains("The group's name is invalid", $e->getMessage());
			return;
		}
		$this->fail();
	}

    public function testInsertGroupOk()
	{
		$table = new USVN_Db_Table_Groups();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('groups_name' => 'InsertGroupOk'));
		$obj->save();
		$this->assertTrue($table->isAGroup('InsertGroupOk'));
    }

    public function testUpdateGroupNoName()
	{
		$table = new USVN_Db_Table_Groups();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('groups_name' => 'UpdateGroupNoName'));
		$id = $obj->save();
		$obj = $table->find($id)->current();
		$obj->setFromArray(array('groups_name' => ''));
		try {
			$id = $obj->save();
		}
		catch (USVN_Exception $e) {
			$this->assertContains("The group's name is empty", $e->getMessage());
			return;
		}
		$this->fail();
	}

    public function testUpdateGroupOk()
	{
		$table = new USVN_Db_Table_Groups();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('groups_name' => 'UpdateGroupOk'));
		$id = $obj->save();
		$this->assertTrue($table->isAGroup('UpdateGroupOk'));
		$obj = $table->find($id)->current();
		$obj->setFromArray(array('groups_name' => 'UpdateGroupOk2'));
		$id = $obj->save();
		$this->assertTrue($table->isAGroup('UpdateGroupOk2'));
    }

    public function testUpdateOnlyDesc()
	{
		$table = new USVN_Db_Table_Groups();
		$obj = $table->fetchNew();
		$obj->setFromArray(array('groups_name' => 'UpdateGroupOk'));
		$id = $obj->save();
		$this->assertTrue($table->isAGroup('UpdateGroupOk'));
		$obj = $table->find($id)->current();
		$obj->setFromArray(array('groups_description' => 'test'));
		$id = $obj->save();
		$this->assertTrue($table->isAGroup('UpdateGroupOk'));
    }

	public function testDeleteAffectedGroup()
	{
		$table = new USVN_Db_Table_GroupsToProjects();
		$project = $this->createProject("project");
		$project2 = $this->createProject("project2");
		$this->assertEquals(0, count($table->findByProjectId($project->id)));
		$this->assertEquals(0, count($table->findByProjectId($project2->id)));
		$group1 = $this->createGroup("group1");
		$group2 = $this->createGroup("group2");
		$project->addGroup($group1);
		$project2->addGroup($group1);
		$project->addGroup($group2);
		$project2->addGroup($group2);
		$this->assertEquals(2, count($table->findByProjectId($project->id)));
		$this->assertEquals(2, count($table->findByProjectId($project2->id)));
		$group1->delete();
		$this->assertEquals(1, count($table->findByProjectId($project->id)));
		$this->assertEquals(1, count($table->findByProjectId($project2->id)));
		$group2->delete();
		$this->assertEquals(0, count($table->findByProjectId($project->id)));
	}

	public function testFindUserInGroup()
	{
		$test = $this->createUser("test");
		$babar = $this->createUser("babar");
		$john = $this->createUser("john");

		$group = $this->createGroup("toto");

		$user_groups = new USVN_Db_Table_UsersToGroups();
		$user_groups->insert(
			array(
				"groups_id" => $group->id,
				"users_id" => $test->id,
				"is_leader" => false
			)
		);
		$user_groups->insert(
			array(
				"groups_id" => $group->id,
				"users_id" => $babar->id,
				"is_leader" => false
			)
		);

		$group_table = new USVN_Db_Table_Groups();
		$group = $group_table->find($test->id)->current();
		$users = $group->findManyToManyRowset('USVN_Db_Table_Users', 'USVN_Db_Table_UsersToGroups');
		$res = array();
		foreach ($users as $user) {
			array_push($res, $user->users_login);
		}
		$this->assertContains("test", $res);
		$this->assertContains("babar", $res);
		$this->assertNotContains("john", $res);
	}

	function testFetchAllForUserAndProject()
	{
		$group1 = $this->createGroup("group1");
		$group2 = $this->createGroup("group2");
		$group3 = $this->createGroup("group3");
		$group4 = $this->createGroup("group4");

		$user1 = $this->createUser("user1");
		$user1->addGroup($group1);
		$user1->addGroup($group2);
		$user1->addGroup($group4);

		$user2 = $this->createUser("user2");
		$user2->addGroup($group1);
		$user2->addGroup($group3);
		$user2->addGroup($group4);

		$proj1 = $this->createProject("proj1");
		$proj1->addGroup($group1);
		$proj1->addGroup($group3);

		$proj2 = $this->createProject("proj2");
		$proj2->addGroup($group2);
		$proj2->addGroup($group4);

		$table = new USVN_Db_Table_Groups();
		$groups = $table->fetchAllForUserAndProject($user1, $proj1);
		$res = array();
		foreach ($groups as $group) {
			array_push($res, $group->name);
		}
		$this->assertContains("group1", $res);
		$this->assertNotContains("group2", $res);
		$this->assertNotContains("group3", $res);
		$this->assertNotContains("group4", $res);

		$groups = $table->fetchAllForUserAndProject($user1, $proj2);
		$res = array();
		foreach ($groups as $group) {
			array_push($res, $group->name);
		}
		$this->assertContains("group2", $res);
		$this->assertContains("group4", $res);
		$this->assertNotContains("group1", $res);
		$this->assertNotContains("group3", $res);

		$groups = $table->fetchAllForUserAndProject($user2, $proj1);
		$res = array();
		foreach ($groups as $group) {
			array_push($res, $group->name);
		}
		$this->assertContains("group1", $res);
		$this->assertContains("group3", $res);
		$this->assertNotContains("group2", $res);
		$this->assertNotContains("group4", $res);

		$groups = $table->fetchAllForUserAndProject($user2, $proj2);
		$res = array();
		foreach ($groups as $group) {
			array_push($res, $group->name);
		}
		$this->assertContains("group4", $res);
		$this->assertNotContains("group1", $res);
		$this->assertNotContains("group2", $res);
		$this->assertNotContains("group3", $res);
	}
}

// Call USVN_Auth_Adapter_DatabaseTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "USVN_Db_Table_GroupsTest::main") {
    USVN_Db_Table_GroupsTest::main();
}
?>