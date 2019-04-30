<?php

// 2018.05.11 bY Stefano.Deiuri@Elettra.Eu

error_reporting(E_ERROR);

require( '../config.php' );
require_lib( 'cws', '1.0' );

if (!$cfg =config( 'page_po_status', true )) {
	echo json_encode(array( 'error' => true ));
	die;
}

$stats_db =file_read_json( APP_STATS, true );

$ts_start =strtotime( DATE_START .' 8:00' ) +APP_DIFF_TZ *3600;
$ts_end =strtotime( DATE_END .' 23:00' ) +APP_DIFF_TZ *3600;

$ts_data_start =$ts_start -(5 *86400);

$history =false;
$x0 =false;
foreach ($stats_db as $tm2 =>$x) {
	$t =explode( '-', $tm2 );
		
	$tts =mktime( $t[3], 0, 0, $t[1], $t[2], $t[0] );
	$tts +=APP_DIFF_TZ *3600;

	if ($tts >= $ts_data_start && $tts <= ($ts_end +86400)) {
		$tm2 =date( "Y-m-d-H", $tts );
	
		if (date( "H", $tts ) == '00' && $x0 == false) {
			$x0 =$x;
		
		} else if (date( "H", $tts ) == '23') {
			$history[date( 'd M', $tts )] =array(
				'processed' =>$x['processed'] -$x0['processed'],
				'qaok' =>$x['qaok'] -$x0['qaok'],
				'dow' =>date( 'D', $tts ),
				'dte' =>ceil(($tts -$ts_end) / 86400) -1
				);
				
			$x0 =$x;
		}
	}
}	


$rows =false;
$rows['date'][] ='date';
$rows['date_dow'][] ='';
$rows['dte'][] ='day to end';
$rows['processed'][] ='processed';
$rows['qaok'][] ='qaok';

foreach ($history as $date =>$x) {
	if ($x['dte'] == '-0') $x['dte'] =0;
	
	$rows['date'][] =$date;
	$rows['date_dow'][] =$x['dow'];
	$rows['dte'][] =$x['dte'];
	$rows['processed'][] =$x['processed'];
	$rows['qaok'][] =$x['qaok'];
}

$fp =fopen( '../data/' .CONF_NAME .'-daily_statistics.csv', 'w' );
fwrite( $fp, CONF_NAME ." Daily Statistics\n" );

foreach ($rows as $name =>$x) {
	fputcsv( $fp, $x );
}
fclose( $fp );

print_r( $rows );

?>
