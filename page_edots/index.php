<?php

// 2019.04.30 bY Stefano.Deiuri@Elettra.Eu

error_reporting(E_ERROR);

require( '../config.php' );
require_lib( 'cws', '1.0' );

$cfg =config( 'page_edots', true );

$legend =false;
foreach ($cfg['labels'] as $name =>$desc) {
	$legend .="<td class='b_${name}'>${desc}</td>";
}

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

?>
<html>
<head>
	<meta http-equiv="refresh" content="1200">
	<title>Paper Processing Status</title>
	<script src='jquery-2.2.3.min.js'></script>
	<script src='scripts.js'></script>
	<link href="https://fonts.googleapis.com/css?family=Roboto+Mono" rel="stylesheet">	
	<link href="colors.css" type="text/css" rel="stylesheet" />
	<link href="style.css" type="text/css" rel="stylesheet" />
</head>

<body>
<div id='header'>
<div id='logo'><img src="logo.jpg" height='50px' alt="logo" border="0"></div>
<div id='title'>Paper Processing Status</div>
<div id='pages'><span id='activepage'>-</span><span id='npages' style='color:#bbb;'>/-</span></div>
<div id='timer'></div>
<div id='timer2'></div>
<div id='clock'></div>
</div>
<div id="edots">
<div class='page' page='1' style='display:block;'></div>
<div class='page' page='2'></div>
<div class='page' page='3'></div>
<div class='page' page='4'></div>
<div class='page' page='5'></div>
<div class='page' page='6'></div>
<div class='page' page='7'></div>
</div>
<img src='<?php echo $qrcode_img; ?>' id='qrcode' />
<table class='legend'><tr>
<? echo $legend; ?>
</tr></table>
</body>
</html>
