<?php

// 2018.04.13 bY Stefano.Deiuri@Elettra.Eu

require( '../config.php' );
require_lib( 'cws','1.0' );

$cfg =config( 'app_paper_status' );

ini_set( 'display_errors', 0 ) ;

date_default_timezone_set( CWS_TIMEZONE );

$on_load =false;
$message =false;
$page =false;

$primary_color =COLOR_PRIMARY;

if (!need_file( APP_PO )) {
	echo_error( "\n\nTry to run spms_importer/make.php!" );
	die;
}

if (!need_file( APP_EDOT )) {
	echo_error( "\n\nTry to run spms_stats_importer/make.php!" );
	die;
}


	
$pc =strtoupper(trim(_G('paper_code')));
if($pc) {
	$papers_status =file_read_json( APP_EDOT, true );
	
	if (isset($papers_status[$pc])) {
		$title ="$pc Paper Status";

		$papers_details =file_read_json( APP_PO, true );
		
		$status_code =$papers_status[$pc]['status'];
		
		$primary_color =$cfg['colors'][$status_code];
		
		$message =$cfg['labels'][$status_code];
				
		$page ="
<div id='status_msg' class='b_$status_code'>
$message
</div>
<div id='status'>
$pc
<br />
<br />
<b>" .$papers_details[$pc]['title'] ."</b>

<hr noshade size='1' />
<small>page loaded at " .date( 'Y-m-d H:i (O)' ) ."</small>
<input type='button' id='refresh' onClick='location.reload(true);' value='Refresh' />

</div>
";
		file_write( APP_LOG, date('U') ."\t$pc\t$status_code\n", 'a' );
		
	} else {
		$message ="<b style='color: red;'>Paper not found</b><br /><br />";
	}
}
	
if (!$page) {
	$title =CONF_NAME ." Paper Status";
	$page ="
<center>
<h1>".CONF_NAME."</h1>
$message
<form>
PAPER CODE
<br />
<input type='text' name='paper_code' id='paper_code' />
<br />
<input type='submit' value='SEARCH' />
</form>
</center>
";

	$on_load ="onLoad=\"document.getElementById('paper_code').focus();\"";
}


$tmpl =implode( '', file( APP_TEMPLATE_HTML ));
foreach (array( 'title', 'on_load', 'page', 'primary_color' ) as $var) {
	$tmpl =str_replace( '{'.$var.'}', $$var, $tmpl );
}
echo $tmpl;

?>
