#!/usr/bin/php
<?php

// 2019.01.22 bY Stefano.Deiuri@Elettra.Eu

if (in_array( '--help', $argv )) {
	echo "Program options:\n"
		."\n";
	return;
}

require( '../config.php' );
require_lib( 'cws', '1.0' );
require_lib( 'spms_importer', '1.0' );

$SPMS =new SPMS_Importer( config() );
$SPMS->GoogleChart();

?>