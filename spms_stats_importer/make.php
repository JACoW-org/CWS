#!/usr/bin/php
<?php

// 2018.05.07 bY Stefano.Deiuri@Elettra.Eu

require( '../config.php' );
require_lib( 'cws','1.0' );

config( 'spms_stats_importer' );
echo date( 'r' ) ."\n" .date('YmdHis') ."\n\n";

echo "\n";

echo "Load PO file (" .APP_PO .")... ";
$po =file_read_json( APP_PO, true );
echo_ok();

echo "\n";


// Get EDITORS ACTIVITY -------------------------------------------------------

if (!file_exists( APP_EDITORS_XML ) || filesize(APP_EDITORS_XML) < 999 || (time() -filemtime( APP_EDITORS_XML )) > 60) {
	echo "Get EDITORS ACTIVITY XML file " .APP_EDITORS_XML_URL ."... ";
	$xmlfile =file( APP_EDITORS_XML_URL );
	$xml_str =implode( '', $xmlfile );
		
	if (DEBUG) file_write( APP_EDITORS_XML .'-' .date('YmdHis'), $xml_str, 'w' );
	
	if (count($xmlfile) && strlen( $xml_str ) > 999) {
		file_write( APP_EDITORS_XML, $xml_str, 'w' );
		echo_ok();

	} else {
		echo_error( "ERROR! (No data)" );
		exit(2);
	}
	
} else {
	echo "Load EDITORS ACTIVITY file " .APP_EDITORS_XML ."... ";
	$xml_str =implode( '', file( APP_EDITORS_XML ));
	echo_ok();
}

$xml =simplexml_load_string( $xml_str );

$editors_stats =array();

foreach ($xml->STAFF_MEMBER as $editor) {
	$activity =false;
	foreach ($editor->ACTIVITIES->ACTIVITY as $x) {
		$activity[ (string)$x->NAME ] =(string)$x->COUNT;
	}
	
	$initdot =false;
	foreach ($editor->INITIAL_DOT_STATUS->DOT_STATUS as $x) {
		$initdot[ strtolower(substr((string)$x->DOT, 0, 1 )) ] =str_replace('%','',(string)$x->STATUS);
	}
	
	$editor_name =(string)$editor->NAME;

	$id =str_pad( $activity['Edit Complete'], 3, '0', STR_PAD_LEFT ) .'|' .$editor_name;
	
	$editors_stats[ $id ] =array(
		'name' =>$editor_name,
		'complete' =>$activity['Edit Complete'],
		'qa' =>$activity['Final QA Passed'] +$activity['Final QA Failed'],
		'dots' =>$initdot
		);
}

krsort( $editors_stats );



// Get EDOT XML ---------------------------------------------------------------

if (!file_exists( APP_EDOT_XML ) || filesize(APP_EDOT_XML) < 999 || (time() -filemtime( APP_EDOT_XML )) > 60) {
	echo "Get EDOT XML file " .APP_EDOT_XML_URL ."... ";
	$xmlfile =file( APP_EDOT_XML_URL );
	$xml_str =implode( '', $xmlfile );
	
	if (DEBUG) file_write( APP_EDOT_XML .'-' .date('YmdHis'), $xml_str, 'w' );

	if (count($xmlfile) && strlen( $xml_str ) > 999) {
		file_write( APP_EDOT_XML, $xml_str, 'w' );
		echo_ok();
		
	} else {
		echo_error( "ERROR! (No data)" );
		exit(2);
	}
	
} else {
	echo "Load EDOT XML file " .APP_EDOT_XML ."... ";
	$xml_str =implode( '', file( APP_EDOT_XML ));
	echo_ok();
}

$xml =simplexml_load_string( $xml_str );



$stats =array( 'qaok' =>0, 'files' =>0, 'g' =>0, 'y' =>0, 'r' =>0, 'nofiles' =>0, 'processed' =>0, 'total' =>0 );

$now =time();

if (file_exists( APP_EDOT )) $dots =file_read_json( APP_EDOT, true );
else $dots =array();

$paper_id_list =array();

foreach ($xml->papers->paper as $paper) {
	$x =(array)$paper->attributes();
	$paper_id =(string)$paper['SESSION'] .(string)$paper['SEQUENCE'];

	$paper_id_list[] =$paper_id;
	
	$qaok =((string)$paper['QA'] == 'FQ');

	$paper_status =strtolower((string)$paper['CODE']);
	
	if ((string)$paper['QA'] == 'QAF') $paper_status ='a';
	else if (!$paper_status) $paper_status ='nofiles';

	if (!isset($po[$paper_id])) {
		echo "\t# unset $paper_id\n";
		
	} else if (!isset($dots[$paper_id]) 
		|| $dots[$paper_id]['status'] != $paper_status 
		|| $dots[$paper_id]['qaok'] != $qaok 
		|| $dots[$paper_id]['pc'] != $po[$paper_id]['primary_code']) {
		
		$dots[$paper_id] =array( 
			'pc' =>$po[$paper_id]['primary_code'], 
			'status' =>$paper_status, 
			'qaok' =>$qaok, 
			'ts' =>$now 
			);
	}
}

foreach ($dots as $paper_id =>$x) {
//	if (!in_array( $paper_id, $paper_id_list )) unset( $dots[$paper_id] );
	if (!in_array( $paper_id, $paper_id_list ) && $dots[$paper_id]['status'] != 'removed') $dots[$paper_id] =array( 'status' =>'removed', 'ts' =>$now );
}

echo "Write EDOT file (" .APP_EDOT .")... ";
file_write_json( APP_EDOT, $dots );
echo_ok();

echo "\n";


foreach ($dots as $paper_id =>$x) {
	$qaok =false;

	extract( $x );
	
	if ($pc == 'Y') {
		if ($status != 'removed') {
			$stats['total'] ++;
			if ($qaok) $stats['qaok'] ++;
		}
		
		if (isset($stats[$status])) $stats[$status] ++;
		else $stats[$status] =1;	

	}
}

$stats['processed'] =$stats['g'] +$stats['y'] +$stats['r'];

if (file_exists(APP_STATS)) {
	echo "Load STATS file (" .APP_STATS .")... ";
	$stats_db =file_read_json( APP_STATS, true );
	echo_ok();
	
} else {
	$stats_db =false;
}


$tm =date( 'Y-m-d-H' );
$last_stats =$stats_db[$tm];
unset($last_stats['ts']);

if (json_encode($last_stats) == json_encode($stats)) return;


echo "Update STATS file... ";
$stats['ts'] =time();
$stats_db[$tm] =$stats;

echo_ok();

echo "\n";
echo "Write STATS file (" .APP_STATS .")... ";
file_write_json( APP_STATS, $stats_db );
echo_ok();

echo "\n";
echo "Write STATS_LAST file (" .APP_STATS_LAST .")... ";
file_write_json( APP_STATS_LAST, $stats );
echo_ok();

echo "\n";
echo "Write EDITORS STATS file (" .APP_EDITORS .")... ";
file_write_json( APP_EDITORS, $editors_stats );
echo_ok();

echo "\n";

?>
