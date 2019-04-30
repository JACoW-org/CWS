<?

require( 'config.php' );

if (ROOT_PATH == '.' || ROOT_PATH == '') {
	echo "Wrong configuration! Please check config.php!";
	die;
}

$content =false;
foreach ($cws_config as $app =>$x) {
	if (isset($x['out_html'])) {
		$href ="html/$x[out_html]";
		if (file_exists($href)) $content .="<li><a href='$href' target='_blank'>$x[name]</a></li>\n";
		else $content .="<li>$x[name]" .(substr($app,0,4) == 'make' ? "<br /><small>(run $app/make.php)</small>" : false) ."</li>\n";
		
	} else if (isset($x['default_page'])) {
		$href =str_replace( '{app}', $app, $x['default_page'] );
		if (file_exists($href)) $content .="<li><a href='$href' target='_blank'>$x[name]</a></li>\n";
		else $content .="<li>$x[name]" .(substr($app,0,4) == 'make' ? "<br /><small>(run $app/make.php)</small>" : false) ."</li>\n";
	}
}

$logo =file_exists( 'logo.jpg' ) ? "<img src='logo.jpg' border='0' align='absmiddle' />" : $cws_config['global']['conf_name'];

?>
<html>
<head>
	<title>CWS</title>
	<link href='https://fonts.googleapis.com/css?family=Lato:400,300' rel='stylesheet' type='text/css'>
	<style>
	body {
		background: #fff;
		margin: 10px;
		font-family: 'Lato', Arial;
		font-size: 20px;
		font-weight: 300;
		}
		
	h1, li {
		margin-bottom: 20px;
		}
	</style>
</head>

<body>
<h1>JACoW Conference Website Scripts for <a href='<? echo $cws_config['global']['conf_url']; ?>' target='_blank'><? echo $logo; ?> </a></h1>
<ul>
<?
echo $content;
?>
</ul>
</body>
</html>
