<?php

// 2018.05.07 bY Stefano.Deiuri@Elettra.Eu

error_reporting(E_ERROR);

require( '../config.php' );
require_lib( 'cws', '1.0' );

if (!$cfg =config( 'page_po_status', true )) {
	echo json_encode(array( 'error' => true ));
	die;
}
	
$ret =array();
	
$editors =file_read_json( APP_EDITORS, true );
$dots =file_read_json( APP_EDOT, true );
$stats_db =file_read_json( APP_STATS, true );

$ts_rqst =$_GET['ts'];

foreach ($dots as $paper_id =>$p) {
	if (($p['pc'] == 'Y' || $p['status'] == 'removed') && $p['ts'] > $ts_rqst) {
		$status =$p['status'];
		if ($status == 'nofiles') $status ='';
		$class =($p['qaok'] ? 'qaok' : $status);				
		$ret['edots'][$paper_id] =$class;
	}
}

	
if (!$ts_rqst) {
	$ret['history'] =$stats_db;

} else {
	foreach ($stats_db as $tm2 =>$x) {
		if ($x['ts'] > $ts_rqst) $ret['history'][$tm2] =$x;
	}	
}	

$ret['editors'] =$editors;

$ret['ts'] =time();
//$ret['ts'] = 0;

$ret['colors'] =array();
foreach (array( 'files', 'a', 'qaok', 'g', 'y', 'r', 'nofiles' ) as $cname) {
	$ret['colors'][] =$cfg['colors'][$cname];
}

$ret['labels'] =$cfg['labels'];

if ($_GET['debug']) {
	echo "<pre>";
	$cfg['spms_passphrase'] ='****';
	print_r( $cfg );
	print_r( $ret );
	return;
}

gz_http_response( json_encode( $ret ) );

?>
