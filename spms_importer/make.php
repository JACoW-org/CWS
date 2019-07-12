#!/usr/bin/php
<?php

// 2019.02.21 by Stefano.Deiuri@Elettra.Eu

if (in_array( '--help', $argv )) {
	echo "Program options:\n"
		."\t--cleanup: clear cached data\n"
		."\t--skip-wget: use cached xml files instead of download again\n"
		."\n";
	return;
}

require( '../config.php' );
require_lib( 'cws', '1.0' );

$cfg =config( 'spms_importer' );

//print_r( $cfg ); die;

foreach ($argv as $cmd) {
	if (substr( $cmd, 0, 10 ) == '--verbose=') $cfg['verbose'] =substr( $cmd, 10 );
	else if ($cmd == '--skip-wget') $cfg['wget'] =false;
}

require_lib( 'spms_importer', '1.0' );

$SPMS =new SPMS_Importer;
$SPMS->config( $cfg );

// Show configuration
if ($cfg['verbose'] > 2) $SPMS->config();

/*
$dates =$SPMS->xtract( 'dates', false, true );
print_r( $dates );
*/

if (in_array( '--cleanup', $argv )) { return $SPMS->cleanup(); }

$ok =$SPMS->load_xml( SPMS_URL );

if ($ok) {
	$SPMS->save_programme();
	$SPMS->save_abstracts();
	$SPMS->save_po(); 
	$SPMS->save_posters();
	
	$SPMS->export_citations();
	$SPMS->export_transparencies();
}

print_r( $SPMS->programme['rooms'] );

?>
