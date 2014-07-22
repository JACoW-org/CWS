#!/usr/bin/php
<?php

// 2013.03.01 bY Stefano.Deiuri@Elettra.Trieste.it

if (in_array( '--help', $argv )) {
	echo "Program options:\n"
		."\n";
	return;
}

require( '../conference.php' );
require( '../libs/jacow-1.0.lib.php' );
require( '../libs/spms_chart-1.1.class.php' );

$Chart =new SPMS_Chart( SPMS_URL );

$Chart->Config( 'width', CHART_WIDTH );
$Chart->Config( 'height', CHART_HEIGHT );
$Chart->Config( 'color1', CHART_COLOR1 );
$Chart->Config( 'skip_format_check', true );

$Chart->GoogleChart( 'regstats', false, 'Registrants' );

?>
