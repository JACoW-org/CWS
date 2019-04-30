#!/usr/bin/php
<?php

// 2019.02.21 by Stefano.Deiuri@Elettra.Eu

require( '../config.php' );
require_lib( 'cws', '1.0' );
$cfg =config( 'make_page_programme' );

require_lib( 'spms_programme', '2.0' );

class IPAC19_Programme extends SPMS_Programme {
 function session( &$ps, $sid, &$html ) {
	$page =false;

/*	
	if ($ps[0] == 'WEOCA') {
		$html['_EMPTY'] ="<td width='##W##' class='roomB continue'>&nbsp;</td>";
		$ps =array( $ps[0], 'EMPTY' ); 
		
	} else 
*/		
	if ($ps[0] == 'TUWPLS' || $ps[0] == 'WEZZPLS') {
		$html['_EMPTY'] ="<td width='##W##' class='roomMainPlenaryplenary2 continue'>&nbsp;</td>";
		$ps =array( 'EMPTY', $ps[0] ); 
	}

	$page =$this->multi_session( $ps, $html );
	
	return $page;
 }
}

$Programme =new IPAC19_Programme;

foreach ($argv as $cmd) {
	if (substr( $cmd, 0, 10 ) == '--verbose=') $cfg['verbose'] =substr( $cmd, 10 );
}

$Programme->config( $cfg );

// Show configuration
if ($cfg['verbose'] > 2) $Programme->config();


if (!need_file( APP_ABSTRACTS ) || !need_file( APP_PROGRAMME )) {
	echo "\n\nTry to run spms_importer/make.php\n\n";
	die;
}

$Programme->load();

$Programme->prepare();

$Programme->make();

$Programme->make_abstracts();

$Programme->make_rooms_css();

//$Programme->make_ics();

?>
