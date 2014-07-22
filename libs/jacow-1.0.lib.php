<?php

// 2014.05.28 by Stefano.Deiuri@Elettra.Eu

//----------------------------------------------------------------------------
function file_write( $_filename, $_data, $_mode ='w' ) {
 $fp =fopen( $_filename, $_mode );

 if (!$fp) {
//	echo "unable to save file $_filename!\n";
	return false;
 }

 fwrite( $fp, (is_array($_data) ? implode('',$_data) : $_data) );

 fclose( $fp );

 return true;
}

//----------------------------------------------------------------------------
function file_read( $_filename ) {
 return implode( '', file( $_filename ));
}

//----------------------------------------------------------------------------
function file_write_json( $_filename, &$_obj ) {
 return file_write( $_filename, json_encode( $_obj ));
}

//----------------------------------------------------------------------------
function file_read_json( $_filename, $_assoc =false ) {
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

?>