<?php

// 2018.04.13 bY Stefano.Deiuri@Elettra.Eu

error_reporting(E_ERROR);

require( '../config.php' );
require_lib( 'cws', '1.0' );

$cfg =config( 'page_edots', true );

$legend =false;
foreach ($cfg['labels'] as $name =>$desc) {
	$legend .="<td class='b_${name}'>${desc}</td>";
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
<img src='qrcode_paper_status.png' id='qrcode' />
<table class='legend'><tr>
<? echo $legend; ?>
</tr></table>
</body>
</html>
