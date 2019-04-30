#!/usr/bin/php
<?php

// 2016.05.17 bY Stefano.Deiuri@Elettra.Eu

require( 'config.php' );

if (ROOT_PATH == '.' || ROOT_PATH == '') {
	echo "\n\nWrong configuration! Please check config.php!\n\n\n";
	die;
}

$force =($argv[1] == '--force');

if ($force) echo "force = $force\n\n";

if (!$cws_config['global']['cron_enabled'] && $force == false) {
	echo "\n\nCron disabled!\n\nCheck \$cws_config[global][cron_enabled] in config.php\n\n";
	return;
}

foreach ($cws_config as $app =>$config) {
	$run =$force;
	
	if (isset($config['cron'])) {
		list( $h, $m ) =explode( ':', $config['cron'] );
		
		if ($h == '*' && $m == '*') $run =true;
		else if ($h == '*' && $m == date('i')) $run =true;
		else if ($h == date('H') && $m == date('i')) $run =true;
				
		if ($run) {
			echo "\n" .date('r') ." ------------------------------------------------------------------------------\n";
			echo "Run $app/make.php at $h:$m\n";
			system( "cd " .ROOT_PATH ."/$app; ./make.php" );
			echo "\n";
		}
	}
}

?>
