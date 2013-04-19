<?php
// 2013.02.27 bY Stefano.Deiuri@Elettra.Trieste.It

header('Content-type: text/html; charset: UTF-8');

require( '../conference.php' );

?>
<html>

<head>
	<title><? echo CONF_NAME; ?> / Programme</title>
	<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
	<link rel='stylesheet' href='ScientificProgramme.css' type='text/css' />
	<script language='javascript' src='ScientificProgramme.js'></script>
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
$fname ="ScientificProgramme/day$day.html";

if (file_exists($fname)) {
	$page =implode( '', file( $fname ));

	echo str_replace( array('index.php'), array('ScientificProgramme.php'), $page );
}
else echo "<i>Wrong date</i>";

?>

</body>
</html>
