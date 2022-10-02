<?php
/**
 * @author  HeyMehedi
 * @since   1.0
 * @version 1.0
 */

namespace HeyMehedi;

use WP_Error;

class Temporary_File_Manager {

	protected static $instance = null;
	public $upload_type;
	public $allowed_extensions;
	public $size_limit;
	public $project_name;

	public $uploaded_files;
	public $seconds;
	public $max;
	public $dir_name;

	public function __construct( $args = array() ) {
		$this->prepare_property( $args );
		add_action( 'init', array( $this, 'init_uploads' ) );
		add_action( 'template_redirect', array( $this, 'cleanup_upload_files' ), 20, 0 );
	}

	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function prepare_property( $args, $project_name = 'project_name' ) {

		$this->default = array(
			'allowed_extensions'              => array( 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'JPG', 'JPEG', 'PNG', 'BMP' ),
			'size_limit'                      => 10485760,
			'project_name'                    => $project_name,
			'uploaded_files'                  => 'tmp',
			'dir_name'                        => 'tmp',
			'remove_file_if_older_than_secs'  => 60,
			'max_items_remove_when_func_call' => 100,
		);

		$args = wp_parse_args( $this->args, $this->default );
		$args = apply_filters( "{$project_name}_args", $args );

		$this->allowed_extensions = $args['allowed_extensions'];
		$this->size_limit         = $args['size_limit'];
		$this->project_name       = $args['project_name'];
		$this->uploaded_files     = $args['uploaded_files'];
		$this->dir_name           = $args['dir_name'];
		$this->seconds            = $args['remove_file_if_older_than_secs'];
		$this->max                = $args['max_items_remove_when_func_call'];
	}

	/**
	 * Initializes the temporary directory for uploaded files.
	 */
	public function init_uploads() {
		$dir = $this->get_upload_dir();

		if ( is_dir( $dir ) and is_writable( $dir ) ) {
			$htaccess_file = path_join( $dir, '.htaccess' );

			if ( ! file_exists( $htaccess_file )
				and $handle = @fopen( $htaccess_file, 'w' ) ) {
				fwrite( $handle, "Deny from all\n" );
				fclose( $handle );
			}
		}
	}

	/**
	 * Uploaded files and moves them to the temporary directory.
	 *
	 * @param should be $_FILES, Default param `$_FILES`.
	 * @return string|WP_Error file path, or WP_Error if validation fails.
	 */
	public function upload_files( $files = array() ) {
		$this->uploaded_files = array();
		$files                = ! empty( $files ) ? $files : $_POST;
		foreach ( $files as $key => $file ) {
			$file_path = $this->upload_single_file( $file );
			if ( $file_path ) {
				$this->uploaded_files[] = $file_path;
			}
		}

		return $this->uploaded_files;
	}

	/**
	 * Validates uploaded file and move to the temporary directory.
	 *
	 * @param array $file an item of `$_FILES`.
	 * @return string|WP_Error file path, or WP_Error if validation fails.
	 */
	public function upload_single_file( $file ) {

		// Move uploaded file to tmp dir
		$dir         = $this->get_upload_dir();
		$uploads_dir = $this->maybe_add_random_dir( $dir );

		$tmp_name = $file['tmp_name'];
		$filename = $file['name'];

		if ( empty( $tmp_name ) or ! is_uploaded_file( $tmp_name ) ) {
			return;
		}

		$filename = $this->canonicalize( $filename, array( 'strto' => 'as-is' ) );
		$filename = $this->anti_script_file_name( $filename );
		$new_file = path_join( $uploads_dir, $filename );

		if ( move_uploaded_file( $tmp_name, $new_file ) ) {
			// Make sure the uploaded file is only readable for the owner process
			chmod( $new_file, 0400 );

			return $new_file;
		}

		return;
	}

	/**
	 * Retrieves uploads directory information.
	 *
	 * @param string|bool $type Optional. Type of output. Default false.
	 * @return array|string Information about the upload directory.
	 */
	public function upload_dir( $type = false ) {
		$uploads = wp_get_upload_dir();

		$uploads = apply_filters( "{$this->project_name}_upload_dir", array(
			'dir' => $uploads['basedir'],
			'url' => $uploads['baseurl'],
		) );

		if ( 'dir' == $type ) {
			return $uploads['dir'];
		}
		if ( 'url' == $type ) {
			return $uploads['url'];
		}

		return $uploads;
	}

	/**
	 * Creates a child directory with a randomly generated name.
	 *
	 * @param string $dir The parent directory path.
	 * @return string The child directory path if created, otherwise the parent.
	 */
	public function maybe_add_random_dir( $dir ) {
		do {
			$rand_max = mt_getrandmax();
			$rand     = zeroise( mt_rand( 0, $rand_max ), strlen( $rand_max ) );
			$dir_new  = path_join( $dir, $rand );
		} while ( file_exists( $dir_new ) );

		if ( wp_mkdir_p( $dir_new ) ) {
			return $dir_new;
		}

		return $dir;
	}

	/**
	 * Cleans up files in the temporary directory for uploaded files.
	 *
	 * @param int $seconds Files older than this are removed. Default 60.
	 * @param int $max Maximum number of files to be removed in a function call.
	 *                 Default 100.
	 */
	public function cleanup_upload_files() {

		if ( is_admin()
			or 'GET' != $_SERVER['REQUEST_METHOD']
			or is_robots()
			or is_feed()
			or is_trackback() ) {
			return;
		}

		$dir = trailingslashit( $this->get_upload_dir() );

		if ( ! is_dir( $dir )
			or ! is_readable( $dir )
			or ! wp_is_writable( $dir ) ) {
			return;
		}

		$seconds = absint( $this->seconds );
		$max     = absint( $this->max );
		$count   = 0;

		if ( $handle = opendir( $dir ) ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( '.' == $file
					or '..' == $file
					or '.htaccess' == $file ) {
					continue;
				}

				$mtime = @filemtime( path_join( $dir, $file ) );

				if ( $mtime and time() < $mtime + $seconds ) { // less than $seconds old
					continue;
				}

				$this->project_rmdir_p( path_join( $dir, $file ) );
				$count += 1;

				if ( $max <= $count ) {
					break;
				}
			}

			closedir( $handle );
		}
	}

	/**
	 * Removes directory recursively.
	 *
	 * @param string $dir Directory path.
	 * @return bool True on success, false on failure.
	 */
	public function project_rmdir_p( $dir ) {
		if ( is_file( $dir ) ) {
			$file = $dir;

			if ( @unlink( $file ) ) {
				return true;
			}

			$stat = stat( $file );

			if ( @chmod( $file, $stat['mode'] | 0200 ) ) { // add write for owner
				if ( @unlink( $file ) ) {
					return true;
				}

				@chmod( $file, $stat['mode'] );
			}

			return false;
		}

		if ( ! is_dir( $dir ) ) {
			return false;
		}

		if ( $handle = opendir( $dir ) ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( $file == "."
					or $file == ".." ) {
					continue;
				}

				$this->project_rmdir_p( path_join( $dir, $file ) );
			}

			closedir( $handle );
		}

		if ( false !== ( $files = scandir( $dir ) )
			and ! array_diff( $files, array( '.', '..' ) ) ) {
			return rmdir( $dir );
		}

		return false;
	}

	/**
	 * Returns the directory path for uploaded files.
	 *
	 * @return string Directory path.
	 */
	public function get_upload_dir() {
		$dir = path_join( $this->upload_dir( 'dir' ), $this->dir_name );
		wp_mkdir_p( $dir );

		return $dir;
	}

	/**
	 * Canonicalizes text.
	 *
	 * @param string $text Input text.
	 * @param string|array|object $args Options.
	 * @return string Canonicalized text.
	 */
	public function canonicalize( $text, $args = '' ) {
		// for back-compat
		if ( is_string( $args ) and '' !== $args
			and false === strpos( $args, '=' ) ) {
			$args = array(
				'strto' => $args,
			);
		}

		$args = wp_parse_args( $args, array(
			'strto'            => 'lower',
			'strip_separators' => false,
		) );

		static $charset = null;

		if ( ! isset( $charset ) ) {
			$charset = get_option( 'blog_charset' );

			$is_utf8 = in_array(
				$charset,
				array( 'utf8', 'utf-8', 'UTF8', 'UTF-8' )
			);

			if ( $is_utf8 ) {
				$charset = 'UTF-8';
			}
		}

		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, $charset );

		if ( function_exists( 'mb_convert_kana' ) ) {
			$text = mb_convert_kana( $text, 'asKV', $charset );
		}

		if ( $args['strip_separators'] ) {
			$text = preg_replace( '/[\r\n\t ]+/', '', $text );
		} else {
			$text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
		}

		if ( 'lower' == $args['strto'] ) {
			if ( function_exists( 'mb_strtolower' ) ) {
				$text = mb_strtolower( $text, $charset );
			} else {
				$text = strtolower( $text );
			}
		} elseif ( 'upper' == $args['strto'] ) {
			if ( function_exists( 'mb_strtoupper' ) ) {
				$text = mb_strtoupper( $text, $charset );
			} else {
				$text = strtoupper( $text );
			}
		}

		$text = trim( $text );

		return $text;
	}

	/**
	 * Converts a file name to one that is not executable as a script.
	 *
	 * @param string $filename File name.
	 * @return string Converted file name.
	 */
	public function anti_script_file_name( $filename ) {
		$filename = wp_basename( $filename );

		$filename = preg_replace( '/[\r\n\t -]+/', '-', $filename );
		$filename = preg_replace( '/[\pC\pZ]+/iu', '', $filename );

		$parts = explode( '.', $filename );

		if ( count( $parts ) < 2 ) {
			return $filename;
		}

		$script_pattern = '/^(php|phtml|pl|py|rb|cgi|asp|aspx)\d?$/i';

		$filename  = array_shift( $parts );
		$extension = array_pop( $parts );

		foreach ( (array) $parts as $part ) {
			if ( preg_match( $script_pattern, $part ) ) {
				$filename .= '.' . $part . '_';
			} else {
				$filename .= '.' . $part;
			}
		}

		if ( preg_match( $script_pattern, $extension ) ) {
			$filename .= '.' . $extension . '_.txt';
		} else {
			$filename .= '.' . $extension;
		}

		return $filename;
	}
}