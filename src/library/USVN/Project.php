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
class USVN_Project
{
	/**
	 * Create SVN repositories
	 *
	 * @param string Project name
	 * @param bool Create standard directories (/trunk, /tags, /branches)
	 */
	static private function createProjectSVN($project_name, $create_dir)
	{
		$config = Zend_Registry::get('config');
		$path = $config->subversion->path
		. DIRECTORY_SEPARATOR
		. 'svn'
		. DIRECTORY_SEPARATOR
		. $project_name;
		if (!USVN_SVNUtils::isSVNRepository($path, true)) {
			$directories = explode(DIRECTORY_SEPARATOR, $path);
			$tmp_path = '';
			foreach ($directories as $directory) {
				$tmp_path .= $directory . DIRECTORY_SEPARATOR;
				if (USVN_SVNUtils::isSVNRepository($tmp_path)) {
					$tmp_path = '';
					break;
				}
			}
			if ($tmp_path === $path . DIRECTORY_SEPARATOR) {
				if ($mod = $config->subversion->chmod) {
					$mod = intval($mod, 8);
				} else {
					$mod = 0700;
				}
				@mkdir($path, $mod, true);
				@chmod($path, $mod); // mkdir is bogus
				
				USVN_SVNUtils::createSVN($path);
				
				if ($create_dir) {
					USVN_SVNUtils::createStandardDirectories($path);
				}
				
				// apply files rights
				$iterator = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
					RecursiveIteratorIterator::CHILD_FIRST
				);
				foreach ($iterator as $file) {
					@chmod((string) $file, $mod);
				}
				
				
				// apply special dir rights on repo/db
				if ($mod = $config->subversion->chmod_db) {
					$mod = intval($mod, 8);
					$dbPath = $path.'/db';
					@chmod($dbPath, $mod);
					
					$iterator = new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator($dbPath, FilesystemIterator::SKIP_DOTS),
						RecursiveIteratorIterator::CHILD_FIRST
					);
					foreach ($iterator as $file) {
						if ($file->isDir()) {
							@chmod((string) $file, $mod);
						}
					}
					//$escape_path = escapeshellarg($path);
					//USVN_ConsoleUtils::runCmdCaptureMessage("chmod g+s $escape_path/*/db && find $escape_path/*/db -type d ! -perm -g=s | xargs chmod g+s", $return);
					//@USVN_DirectoryUtils::chmodRecursive($path.'/db', intval($mod, 8));
				}
				
			} else {
				$message = "One of these repository's subfolders is a subversion repository.";
				throw new USVN_Exception(T_("Can't create subversion repository:<br />%s"), $message);
			}
		} else {
			$message = $project_name." is a SVN repository.";
			throw new USVN_Exception(T_("Can't create subversion repository:<br />%s"), $message);
		}
	}

	static private function ApplyFileRights($project, $group, $create_svn_directories)
	{
		$acces_rights = new USVN_FilesAccessRights($project->projects_id);
		
		
		if ($create_svn_directories) {
			$acces_rights->setRightByPath($group->groups_id, '/', true, false);
			$acces_rights->setRightByPath($group->groups_id, '/branches', true, true);
			$acces_rights->setRightByPath($group->groups_id, '/tags', true, true);
			$acces_rights->setRightByPath($group->groups_id, '/trunk', true, true);
		}
		else {
			$acces_rights->setRightByPath($group->groups_id, '/', true, true);
		}
	}
	
	static private function getLoginUser( $login )
	{
		$user_table = new USVN_Db_Table_Users();
		$user = $user_table->fetchRow(array('users_login = ?' => $login));
		if( $user === null ) 
		{
			throw new USVN_Exception(T_('Login %s not found'), $login);
		}
		return $user;
	}

	/**
	 * Create a project
	 *
	 * @param array Fields data
	 * @param string The creating user
	 * @param bool Create a group for the project
	 * @param bool Add user into group
	 * @param bool Add user as admin for the project
	 * @param bool Create SVN standard directories
	 * @return USVN_Db_Table_Row_Project
	 */
	static public function createProject(array $data, $login, $create_group, $add_user_to_group, $create_admin, $create_svn_directories)
	{
		// We need check if admin exist before create project because we can't go back
		$user = USVN_Project::getLoginUser( $login );

		$groups = new USVN_Db_Table_Groups();
		if ($create_group) {
			$group = $groups->fetchRow(array('groups_name = ?' => $data['projects_name']));
			if ($group !== null) {
				throw new USVN_Exception(T_("Group %s already exists."), $data['projects_name']);
			}
		}
		try {
			$table = new USVN_Db_Table_Projects();
			$table->getAdapter()->beginTransaction();
			$project = $table->createRow($data);
			$project->save();

			USVN_Project::createProjectSVN($data['projects_name'], $create_svn_directories);
				
			if ($create_group) {
				$group = $groups->createRow();
				$group->description = sprintf(T_("Autocreated group for project %s"), $data['projects_name']);
				$group->name = $data['projects_name'];
				$group->save();

				$project->addGroup($group);

				USVN_Project::ApplyFileRights($project, $group, $create_svn_directories);
			}

			if ($create_group && $add_user_to_group) {
				$group->addUser($user);
				$group->promoteUser($user);
			}
			if ($create_admin) {
				$project->addUser($user);
			}
		}
		catch (Exception $e) {
			$table->getAdapter()->rollBack();
			throw $e;
		}
		$table->getAdapter()->commit();
		return $project;
	}

	/**
	 * Create the groups for a project as specified in the template's access control specs.
	 * 
	 * @param USVN_Db_Table_Projects_Row $project [in] Project to which the groups will be added
	 * @param array $access_specs [in] SVN access control specs
	 * @param USVN_Db_Table_Users_Row $admin_user [in] User that will be administrator for the groups (may be null) 
	 * @param array $created_groups [out] Hashed array with the list of created groups and correspondence between access specs name and real
	 *        name 
	 * @throws USVN_Exception
	 */
	static private function _createSvnAccessControlGroups( $project, $access_specs, $admin_user, &$created_groups )
	{
		$groups = new USVN_Db_Table_Groups();
		$created_groups = array();

		// Create the specified groups
		foreach( $access_specs['groups'] as $group_name )
		{
			if( array_key_exists( $group_name, $created_groups ) )
			{
				throw new USVN_Exception( T_("Project group %s has been defined more than once in access file."), $group_name );
			}

			if( $group_name === "" )
			{
				$db_group_name = $project->projects_name;
			}
			else
			{
				$db_group_name = $project->projects_name."-".$group_name;
			}
			
			$groups->checkGroupName( $db_group_name );

			if( $groups->isAGroup( $db_group_name) )
			{
				throw new USVN_Exception( T_("Group %s already exists."), $db_group_name );
			}

			$group = $groups->createRow();
			$group->description = sprintf( T_("Autocreated group for project %s (%s)"), $project->projects_name, $group_name );
			$group->name = $db_group_name;
			$group->save();
			
			$project->addGroup($group);
			
			if( $admin_user !== NULL )
			{
				$group->addUser($admin_user);
				$group->promoteUser($admin_user);
			}
			
			$created_groups[$group_name] = $group->groups_id;
		}
	}

	/**
	 * Create the access rights for a project as specified in the template's access control specs.
	 * 
	 * @param USVN_Db_Table_Projects_Row $project [in] Project to which the groups will be added
	 * @param array $access_specs [in] SVN access control specs
	 * @param array $created_groups [in] Hashed array with the list of created groups and correspondence between access specs name and real
	 *        name 
	 * @throws USVN_Exception
	 */
	static private function _createSvnAccessControlRights( $project, $access_specs, $created_groups )
	{
		$file_access_rights = new USVN_FilesAccessRights( $project->projects_id );
		$groups = new USVN_Db_Table_Groups();
	
		foreach( $access_specs['access'] as $path => $rights )
		{
			if( empty( $path ) )
			{
				$path = "/";
			}
			else if( $path[0] != '/' )
			{
				$path = '/'.$path;
			}
			
			foreach( $rights as $group_name => $access_type )
			{
				if( $group_name[0] == '#' )
				{
					$existing_group_name = substr( $group_name, 1 );
					$existing_group = $groups->findByGroupsName( $existing_group_name );
					if( empty( $existing_group ) )
					{
						throw new USVN_Exception( T_("Pre-defined group %s doesn't exists."), $existing_group_name );
					}
					$group_id = $existing_group->groups_id;
					if( ! $project->groupIsMember($existing_group) )
					{
						$project->addGroup($existing_group);
					}
				}
				else
				{
					$group_id = $created_groups[$group_name];
					if( empty( $group_id ) )
					{
						throw new USVN_Exception( T_("Project group %s hasn't been defined in access file."), $group_name );
					}
				}
				
				$read_right = ( strpos( $access_type, "r" ) !== false );
				$write_right = ( strpos( $access_type, "w" ) !== false );
				
				$file_access_rights->setRightByPath( $group_id, $path, $read_right, $write_right );
			}
		}
	}

	/**
	 * Create the groups and access rights for a project as specified in the template's access control specs.
	 * 
	 * @param USVN_Db_Table_Projects_Row $project [in] Project to which the groups will be added
	 * @param array $access_specs [in] SVN access control specs
	 * @param USVN_Db_Table_Users_Row $admin_user [in] User that will be administrator for the groups (may be null) 
	 * @throws USVN_Exception
	 */
	static private function _parseSvnAccessControlSpecs( $project, $access_specs, $admin_user )
	{
		self::_createSvnAccessControlGroups( $project, $access_specs, $admin_user, $created_groups );
		self::_createSvnAccessControlRights( $project, $access_specs, $created_groups );
	}

	/**
	 * Create a project from a template.
	 * 
	 * @param array $data [in] Project data in project table initializer form
	 * @param string $login [in] Username of the user that is creating the project
	 * @param string $template_name [in] Name of the template to be used
	 * @param bool $project_admin [in] Whether to set the creating user as admin of the project
	 * @param bool $group_admin [in] Whether to set the creating user as admin of the created groups
	 * @return USVN_Db_Table_Row_Project Newly created project
	 * @throws Exception
	 */
	static public function createProjectFromTemplate( array $data, $login, $template_name, $project_admin, $group_admin )
	{
		// We need check if admin exist before create project because we can't go back
		$user = USVN_Project::getLoginUser( $login );
		$project_name = $data['projects_name'];

		$projects = new USVN_Db_Table_Projects();
		
		try 
		{
			$projects->getAdapter()->beginTransaction();

			$project = $projects->createRow($data);
			$project->save();

			$access_specs = NULL;
			$init_script = USVN_SVNUtils::getInitScriptFromTemplate( $template_name, $project_name, $access_specs );
			
			if( $access_specs != NULL )
			{
				USVN_Project::_parseSvnAccessControlSpecs( $project, $access_specs, $group_admin ? $user : NULL );
			}
			
			if( $project_admin ) 
			{
				$project->addUser($user);
			}

			USVN_Project::createProjectSVN( $project_name, false );
			
			USVN_SVNUtils::initRepositoryFromScript( $project_name, $init_script );
		}
		catch( Exception $e ) 
		{
			$projects->getAdapter()->rollBack();
			throw $e;
		}
		$projects->getAdapter()->commit();
		return $project;
	}

	/**
	 * Delete a project.
	 * 
	 * @param string $project_name [in] Name of the project to be deleted
	 * @throws USVN_Exception
	 */
	public static function deleteProject( $project_name )
	{
		$table = new USVN_Db_Table_Projects();
		$project = $table->fetchRow(array('projects_name = ?' => $project_name));
		if ($project === null) {
			throw new USVN_Exception(T_("Project %s doesn't exist."), $project_name);
		}
		$project->delete();
		$groups = new USVN_Db_Table_Groups();
		$where = $groups->getAdapter()->quoteInto("groups_name = ?", $project_name);
		$group = $groups->fetchRow($where);
		if ($group !== null) {
			$group->delete();
		}

		USVN_DirectoryUtils::removeDirectory(Zend_Registry::get('config')->subversion->path
		. DIRECTORY_SEPARATOR
		. 'svn'
		. DIRECTORY_SEPARATOR
		. $project_name);

	}
}
