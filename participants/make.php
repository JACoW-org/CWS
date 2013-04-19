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

$chart_var ='Registrants';
$width =CHART_WIDTH;
$height =CHART_HEIGHT;

$Chart =new SPMS_Chart( SPMS_URL );
$Chart->Config( 'width', CHART_WIDTH );
$Chart->Config( 'height', CHART_HEIGHT );
$Chart->Config( 'append_chk', false );
$Chart->Config( 'skip_format_check', true );
$Chart->Config( 'template_html', false );
$Chart->GoogleChart( 'regstats', false, $chart_var );

$type ='attendees';
$url =SPMS_URL .'/xtract.' .$type;
echo "Get data from: $url.. ";
$csv =file( $url );
file_write( TMP_ATH .'/' .$type .'.csv', $csv );
echo 'OK (' .count($csv) .' records)';
echo "\n";

$participants =array();
$countries =array();
			
foreach ($csv as $id =>$line) {
	list( $surname, $name, $inst, $nation, $type ) =explode( '","', substr( trim($line), 1, -1 ) );
			
	$participants[$type]["$surname $name"] ="<b>$surname $name</b> ($inst, $nation)";
	$countries[$nation] ++;
} 

$D =&$participants['D'];
ksort( $D );
$delegates_n =count( $D );
$delegates_list =implode( "<br />\n", $D );

$S =&$participants['S'];
ksort( $S );
$exhibitors_n =count( $S );
$exhibitors_list =implode( "<br />\n", $S );

arsort( $countries );
$countries_n =count( $countries );
$countries_list ="<table class='participants_countries'>";
foreach ($countries as $name =>$num) {
	if ($name && $name != 'Unknown') $countries_list .="<tr><th>$name</th><td vliagn='middle'><div class='chart_bar' style='width: ${num}px;'></div> $num</td></tr>\n";
}
$countries_list .="</table>";

$fname =OUT_PATH .'/Participants.html';
echo "Save file $fname... ";
$template =file_read( 'Template.html' );
eval( "\$out =\"$template\";" );
echo file_write( $fname, $out ) ? "OK" : "Error";

?>
