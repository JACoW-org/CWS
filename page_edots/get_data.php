<?php

// 2019.09.06 bY Stefano.Deiuri@Elettra.Eu

error_reporting(E_ERROR);

require( '../config.php' );
require_lib( 'cws', '1.0' );

define( 'CFG_VERSION', 1 );

$cfg =config( 'page_edots', true );

if (empty($cfg)) {
	echo json_encode(array( 'error' => true ));
	die;
}
	
$ret =array(
	'cfg' =>array(
		'version' =>CFG_VERSION
		)
	);
	
$dots =file_read_json( APP_EDOT, true );

$ts_rqst =$_GET['ts'];

if (empty($ts_rqst)) {
	
	if (APP_PAPER_STATUS_QRCODE) {
		$qrcode_img ='../html/qrcode_app_paper_status.png';

		if (!file_exists( $qrcode_img )) {
			require( '../libs/phpqrcode/qrlib.php' );
			$qrcode_content =APP_PAPER_STATUS_URL ? APP_PAPER_STATUS_URL : ROOT_URL .'/app_paper_status';
			QRcode::png( $qrcode_content, $qrcode_img, 'L', 4 );
			
			$png =imagecreatefrompng( $qrcode_img );
			$bg = imagecolorat( $png, 0, 0 );
			imagecolorset( $png, $bg, 85, 85, 85 );
			imagepng( $png, $qrcode_img );
		}
	} else {
		$qrcode_img =false;
	}
	
	
	$legend =false;
	foreach ($cfg['labels'] as $name =>$desc) {
		$legend[$name] =$desc;
	}	
	
	$n_dots =count( $dots );
	
	if (!APP_BOARD_COLS) {
		$cols =($n_dots > 500 ? 10 : 8);
		$rows =min( 22, ceil( $n_dots / $cols ));
	
	} else {
		$cols =APP_BOARD_COLS;
		$rows =APP_BOARD_ROWS;
	}
	
	$ret['cfg'] =array(
		'version' =>CFG_VERSION,
		'conf_name' =>CONF_NAME,
		'change_page_delay' =>APP_CHANGE_PAGE_DELAY,
		'reload_data_delay' =>APP_RELOAD_DATA_DELAY, // seconds	
		'cols' =>$cols,
		'rows' =>$rows,
		'legend' =>$legend,
		'qrcode' =>$qrcode_img,
		'dots' =>$n_dots,
		'pages' =>ceil( $n_dots /($cols * $rows))
		);	
}

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