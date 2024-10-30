<?php 

class CF7SG_SIGN_ACTIONS{

	public static $instance = null;
	public $sign_inputs     = [];
	public $uploads_dir     = '';

	public static function get_instance(){

		if( static::$instance === null ) 
			static::$instance =  new CF7SG_SIGN_ACTIONS;

		return static::$instance;
	}

	private function __construct(){

		if ( empty( $_POST['_wpcf7_unit_tag'] ) ) {
			return false;
		}

		add_filter( 'wpcf7_validate_sign', [$this,'validation_filter'], 10, 2 );
		add_filter( 'wpcf7_validate_sign*', [$this,'validation_filter'], 10, 2 );
		add_filter( 'wpcf7_mail_components', [$this,'mail_components'], 10, 3 );
		add_action( 'wpcf7_mail_sent', [$this,'delete_file']);
		add_action( 'wpcf7_mail_failed', [$this,'delete_file']);

		add_filter('cfdb7_before_save_data', [$this, 'cfdb7_before_save_data']);
		add_filter('cf7adb_before_save_data', [$this, 'cf7adb_before_save_data']);

		$this->uploads_dir = $this->wpcf7_upload_tmp_dir();
	}

	private function sanitize_posted_data( $value ) {
		
		$value = wp_check_invalid_utf8( $value );
		$value = wp_kses_no_null( $value );
		
		return $value;
	}

	public function delete_file(){
		foreach($this->sign_inputs as $sign => $filename){
			@unlink( $filename );
		}	
	}

	public function cfdb7_before_save_data( $data ){
		$upload_dir    = wp_upload_dir();
   		$cfdb7_dir = $upload_dir['basedir'].'/cfdb7_uploads';
		foreach( $this->sign_inputs as $sign => $filename ){
			$data[ $sign.'cfdb7_file' ] = $filename;
			copy($this->uploads_dir.'/'.$filename, $cfdb7_dir.'/'.$filename);
			unset( $data[$sign] );
		}
		return $data;
	}

	public function cf7adb_before_save_data( $data ){
		$upload_dir    = wp_upload_dir();
   		$cf7adb_dir = $upload_dir['basedir'].'/cf7db_uploads';
		foreach( $this->sign_inputs as $sign => $filename ){
			$data[ $sign.'_cf7dbp_file' ] = $filename;
			copy($this->uploads_dir.'/'.$filename, $cf7adb_dir.'/'.$filename);
			unset( $data[$sign] );
		}
		return $data;
	}

	private function wpcf7_upload_tmp_dir() {
		if ( defined( 'WPCF7_UPLOADS_TMP_DIR' ) ) {
			$dir = path_join( WP_CONTENT_DIR, WPCF7_UPLOADS_TMP_DIR );
			wp_mkdir_p( $dir );
	
			if ( wpcf7_is_file_path_in_content_dir( $dir ) ) {
				return $dir;
			}
		}
	
		$dir = path_join( $this->wpcf7_upload_dir( 'dir' ), 'wpcf7_uploads' );
		wp_mkdir_p( $dir );
		return $dir;
	}

	private function wpcf7_upload_dir( $type = false ) {
		$uploads = wp_get_upload_dir();
	
		$uploads = apply_filters( 'wpcf7_upload_dir', array(
			'dir' => $uploads['basedir'],
			'url' => $uploads['baseurl'],
		) );
	
		if ( 'dir' == $type ) {
			return $uploads['dir'];
		} if ( 'url' == $type ) {
			return $uploads['url'];
		}
	
		return $uploads;
	}


	/** 
	 * Input validation
	 */
	public function validation_filter( $result, $tag ) {
		$name = $tag->name;

		$value = $this->sanitize_posted_data( $_POST[$name] );

		if ( $tag->is_required() and '' === $value ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
			return $result;
		}

		$this->sign_inputs[$name] = '';


		if( empty( $value ) ) 
				return $result;

		$filename = md5($name).time().rand(1,1000).'.png';

		list($type, $value) = explode(';', $value);
		list(,$extension) = explode('/',$type);
		list(,$value)      = explode(',', $value);
		$value = base64_decode($value);
		file_put_contents($this->uploads_dir.'/'.$filename, $value);

		@chmod( $filename, 0644 );

		$this->sign_inputs[$name] = $filename;

		return $result;
	}



	/** 
	 * Add sign to mail 
	 */
	public function mail_components( $components, $contact_form, $mail ){

			$template = $mail->get( 'attachments' );
			$attachments = array();

			foreach ( $this->sign_inputs as $tagname => $filename ) {
				if (  ! empty( $filename ) && false !== strpos( $template, "[${tagname}]") ){
					$attachments[] = esc_url($this->uploads_dir.'/'.$filename);
				}
			}
			
			$components['attachments'] = array_merge( $attachments, $components['attachments']);
			return $components;
	}
}
