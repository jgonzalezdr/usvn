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
 * $Id: SVNUtils.php 1536 2008-11-01 16:08:37Z duponc_j $
 */
class USVN_SVNUtils
{
    public static $hooks = array('post-commit',
                                        'post-unlock',
                                        'pre-revprop-change',
                                        'post-lock',
                                        'pre-commit',
                                        'pre-unlock',
                                        'post-revprop-change',
                                        'pre-lock',
                                        'start-commit');

	/**
	* @param string Path of subversion repository
	* @return bool
	*/
    public static function isSVNRepository($path, $available = false)
    {
        if (file_exists($path . "/hooks") && file_exists($path . "/db")){
            return true;
        }
				if ($available && is_dir($path)) {
					$not_dir = false;
					foreach (array_diff(scandir($path), array('.', '..')) as $file) {
						if (!is_dir($path.'/'.$file)) {
							$not_dir = true;
						}
					}
					if ($not_dir === false) {
						return true;
					}
				}
        return false;
    }

	/**
	* @param string Output of svnlook changed
	* @return array Exemple array(array('M', 'tutu'), array('M', 'dir/tata'))
	*/
	public static function changedFiles($list)
	{
		$res = array();
		$list = explode("\n", $list);
		foreach($list as $line) {
			if ($line) {
				$ex = explode(" ", $line, 2);
				array_push($res, $ex);
			}
		}
		return $res;
	}

	/**
	* Call the svnlook binary on an svn transaction.
	*
	* @param string svnlook command (see svnlook help)
	* @param string repository path
	* @param string transaction (call TXN into svn hooks samples)
	* @return string Output of svnlook
	* @see http://svnbook.red-bean.com/en/1.1/ch09s03.html
	*/
	public static function svnLookTransaction($command, $repository, $transaction)
	{
		$command = escapeshellarg($command);
		$transaction = escapeshellarg($transaction);
		$repository = escapeshellarg($repository);
		return USVN_ConsoleUtils::runCmdCaptureMessage(USVN_SVNUtils::svnCommand("$command -t $transaction $repository"), $return);
	}

	/**
	* Call the svnlook binary on an svn revision.
	*
	* @param string svnlook command (see svnlook help)
	* @param string repository path
	* @param integer revision
	* @return string Output of svnlook
	* @see http://svnbook.red-bean.com/en/1.1/ch09s03.html
	*/
	public static function svnLookRevision($command, $repository, $revision)
	{
		$command = escapeshellarg($command);
		$revision = escapeshellarg($revision);
		$repository = escapeshellarg($repository);
		return USVN_ConsoleUtils::runCmdCaptureMessage(USVN_SVNUtils::svnCommand("$command -r $revision $repository"), $return);
	}

	/**
	* Return minor version of svn client
	*
	* @return int (ex for svn 1.3.4 return 3)
	*/
	public static function getSvnMinorVersion()
	{
		$version = USVN_SVNUtils::getSvnVersion();
		return $version[1];
	}

	/**
	* Return version of svn client
	*
	* @return array  (ex: for svn version 1.3.3 array(1, 3, 3))
	*/
	public static function getSvnVersion()
	{
		return USVN_SVNUtils::parseSvnVersion(USVN_ConsoleUtils::runCmdCaptureMessage(USVN_SVNUtils::svnCommand("--version --quiet"), $return));
	}

	/**
	* Parse output of svn --version for return the version number
	*
	* @param string output of svn --version
	* @return array  (ex: for svn version 1.3.3 array(1, 3, 3))
	*/
	public static function parseSvnVersion($version)
	{
		$version = rtrim($version);
		return explode(".", $version);
	}

	/**
	* Get the command svn
	*
	* @param string Parameters
	*/
	public static function svnCommand($cmd)
	{
		return "svn --config-dir /USVN/fake $cmd";
	}

	/**
	* Get the command svnadmin
	*
	* @param string Parameters
	*/
	public static function svnadminCommand($cmd)
	{
		return "svnadmin --config-dir /USVN/fake $cmd";
	}

    /**
    * Import file into subversion repository
    *
    * @param string path to server repository
    * @param string path to directory to import
    */
    private static function _svnImport($server, $local)
    {
        $server = USVN_SVNUtils::getRepositoryFileUrl($server);
        $local = escapeshellarg($local);
        $cmd = USVN_SVNUtils::svnCommand("import --non-interactive --username USVN -m \"" . T_("Commit by USVN") ."\" $local $server");
		$message = USVN_ConsoleUtils::runCmdCaptureMessage($cmd, $return);
		if ($return) {
			throw new USVN_Exception(T_("Can't import into subversion repository.\nCommand:\n%s\n\nError:\n%s"), $cmd, $message);
		}
    }

    /**
    * Create empty SVN repository
    *
    * @param string Path to create subversion
    */
    public static function createSvn($path)
    {
      $escape_path = escapeshellarg($path);
      $message = USVN_ConsoleUtils::runCmdCaptureMessage(USVN_SVNUtils::svnadminCommand("create $escape_path"), $return);
      if ($return) {
		throw new USVN_Exception(T_("Can't create subversion repository: %s"), $message);
      }
    }

    /**
    * Create standard svn directories
    * /trunk
    * /tags
    * /branches
    *
    * @param string Path to create subversion
    */
    public static function createStandardDirectories($path)
    {
      $tmpdir = USVN_DirectoryUtils::getTmpDirectory();
      try {
		mkdir($tmpdir . DIRECTORY_SEPARATOR . "trunk");
		mkdir($tmpdir . DIRECTORY_SEPARATOR . "branches");
		mkdir($tmpdir . DIRECTORY_SEPARATOR . "tags");
		USVN_SVNUtils::_svnImport($path, $tmpdir);
      }
      catch (Exception $e) {
		USVN_DirectoryUtils::removeDirectory($path);
		USVN_DirectoryUtils::removeDirectory($tmpdir);
		throw $e;
      }
      USVN_DirectoryUtils::removeDirectory($tmpdir);
    }

    /**
    * Checkout SVN repository into filesystem
    * @param string Path to subversion repository
    * @param string Path to destination
    */
    public static function checkoutSvn($src, $dst)
    {
		$dst = escapeshellarg($dst);
		$src = USVN_SVNUtils::getRepositoryFileUrl($src);
		$message = USVN_ConsoleUtils::runCmdCaptureMessage(USVN_SVNUtils::svnCommand("co $src $dst"), $return);
		if ($return) {
			throw new USVN_Exception(T_("Can't checkout subversion repository: %s"), $message);
		}
	}

	/**
	* List files into Subversion
    *
	* @param string Path to subversion repository
    * @param string Path into subversion repository
    * @return associative array like: array(array(name => "tutu", isDirectory => true))
	*/
	public static function listSvn($repository, $path)
	{
		$escape_path = USVN_SVNUtils::getRepositoryFileUrl($repository . '/' . $path);
		$lists = USVN_ConsoleUtils::runCmdCaptureMessageUnsafe(USVN_SVNUtils::svnCommand("ls --xml $escape_path"), $return);
		if ($return) {
			throw new USVN_Exception(T_("Can't list subversion repository: %s"), $lists);
		}
		$res = array();
		$xml = new SimpleXMLElement($lists);
		foreach ($xml->list->entry as $list) {
			if ($list['kind'] == 'file') {
				array_push($res, array(
																"name" => (string)$list->name,
																"isDirectory" => false,
																"path" => str_replace('//', '/', $path . "/" . $list->name),
																"size" => $list->size,
																"revision" => $list->commit['revision'],
																"author" => $list->commit->author,
																"date" => $list->commit->date
															));
			}
			else {
				array_push($res, array("name" => (string)$list->name, "isDirectory" => true, "path" => str_replace('//', '/', $path . "/" . $list->name  . '/')));
			}
		}
		
		usort($res, array("USVN_SVNUtils", "listSvnSort"));
		return $res;
	}

	private static function listSvnSort($a,$b){
		if($a["isDirectory"]){
			if($b["isDirectory"]){
				return (strcasecmp($a["name"], $b["name"]));
			}else{
				return -1;
			}
		}
		if($b["isDirectory"]){
			return 1;
		}
		return (strcasecmp($a["name"], $b["name"]));
	}

	/**
	 * This code work only for directory
	 * Directory separator need to be /
	 */
	private static function getCannocialPath($path)
	{
		$origpath = $path;
		$path = preg_replace('#//+#', '/', $path);
		$list_path = preg_split('#/#', $path, -1, PREG_SPLIT_NO_EMPTY);
		$i = 0;
		while (isset($list_path[$i])) {
			if ($list_path[$i] == '..') {
				unset($list_path[$i]);
				if ($i > 0) {
					unset($list_path[$i - 1]);
				}
				$list_path = array_values($list_path);
				$i = 0;
			}
			elseif ($list_path[$i] == '.') {
				unset($list_path[$i]);
				$list_path = array_values($list_path);
				$i = 0;
			}
			else {
				$i++;
			}
		}
		$newpath = '';
		$first = true;
		foreach ($list_path as $path) {
			if (!$first) {
				$newpath .= '/';
			}
			else {
				$first = false;
			}
			$newpath .= $path;
		}
		if ($origpath[0] == '/') {
			return '/' . $newpath;
		}
		if(strtoupper(substr(PHP_OS, 0,3)) == 'WIN' ) {
			return $newpath;
		}
		else {
			return getcwd() . '/' . $newpath;
		}
	}

	/**
	 * Return clean version of a Subversion repository path between double quotes and with file:// before
	 *
	 * @param string Path to repository
	 * @return string absolute path to repository
	 */
	public static function getRepositoryFileUrl($path)
	{
		if(strtoupper(substr(PHP_OS, 0,3)) == 'WIN' ) {
			$newpath = realpath($path);
			if ($newpath === FALSE) {
				$path = str_replace('//', '/', str_replace('\\', '/', $path));
				$path = USVN_SVNUtils::getCannocialPath($path);
			}
			else {
				$path = $newpath;
			}
			return '"file:///' . str_replace('\\', '/', $path) . '"';
		}
		$newpath = realpath($path);
		if ($newpath === FALSE) {
			$newpath = USVN_SVNUtils::getCannocialPath($path);
		}
		return escapeshellarg('file://' . $newpath);
	}

	/**
	*
	* @param string Project name
	* @param string Path into Subversion
	* @return string Return url of files into Subversion
	*/
	public static function getSubversionUrl($project, $path)
	{
		$config = Zend_Registry::get('config');
		$url = $config->subversion->url;
		if (substr($url, -1, 1) != '/') {
			$url .= '/';
		}
		$url .= $project . $path;
		return $url;
	}
	
	/**
	 * Get the path in the filesystem to the given repository.
	 * 
	 * @param USVN_Config_Ini $config [in] Global configuration
	 * @param string $repo_name [in] Name of the repository
	 * @return string Path in the filesystem to the given repository
	 */
	public static function getRepositoryFilesystemPath( $config, $repo_name )
	{
		return $config->subversion->path
		. DIRECTORY_SEPARATOR
		. 'svn'
		. DIRECTORY_SEPARATOR
		. $repo_name;
	}

	/**
	 * Get the path in the filesystem to the templates repository.
	 * 
	 * @param USVN_Config_Ini $config [in] Global configuration
	 * @return string Path in the filesystem to the templates repository.
	 */
	private static function getTemplatesRepoFilesystemPath( $config )
	{
		if( empty( $config->projectTemplates ) ||
		    empty( $config->projectTemplates->repoName ) )
		{
			return NULL;
		}
		
		return self::getRepositoryFilesystemPath( $config, $config->projectTemplates->repoName );
	}

	/**
	 * Get the path to the templates repository in URL form (file://...).
	 * 
	 * @param USVN_Config_Ini $config [in] Global configuration
	 * @return string Path in URL form to the templates repository.
	 */
	private static function getTemplatesRepoFileUrl( $config )
	{
		$path = self::getTemplatesRepoFilesystemPath( $config );
		if( empty( $path ) )
		{
			return NULL;
		}
		
		return self::getRepositoryFileUrl( $path );
	}
	
	/**
	 * Get the list of repository templates.
	 * 
	 * @return array List of templates as a hashed array
	 * @throws USVN_Exception
	 */
	public static function getRepositoryTemplates()
	{
		$config = Zend_Registry::get('config');
		
		$path = self::getTemplatesRepoFileUrl( $config );
		if( empty( $path ) )
		{
			return NULL;
		}
		
		$cmd_output = USVN_ConsoleUtils::runCmdCaptureMessageUnsafe( USVN_SVNUtils::svnCommand("ls $path"), $return_code );
		if ($return_code) 
		{
			throw new USVN_Exception(T_("Can't list subversion templates repository: %s"), $cmd_output);
		}
		
		$template_list = explode("\n", $cmd_output);
		
		$template_hash = array();
		foreach( $template_list as $value )
		{
			if( empty($value) ) continue;
			$trimmed_value = substr( $value, 0, -1 );
			$template_hash[$trimmed_value] = $trimmed_value;
		}
		
		return $template_hash;
	}

	/**
	 * Get a descriptive message for the last JSON decoding error.
	 * @return string Message for the last JSON decoding error.
	 */
	private static function getJsonErrorMsg()
	{
		if( function_exists('json_last_error_msg') ) 
		{
			return json_last_error_msg();
		}
		else
		{
			switch( json_last_error() ) 
			{
			case JSON_ERROR_NONE:
				return 'No error';
			case JSON_ERROR_DEPTH:
				return 'Maximum stack depth exceeded';
			case JSON_ERROR_STATE_MISMATCH:
				return 'Underflow or the modes mismatch';
			case JSON_ERROR_CTRL_CHAR:
				return 'Unexpected control character found';
			case JSON_ERROR_SYNTAX:
				return 'Syntax error, malformed JSON';
			case JSON_ERROR_UTF8:
				return 'Malformed UTF-8 characters, possibly incorrectly encoded';
			default:
				return 'Unknown error';
			}			
		}
	}
	
	/**
	 * Decode the SVN access control file (that shall be in JSON form) into
	 * a hashed array containing the decoded elements.
	 * 
	 * @param string $svnaccess_contents [in] SVN access control file in JSON form.
	 * @return array Decoded elements
	 * @throws USVN_Exception
	 */
	private static function parseAccessFile( $svnaccess_contents )
	{
		$svnaccess_specs = json_decode( $svnaccess_contents, TRUE );
		if( json_last_error() != JSON_ERROR_NONE )
		{
			throw new USVN_Exception( "Error parsing '%s': %s", $svnaccess_filename, self::getJsonErrorMsg() );
		}
		return $svnaccess_specs;
	}
	
	/**
	 * Get the initializer script for a new repository from a repository template,
	 * along with the access control specs (if defined in the template).
	 * 
	 * @param string $template_name [in] The name of the template to copy
	 * @param string $new_repo_name [in] The name of the new repository
	 * @param array $svnaccess_specs [out] SVN access control specs
	 * @return string Initializer script
	 * @throws USVN_Exception
	 */
	public static function getInitScriptFromTemplate( $template_name, $new_repo_name, &$svnaccess_specs )
	{
		$config = Zend_Registry::get('config');
		
		$template_svn_path = self::getTemplatesRepoFilesystemPath( $config );
		if( empty( $template_svn_path ) )
		{
			throw new USVN_Exception( T_("Can't get templates repository path from configuration.") );
		}
		
		// Get template dump
		$cmd = 'svnadmin dump -r HEAD "'.$template_svn_path.'"';
		$initial_dump = USVN_ConsoleUtils::runCmdCaptureMessageUnsafe( $cmd, $return_code, false );
		if( $return_code ) 
		{
			throw new USVN_Exception(T_("Can't retrieve project template from repository: %s."), $initial_dump);
		}
		
		// Delete the entries for other templates and for the template's root directory
		$new_dump = preg_replace( "~^Node-path: (?!".$template_name."\/).*?(?=(?:^Node-path:|\Z))~sm", "", $initial_dump );
		
		// Rebase remaining entries paths
		$new_dump = preg_replace( "~^Node-path: ".$template_name."\/~m", "Node-path: ", $new_dump );
		
		// Find access control file
		$svnaccess_filename = "svnaccess.json";
		$svnaccess_specs = NULL;
		if( preg_match( "~^Node-path: ".$svnaccess_filename."$.*?PROPS-END$(.*?)(?:^Node-path:|\Z)~sm", $new_dump, $svnaccess_matches) )
		{
			// Delete access control file
			$new_dump = preg_replace( "~^Node-path: ".$svnaccess_filename."$.*?(?=(?:Node-path:|\Z))~sm", "", $new_dump, 1, $regex_count );
			if( $regex_count != 1 )
			{
				throw new USVN_Exception(T_("Couldn't delete the access file from template:\n%s"), $initial_dump);
			}
			
			// Parse access control file
			$svnaccess_specs = USVN_SVNUtils::parseAccessFile( $svnaccess_matches[1] );
		}

		// Replace log
		$new_dump = preg_replace( "~svn:log\nV.*?\n.*?((\nK \d+\n)|(\nPROPS-END\n))~sm", "svn:log\nV 8\nCreation$1", $new_dump, 1, $regex_count );
		if( $regex_count != 1 )
		{
			throw new USVN_Exception(T_("Couldn't replace the template log:\n%s"), $initial_dump);
		}

		// Replace author
		$new_dump = preg_replace( "~svn:author\nV.*?\n.*?\n~sm", "svn:author\nV 4\nUSVN\n", $new_dump, 1, $regex_count );
		if( $regex_count != 1 )
		{
			throw new USVN_Exception(T_("Couldn't replace the template log:\n%s"), $initial_dump);
		}
		
		// Replace log date
		$log_date = gmdate( "Y-m-d\TH:i:s.u\Z" );
		$new_dump = preg_replace( "~svn:date\nV.*?\n.*?\n~sm", "svn:date\nV ".strlen($log_date)."\n".$log_date."\n", $new_dump, 1, $regex_count );
		if( $regex_count != 1 )
		{
			throw new USVN_Exception(T_("Couldn't replace the template date:\n%s"), $initial_dump);
		}

		// Replace project name placeholders (only in paths)
		$new_dump = preg_replace( "~(Node-path: .*?)#ProjectName#(.*)~s", "$1".$new_repo_name."$2", $new_dump );

		
		return $new_dump;
	}

	/**
	 * Initializes a repository from an initialization script.
	 * 
	 * @param string $new_repo_name [in] The name of the new repository
	 * @param string $init_script [in] Initializer script for the new repository
	 * @throws USVN_Exception
	 */
	public static function initRepositoryFromScript( $new_repo_name, $init_script )
	{
		$config = Zend_Registry::get('config');

		$new_repo_svn_path = self::getRepositoryFilesystemPath( $config, $new_repo_name );

		// Load template dump
		$cmd = "svnadmin load $new_repo_svn_path";
		$load_msg = USVN_ConsoleUtils::runCmdSendMessageToStdin( $cmd, $return_code, $init_script );
		if( $return_code ) 
		{
			throw new USVN_Exception(T_("Can't import project template into the new repository: %s"), $load_msg);
		}
	}

}
