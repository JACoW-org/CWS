#!/usr/bin/php
<?php

// 2013.07.29 bY Stefano.Deiuri@Elettra.Trieste.it

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
file_write( TMP_PATH .'/' .$type .'.csv', $csv );
echo 'OK (' .count($csv) .' records)';
echo "\n";

$participants =array();
$countries =array();
			
foreach ($csv as $id =>$line) {
	list( $surname, $name, $inst, $nation, $type ) =explode( '","', substr( trim($line), 1, -1 ) );
			
	$participants[$type][$surname .' ' .$name] ="<b>$surname $name</b> ($inst, $nation)";

	if (array_key_exists( $nation, $countries )) $countries[$nation] ++;
	else $countries[$nation] =1;
} 

$D =&$participants['D'];
ksort( $D );
$delegates_n =count( $D );
$delegates_list =$delegates_n ? "<h2>Delegates</h2>\n<p class='participants_list'>\n" .implode( "<br />\n", $D ) ."</p>\n" : false;
$delegates_count =$delegates_n ? "<b>${delegates_n}</b> delegates" : false;


$S =&$participants['S'];
ksort( $S );
$exhibitors_n =count( $S );
$exhibitors_list =$exhibitors_n ? "<h2>Exhibitors' Representatives and Assistants</h2>\n<p class='participants_list'>\n" .implode( "<br />\n", $S ) ."</p>\n" : false;
$exhibitors_count =$exhibitors_n ? ", <b>${exhibitors_n}</b> <a href='#exhibitors'>exhibitors</a>' representatives and assistants" : false;


arsort( $countries );
$countries_n =count( $countries );
if ($countries_n) {
	$countries_list ="<h2>Countries</h2>\n<table class='participants_countries'>";
	foreach ($countries as $name =>$num) {
		if ($name && $name != 'Unknown') $countries_list .="<tr><th>$name</th><td vliagn='middle'><div class='chart_bar' style='width: ${num}px;'></div> $num</td></tr>\n";
	}
	$countries_list .="</table>";
	$countries_count =", from <b>${countries_n}</b> <a href='#countries'>countries</a>";
} else {
	$countries_list =false;
	$countries_count =false;
}

$fname =OUT_PATH .'/Participants.html';
echo "Save file $fname... ";
$template =file_read( 'Template.html' );
eval( "\$out =\"$template\";" );
echo file_write( $fname, $out ) ? 'OK' : 'Error';

?>
