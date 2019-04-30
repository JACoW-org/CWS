<?php
// 2018.04.12 bY Stefano.Deiuri@Elettra.Trieste.It

header('Content-type: text/html; charset: UTF-8');

require( '../config.php' );

?>
<html>

<head>
	<title><? echo $cws_config['global']['conf_name']; ?> / Programme</title>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
	<link rel='stylesheet' href='programme.css' type='text/css' />
	<link rel='stylesheet' href='programme-rooms.css' type='text/css' />
	<script language='javascript' src='programme.js'></script>
	<script language='javascript' src='prototype.js'></script>
	<style>
	body, td, th {
		font-family: Arial;
		font-size: 12px;
	}
	</style>
</head>

<body>


<?php 

$day =isset($_GET['day']) ? $_GET['day'] : '1';
$fname ="programme/day$day.html";

if (file_exists($fname)) {
	$page =implode( '', file( $fname ));

	echo str_replace( array('index.php'), array('programme.php'), $page );
}
else echo "<i>Wrong date</i>";

?>

</body>
</html>
