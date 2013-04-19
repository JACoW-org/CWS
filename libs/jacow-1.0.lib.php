<?php

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

?>