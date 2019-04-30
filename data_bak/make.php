#!/usr/bin/php
<?php

// 2018.04.28 bY Stefano.Deiuri@Elettra.Eu

require( '../config.php' );
require_lib( 'cws', '1.0' );

$cfg =config( 'data_bak' );

$tm =date('Ymd-Hi');
$bak =$tm .'.tgz';
$cmd ="tar zcvf $bak ../data/ 2>/dev/null";


echo "Backup data $tm... ";

exec( $cmd, $output, $status );

echo "OK ($status)\n";

?>
