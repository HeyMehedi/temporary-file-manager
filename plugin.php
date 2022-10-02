<?php
/**
 * Plugin Name:  Temporary File Manager ( Test )
 * Description:  Before Include in Your Project Take a test
 * Version:      0.1
 * Author:       HeyMehedi
 */

use HeyMehedi\Temporary_File_Manager;

include_once 'lib/temporary-file-manager.php';

$temp_file_manager = new Temporary_File_Manager( array() );

$returned_file_dir = $temp_file_manager->upload_files( true );

echo '<img src="' . $returned_file_dir[0] . '"/>';