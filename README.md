
# Temporary File Manager for WP

A brief description of what this project does and who it's for




## Usage/Examples

```php
use HeyMehedi\Temporary_File_Manager;

include_once 'temporary-file-manager.php';
```

```php
$args = array(
    'allowed_extensions'              => array( 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'JPG', 'JPEG', 'PNG', 'BMP' ),
    'size_limit'                      => 10485760,
    'project_name'                    => 'project_name,
    'uploaded_files'                  => 'tmp',
    'dir_name'                        => 'tmp',
    'remove_file_if_older_than_secs'  => 60,
    'max_items_remove_when_func_call' => 100,
);
```

```php
$temp_file_manager = new Temporary_File_Manager( $args );
```

```php
/**
* Return File URLs
*/
$returned_file_url = $temp_file_manager->upload_files( true );
```

```php
/**
* Return Files Dirs
*/
$returned_file_dir = $temp_file_manager->upload_files( false );
```
