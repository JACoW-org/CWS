#!/usr/bin/php
<?php

// 2015.01.27 bY Stefano.Deiuri@Elettra.Trieste.it

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
$color1 =CHART_COLOR1;
$exhibitors =false;

$Chart =new SPMS_Chart( SPMS_URL );
$Chart->Config( 'width', CHART_WIDTH );
$Chart->Config( 'height', CHART_HEIGHT );
$Chart->Config( 'color1', CHART_COLOR1 );
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
	$line =iconv( 'ISO-8859-1', 'UTF-8//TRANSLIT', trim($line) );
	list( $surname, $name, $inst, $nation, $type ) =explode( '","', substr( $line, 1, -1 ) );
			
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

$tmpl_fname =$exhibitors ? 'Template.html' : 'Template-without-exhibitors.html';
echo "Read template file ($tmpl_fname)\n";
$template =file_read( $tmpl_fname );
eval( "\$out =\"$template\";" );

$fname =OUT_PATH .'/Participants.html';
echo "Save html file... " .(file_write( $fname, $out ) ? 'OK' : 'Error') ."\n";

$template =file_read( 'Participants.css' );
eval( "\$out =\"$template\";" );
echo "Save css file... " .(file_write( OUT_PATH .'/Participants.css', $out ) ? 'OK' : 'Error') ."\n";

?>
