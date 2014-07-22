#!/usr/bin/php
<?php

// 2013.05.28 by Stefano.Deiuri@Elettra.Eu

if (in_array( '--help', $argv )) {
	echo "Program options:\n"
		."\t--cleanup: clear cached data\n"
		."\t--skip-xml: use cached xml files instead of download again\n"
		."\t--skip-wget: use cached xml files instead of download again\n"
		."\n";
	return;
}

require( '../conference.php' );
require( '../libs/jacow-1.0.lib.php' );
require( '../libs/spms_programme-1.5.class.php' );

/*
class IPAC14_Programme extends SPMS_Programme {
 function session( &$ps, $sid, &$html ) {
	$page =false;
	
	if ($ps[0] == 'WEOCA') {
		$html['_EMPTY'] ="<td width='##W##' class='roomB continue'>&nbsp;</td>";
		$ps =array( $ps[0], 'EMPTY' ); 
	}

	$page =$this->multi_session( $ps, $html );
	
	return $page;
 }
}

$Programme =new IPAC14_Programme;
*/

$Programme =new SPMS_Programme;

// Config script
//$Programme->config( 'path', '../programme' );
$Programme->config( 'programme_base_url', 'index.php' );
$Programme->config( 'tab_w', " width='750'" );
$Programme->config( 'tsz_adjust', 5*60*60 );

if (in_array( '--skip-wget', $argv )) { $Programme->config( 'wget', false ); }
if (in_array( '--cleanup', $argv )) { return $Programme->cleanup(); }

// Show configuration
$Programme->config();

$Programme->prepare();

if (in_array( '--skip-xml', $argv )) {
	$Programme->load();
} else {
	$Programme->load_xml( SPMS_URL );
	$Programme->save();
}

$Programme->make();
$Programme->make_abstracts();
//$Programme->make_ics();

?>
