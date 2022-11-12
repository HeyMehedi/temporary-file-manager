
# Temporary File Manager for WP

Upload and move files to the temporary directory, and clean up files and directories according to your schedule after finishing the job.



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


## Use cases
* Store submission data as temporary files and send emails. After completing the job clean up the files.
* Save Submitted files to Temporary for a while. 
* and more...
