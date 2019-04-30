<?php

// 2019.01.04 by Stefano.Deiuri@Elettra.Eu

define( 'WEB_ECHO_STYLE', 'font-family: Arial; font-weight: bold; padding: 3px;' );

//-----------------------------------------------------------------------------
function echo_result( $_status, $_error_message ='ERROR', $_ok_message ='OK' ) {
 if ($_status) echo_ok( $_ok_message);
 else echo_error( $_error_message );
 
 return $_status;
}

//-----------------------------------------------------------------------------
function echo_ok( $_message ='OK' ) { 
 if (defined(APP_ECHO_MODE) && APP_ECHO_MODE == 'web') echo "<div style='" .WEB_ECHO_STYLE ." color: green;'>" .str_replace( "\n", "<br />", $_message )."</div>\n";
 else echo (defined(COLORED_OUTPUT) && COLORED_OUTPUT? "\033[0;32m$_message\033[0m\n" : "$_message\n"); 
}

//-----------------------------------------------------------------------------
function echo_error( $_message ='ERROR' ) { 
 if (defined(APP_ECHO_MODE) && APP_ECHO_MODE == 'web') echo "<div style='" .WEB_ECHO_STYLE ." color: red;'>" .str_replace( "\n", "<br />", $_message )."</div>\n";
 else echo (defined(COLORED_OUTPUT) && COLORED_OUTPUT ? "\033[0;31m$_message\033[0m\n" : "$_message\n"); 
}

//-----------------------------------------------------------------------------
function need_file() {
 $files =func_get_args();
 
 foreach ($files as $fname) {
	if (!file_exists( $fname )) {
		echo_error( "ERROR (Unable to open $fname)" );
		return false;
	}
 }
	
 return true;
}






//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
class CWS_OBJ {
 var $cfg =false;

 //-----------------------------------------------------------------------------
 public function config( $_var =false, $_val =false ) {
	if (is_array($_var)) {
		$this->cfg =$_var;

	} else if ($_var == false) {
		$c =$this->cfg;
		$c['spms_passphrase'] ='*****';
		print_r( $c );

	} else {
		$this->cfg[$_var] =$_val;
	}
 }
 
 //-----------------------------------------------------------------------------
 function verbose( $_message ="", $_level =1, $_nl =true ) {
	if (is_array( $_message )) {
		foreach ($_message as $level =>$message) {
			if ($this->cfg['verbose'] >= $level) echo $message;
		}
		return;
	}
	 
	if ($this->cfg['verbose'] >= $_level) echo $_message .($_nl ? "\n" : false);
 }
 
 //-----------------------------------------------------------------------------
 function verbose2( $_message ="", $_level =1, $_nl =true ) {
	if ($this->cfg['verbose'] == $_level) echo $_message .($_nl ? "\n" : false);
 }
 
 //-----------------------------------------------------------------------------
 function verbose_ok( $_message ='OK', $_level =1 ) {
	if ($this->cfg['verbose'] >= $_level) echo_ok( $_message );
 }
 
 //-----------------------------------------------------------------------------
 function verbose_error( $_message ='ERROR', $_level =1 ) {
	if ($this->cfg['verbose'] >= $_level) echo_error( $_message );
 } 
}







//----------------------------------------------------------------------------
function config( $_app =false, $_check_in_file_exit =false ) {
 global $cws_config;
 
 foreach (array( 'conf_name', 'spms_url', 'spms_passphrase', 'cws_timezone', 'root_url', 'root_path') as $var) {
	if (!isset($cws_config['global'][$var]) || $cws_config['global'][$var] == '') {
		echo_error( "\n\nWrong configuration! Please check config.php! (global\\$var)\n\n\n" );
		die;
	}	 
 }
 
 if (!$_app) {
	$p =pathinfo( $_SERVER['PWD'] );
	$_app =$p['basename'];
	
	echo "Read config for $_app\n\n";
 }
  
 if (!isset($cws_config[$_app])) {
	echo_error( "App undefined!\n\n" );
	die;
 }
 
 cws_define( 'app', $_app );
  
 $cfg =$cws_config[$_app];
 
 foreach (array('data_path','tmp_path','out_path') as $path_name) {
	if (!isset($cfg[$path_name])) $$path_name =$cfg[$path_name] =$cws_config['global'][$path_name];
	else {
		$$path_name =$cfg[$path_name];
	}
 }
 
 foreach ($cws_config['global'] as $name =>$value) {
	$value =str_replace( '{root_path}', ROOT_PATH, $value );

	list( $name1, $name2 ) =explode( '_', $name );
	if ($name1 == 'color') $cfg['colors'][$name2] =$value;
	else if ($name1 == 'label') $cfg['labels'][$name2] =$value;	 
	
	cws_define( $name, $value );
 }
 
 foreach ($cfg as $name =>$value) {
	list( $name1, $name2 ) =explode( '_', $name );
	 
	if ($name1 == 'color') $cfg['colors'][$name2] =$value;
	else if ($name1 == 'label') $cfg['labels'][$name2] =$value;	 
	else if ($name != 'colors' && $name != 'labels') {
		$value =str_replace( '{app_data_path}', $data_path, $value );
		$value =str_replace( '{app_out_path}', $out_path, $value );
		$value =str_replace( '{app_tmp_path}', $tmp_path, $value );
		$value =str_replace( '{root_path}', ROOT_PATH, $value );
		$value =str_replace( '{data_path}', DATA_PATH, $value );
		$value =str_replace( '{out_path}', OUT_PATH, $value );
		$value =str_replace( '{tmp_path}', TMP_PATH, $value );
		$value =str_replace( '{root_url}', ROOT_URL, $value );
		$value =str_replace( '{spms_url}', SPMS_URL, $value );
		$value =str_replace( '{app}', $_app, $value );
		 
		if (substr( $name, 0, 3 ) == 'in_') {
			if (!file_exists( $value ) || filesize( $value ) == 0) {
				if ($_check_in_file_exit) return false;
				echo_error( "ERROR! Missing file $value!" );
				die;
			}
			
			if (!filesize( $value ) || filesize( $value ) == 0) {
				if ($_check_in_file_exit) return false; 
				echo_error( "ERROR! Bad size file $value!" );
				die;
			}
			
			$name =substr( $name, 3 );
		}

		$cfg[$name] =$value;
	}
	 
	cws_define( $name, $value, true );
 }

 foreach ($cws_config['global'] as $name =>$value) {
	list( $name1, $name2 ) =explode( '_', $name );
	if (!isset($cfg[$name])) $cfg[$name] =$value;
 }

 foreach (array('data_path','tmp_path','out_path') as $path_name) {
	$name =strtoupper( substr( $path_name, 0, -5 ));
	$path =$cfg[$path_name];
	if (!file_exists( $path )) {
		echo "Create $name directory ($path)... ";
		if (mkdir( $path )) {
			system( 'chown apache.apache ' .$path );
			system( 'chmod 775 ' .$path );
			echo_ok();
		} else {
			echo_error( "ERROR! (unable to create $name directory)" );
			die;
		}		
	} else {
		if (!is_writable( $path )) {
			echo_error( "ERROR! Unable to write in $name directory ($path)" );
			die;
		}
	}
 }
 
 date_default_timezone_set( CWS_TIMEZONE );
 
 return $cfg;
}

//----------------------------------------------------------------------------
function cws_define( $_name, $_value, $_app =false ) {
	if (is_array( $_value )) return;
	
	$name =($_app ? 'APP_' : false) .strtoupper( $_name);
	define( $name, $_value );
//	echo "define $name =$_value\n";
}


//----------------------------------------------------------------------------
function file_write( $_filename, $_data, $_mode ='w', $_verbose =false, $_verbose_message =false ) {
	
 if ($_verbose)	echo "Save $_verbose_message ($_filename)... ";
	
 $fp =fopen( $_filename, $_mode );

 if (!$fp) {
//	echo "unable to save file $_filename!\n";
	if ($_verbose)echo_error( "ERROR (writing)" );
	return false;
 }

 fwrite( $fp, (is_array($_data) ? implode('',$_data) : $_data) );

 fclose( $fp );

 if ($_verbose)	echo_ok();

 return true;
}

//----------------------------------------------------------------------------
function file_read( $_filename, $_verbose =false, $_verbose_message =false ) {
 
 if ($_verbose)	echo "Read $_verbose_message ($_filename)... ";
 
 if (!file_exists( $_filename )) {
	if ($_verbose) echo_error( "ERROR (not exists)" );
	return false;
 }
	
 $c =file( $_filename );
 
 if (empty($c)) {
	if ($_verbose) echo_error( "ERROR (empty file)" );
	return false;
 }
	
 if ($_verbose)	echo_ok();
 
 return implode( '', $c );
}

//----------------------------------------------------------------------------
function file_write_json( $_filename, &$_obj ) {
 return file_write( $_filename, json_encode( $_obj ));
}

//----------------------------------------------------------------------------
function file_read_json( $_filename, $_assoc =false ) {
 if (!file_exists( $_filename )) return false;
 
 return json_decode( implode( '', file( $_filename )), $_assoc );
}

//-----------------------------------------------------------------------------
function download_file( $_tmp_fname, $_download_fname, $_content_type ='text' ) {
 header( 'Content-type: ' .$_content_type );
 header( 'Content-Disposition: attachment; filename=' .$_download_fname );
 header( 'Content-Length: ' .filesize( $_tmp_fname ) );
 header( 'Pragma: public' );
 readfile( $_tmp_fname );
}

//-----------------------------------------------------------------------------
function _R( $_name, $_value =false, $_false_value =false ) {
 if (!isset( $_REQUEST[$_name] )) return $_false_value;
 if ($_value && ($_REQUEST[$_name] != $_value)) return $_false_value;
 return $_REQUEST[$_name];
} 

//-----------------------------------------------------------------------------
function _P( $_name, $_value =false, $_false_value =false ) {
 if (!isset( $_POST[$_name] )) return $_false_value;
 if ($_value && ($_POST[$_name] != $_value)) return $_false_value;
 return $_POST[$_name];
}

//-----------------------------------------------------------------------------
function _G( $_name, $_value =false, $_false_value =false ) {
 if (!isset( $_GET[$_name] )) return $_false_value;
 if ($_value && ($_GET[$_name] != $_value)) return $_false_value;
 return $_GET[$_name];
}

//-----------------------------------------------------------------------------
function gz_http_response( $_text ) {
 if (strpos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip' ) !== false) {
	ob_start("ob_gzhandler");
 } else {
	ob_start();
 }
	
 echo $_text;
 ob_flush();
}

?>