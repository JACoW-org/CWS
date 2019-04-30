#!/usr/bin/php
<?php

// 2018.04.13 bY Stefano.Deiuri@Elettra.Eu

require( '../config.php' );
require_lib( 'cws', '1.0' );

$css =false;
$dark_threshold =160;

foreach ($cws_config['global'] as $var =>$color) {
	if (substr( $var, 0, 6 ) == 'color_') {
		$color_name =substr( $var, 6 );
		
		$r =hexdec(substr( $color, 1, 2 ));
		$g =hexdec(substr( $color, 3, 2 ));
		$b =hexdec(substr( $color, 4, 2 ));
		
		$text_color =($r < $dark_threshold && $g < $dark_threshold && $b < $dark_threshold) ? 'white' : 'black';
		
		$css .="
/* ($r,$g,$b) */
.$color_name {
	color: $color; 
}

.b_$color_name {
	background-color: $color;
	color: $text_color;
}

		";			
	}
}

foreach ($cws_config as $app =>$config) {
	if (isset($config['colors_css']) && $config['colors_css']) {
		$fname ="../$app/colors.css";
		echo "Write $fname... ";
		echo_result( file_write( $fname, $css ) );
	}
}

?>