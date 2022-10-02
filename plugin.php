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
$file              = isset( $_FILES['hello_kitty'] ) ? $_FILES['hello_kitty'] : array();
var_dump( $_FILES['hello_kitty'] );

$temp_file_manager->upload_files();