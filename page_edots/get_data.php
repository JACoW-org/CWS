<?php

// 2017.05.04 bY Stefano.Deiuri@Elettra.Eu

error_reporting(E_ERROR);

require( '../config.php' );
require_lib( 'cws', '1.0' );

if (!config( 'page_edots', true )) {
	echo json_encode(array( 'error' => true ));
	die;
}
	
$ret =array();
	
$dots =file_read_json( APP_EDOT, true );

$ts_rqst= $_GET['ts'];

$map_days =array( 'mo' =>1, 'tu' =>2, 'we' =>3, 'th' =>4, 'fr' =>5 );

foreach ($dots as $paper_id =>$p) {
	if (($p['pc'] == 'Y' || $p['status'] == 'removed') && $p['ts'] > $ts_rqst) {
		$status =$p['status'];
		if ($status == 'nofiles') $status ='';
		$class =($p['qaok'] ? 'qaok' : $status);				
		
		$day =strtolower(substr($paper_id,0,2));
		
		$ret['edots'][$map_days[$day].$paper_id] =$class;
	}
}

ksort( $ret['edots'] );


$ret['title'] =CONF_NAME .' ' .APP_NAME;

$ret['ts'] =time();

if ($_GET['debug']) {
        echo "<pre>";
	$cfg['spms_passphrase'] ='****';
	print_r( $cfg );
	print_r( $ret );
	return;
}

gz_http_response( json_encode( $ret ) );

?>
