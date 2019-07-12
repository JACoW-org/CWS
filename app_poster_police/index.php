<?php

// 2019.05.02 by Stefano.Deiuri@Elettra.Eu

require( '../config.php' );
require_lib( 'cws', '1.0' );

session_start();

//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
class PosterPolice extends CWS_OBJ {
 var $PP; // PosterPolicy
 var $PPS; // PosterPolicyStatus
 var $cfg;
 
 //-----------------------------------------------------------------------------
 function __construct() {
	$this->PP =false;
	$this->PPS =false;
		
	$this->day =_G('day');
	$this->session =_G('session');
	$this->poster =_G('poster');

	$this->cfg =array( 
		'pps_fname' =>false
		);
 }
 
 //-----------------------------------------------------------------------------
 public function config( $_cfg =false ) {
	parent::config( $_cfg );

	if ($_cfg && $this->cfg['dummy_mode']) $this->cfg['sync_url'] ='http://' .$_SERVER['SERVER_NAME'] .str_replace( 'index.php', 'dummy_sync.php', $_SERVER['PHP_SELF'] );
//	if ($_cfg && $this->cfg['dummy_mode']) $this->cfg['sync_url'] ='dummy_sync.php';
 }
 
 //-----------------------------------------------------------------------------
 function check_auth() {
	$auth_code =md5(SPMS_PASSPHRASE .APP_PASSWORD .'OkeY!');
	
	if ($_SESSION['auth'] == $auth_code) return true;
	
	if (isset($_POST['pass']) && $_POST['pass'] == APP_PASSWORD) {
		$_SESSION['auth'] =$auth_code;
		return true;
	}
	
	$this->cfg['dummy_mode'] =false;
	
	$this->draw_begin();
	echo "<!--\n$_SERVER[HTTP_USER_AGENT]\n-->
<div class='login'>
<form method='POST'>
<h1>" .CONF_NAME ." PosterPolice</h1>
<input type='password' name='pass' id='code' autocomplete='off' placeholder='PASSWORD' autofocus />
<br /><input type='submit' value='Login' />
</div>
</form>";
	$this->draw_end();
	die;
 }
 
 //-----------------------------------------------------------------------------
 function draw_begin() {
	$day =$this->day;
	$session =$this->session;
	$poster =$this->poster;

	$poster_vars =false;

	if ($poster) {
		$s =$this->get_status();
		array_pop( $s );
		$comment =$s ? array_pop( $s ) : '';
		$status =$s ? implode( ',', $s ) : '-1,-1,-1,-1';
	
		$poster_vars ="var poster ='$poster';\n"
			."\tvar poster_status =[ $status ];\n"
			."\tvar comment ='$comment';";
	}
	
	echo "
<html>
<head>
	<meta name='_viewport' content='width=device-width, initial-scale=1.0'>
	<meta name='mobile-web-app-capable' content='yes'>

	<title>" .CONF_NAME ." PosterPolice</title>

	<link href='https://fonts.googleapis.com/css?family=Lato:300' rel='stylesheet' type='text/css'>
	<link href='style.css' rel='stylesheet' type='text/css' />	
	
	<script src='jquery-2.2.3.min.js'></script>
	
	<script>
	var script ='$_SERVER[PHP_SELF]';
	var sync =false;
	var day ='$day';
	var session ='$session';
	$poster_vars
	</script>
	
	<script src='poster_police.js'></script>
</head>

<body>
<div id='sync_bkg' style='display:none'>
	<div id='sync_box'>
		<div id='sync_title'>Syncing...</div>
		<div id='sync_bar'>0%</div>
		<div id='sync_log'>...</div>
	</div>
</div>";

	if ($this->cfg['dummy_mode']) echo "<div class='warning2'>Dummy mode enabled!</div>\n";
 }
 
 
 //-----------------------------------------------------------------------------
 function draw_end() {
	echo "
</body>
</html>
";
 }

 
 //-----------------------------------------------------------------------------
 function handle() {
	if (_G('export')) {
		$this->export();
		return;
	}
 
	if (_R('cmd', 'session_sync')) {
		list( $tp, $tpc, $errors, $pcode, $status )=$this->session_sync();
		$percent =round( $tpc * 100 / $tp );

		echo '{ "tp": "' .$tp .'", "tpc": "' .$tpc .'", "errors": "' .$errors .'", "percent": "' .$percent .'", "pcode": "' .$this->session .$pcode .'", "status": "' .$status .'" }';
		return;
	}
 
	if (_G('save')) {
		$this->save_status( _G('status0'), _G('status1'), _G('status2'), _G('status3'), _G('comment') );
		if (_G('next')) {
			$this->poster =_G('next');
//			$this->poster =str_pad( ++$this->poster, 3, '0', STR_PAD_LEFT );
		} else {
			$this->poster =0;
		}
	}
 
	$this->draw_begin();
	$this->select_day();
	$this->select_session();
	$this->select_poster();
	$this->draw_end();
 }

 
 //-----------------------------------------------------------------------------
 function export() {
	$csv_fname =str_replace( '/data/', '/tmp/', substr( $this->cfg['pps_fname'], 0, -5 )) .'.csv';
	$fp =fopen( $csv_fname, 'w' );
	
	$record =array( 'Poster Code', 'Abstract ID', 'Manned', 'Posted', 'Satisfactory', 'Picture', 'Comments' );
	fputcsv( $fp, array_values($record) );

	foreach ($this->PPS as $scode =>$so) {
		foreach ($so as $pcode =>$s) {
			$record =array(  $scode.$pcode, $s[5], $s[0], $s[1], $s[2], $s[3], $s[4] );
			fputcsv( $fp, array_values($record) );
		}
	}
	
	fclose( $fp );
	
	download_file( $csv_fname, CONF_NAME .'-PosterPolice.csv', 'application/excel' );
 } 
 
 
 //-----------------------------------------------------------------------------
 function session_sync() {
	set_time_limit( 60 );

	$sync_file =substr( $this->cfg['pps_fname'], 0, -5 ) .'-sync.json';
	
	if (file_exists( $sync_file )) {
		$sync =file_read_json( $sync_file, true );
	} else {
		foreach ($this->PPS as $pcode =>$s) {
			$sync[$pcode] =array( 'data' =>$s, 'sync_ts' =>false, 'sync_status' =>false );
		}
	}
	
	$sync_pcode =false;
	foreach ($sync as $pcode =>$x) {
		if (!$x['sync_ts'] && !$sync_pcode) {
			$s =$x['data'];
			$url =$this->cfg['sync_url']
				."?chk=" .SPMS_PASSPHRASE
				."&pid=" .$this->cfg['pp_manager']
				."&aid=$s[5]"
				."&pp=" .($s[1] ? 'Y' : 'N')
				."&pm=" .($s[0] ? 'Y' : 'N')
				."&ps=" .($s[2] ? 'Y' : 'N')
				."&pt=" .($s[3] ? 'Y' : 'N')
				."&co=" .urlencode($s[4]);

			$sync_status =trim(reset(file( $url )));

			if ($sync_status == '') $sync_status ='WD!';
			
			$x['sync_ts'] =time();
			$x['sync_status'] =$sync_status;
			
			$sync[$pcode] =$x;
			
			$sync_pcode =$pcode;
		}
	}
	
	$n =0;
	$ns =0; // synched
	$ne =0; // errors
	foreach ($sync as $pcode =>$x) {
		if ($x['sync_ts']) $ns ++;
		if ($x['sync_status'] != 'OK') $ne ++;
		$n ++;
	}
	
	file_write_json( $sync_file, $sync );
	
	if ($ns == $n) rename( $sync_file, substr( $sync_file, 0, -5 ) .'-' .date('U') .'.json' );
	
	return array( $n, $ns, $ne, $sync_pcode, $sync_status );
 } 
 
 
 //-----------------------------------------------------------------------------
 function select_day() {
	if ($this->day) {
		$day2 =date('D, j M Y',strtotime($this->day));
		echo "<div class='day_selected' onClick='select_day(false)'>$day2</div>\n";
		return;
	}
 
	echo "<h1 class='maintitle'>" .CONF_NAME ." Poster Police</h1>\n";
	foreach ($this->PP as $day =>$do) {
		$day2 =date('D, j M Y',strtotime($day));
		$today =$day == date('d-M-y');
		echo "<div class='day" .($today ? ' today' : false) ."' onClick='select_day(\"$day\")'>$day2</div>\n";
	}
 }
 
 
 //-----------------------------------------------------------------------------
 function session_stats( $_code =false ) {
	$sess =$_code ? $_code : $this->session;
 	$tp =count( $this->PP[$this->day][$sess]['posters'] );
	
	if ($_code) {
		$this->cfg['pps_fname'] =APP_DATA_PATH .'/' .$_code .'.json';
		if (file_exists( $this->cfg['pps_fname'] )) {
			$this->PPS =file_read_json( $this->cfg['pps_fname'], true );
		} else {
			return array( $tp, 0, 0 );
		}
	}
	
	$tpc =count( $this->PPS );
	$percent =round( $tpc * 100 / $tp );
	
	$sync_file =substr( $this->cfg['pps_fname'], 0, -5 ) .'-sync.json';
	$sync_pending =file_exists($sync_file);

	$last_sync =false;
	if (!$sync_pending) {
		$ls =exec( "ls -1tr " .APP_DATA_PATH .'/' .$_code .'-*.json'."" );
		if ($ls) {
			$ts =substr( $ls, strrpos( $ls, '-' ) +1, -5 );
			$last_sync =date( 'j M Y H:i', $ts );
		}
	}
	
	return array( $tp, $tpc, $percent, $last_sync, $sync_pending );
 }
 
 
 //-----------------------------------------------------------------------------
 function select_session() {
	if ($this->session) {
		list( $tp, $tpc, $percent ) =$this->session_stats();
		
		$style ="background-image: url(1px.png); background-repeat: no-repeat; background-size: $percent% 100%;";
		echo "<div class='session_selected' onClick='select_session(false)' style='$style'>" .$this->PP[$this->day][$this->session]['location'] .' (' .$this->session  .')'
			."<span style='float: right'>$tpc / $tp</span></div>\n"
			."</div>\n";

		return;
	}

	if (!isset($this->PP[$this->day])) return;
	
	foreach ($this->PP[$this->day] as $code =>$co) {
		list( $tp, $tpc, $percent, $last_sync, $sync_pending ) =$this->session_stats( $code );
		
		$sync_button =($sync_pending ? 'Finish sync' : 'Sync')
			.($last_sync ? "<div class='last_sync'>last sync: $last_sync</div>" : false);

		$style ="background-image: url(1px.png); background-repeat: no-repeat; background-size: $percent% 100%;";
		echo "<div class='session' onClick='select_session(\"$code\")' style='$style'>$co[location] ($code)" 
			.($percent >= 100 ? "<div class='syncbutton' onClick='session_sync(\"$code\"); event.stopPropagation();'>$sync_button</div>" : false) 
			."</div>\n";
	}
 } 
 
 
 //-----------------------------------------------------------------------------
 function get_status( $_code =false ) {
	$pc =($_code ? $_code : $this->poster); // PosterCode
	if (!isset( $this->PPS[$pc])) return false;
	return $this->PPS[$pc];
 }
 
 
 //-----------------------------------------------------------------------------
 function select_poster() {
	$posters =&$this->PP[$this->day][$this->session]['posters'];

	if ($this->poster) {
		$p =$posters[$this->poster];
		
		$s =$this->get_status();
		if ($s) {
			$class0  =$s[0] ? 'On' : 'Off';
			$class1  =$s[1] ? 'On' : 'Off';
			$class2  =$s[2] ? 'On' : 'Off';
			$class3  =$s[3] ? 'PhotoYes' : 'PhotoNo';
			$class_comment =$s[4] ? 'comment_set' : 'comment';
			$comment =$s[4] ? $s[4] : 'Comments';
			
		} else {
			$class0 =$class1 =$class2 =$class3 ='Switch';
			$class_comment ='comment';
			$comment ='Comments';
		}
		
		$next =true;
		
		$posterslist =array_keys( $posters );
		while (current($posterslist) != $this->poster && $next) $next =next($posterslist);
		$next =next($posterslist);
		
				
		echo "<div class='poster_selected'>
		<h1>" .$this->poster ."</h1>
		<h1>$p[title]</h1>
		<h2>$p[presenter]</h2>
		<center>
		<div class='$class1' id='status1' onClick='change_poster_status(1)'>Posted</div>
		<div class='$class0' id='status0' onClick='change_poster_status(0)'>Manned</div>
		<div class='$class2' id='status2' onClick='change_poster_status(2)'>Satisfactory</div>
		<div class='$class3' id='status3' onClick='change_poster_status(3)'>Picture</div>
		<br />
		<div class='$class_comment' id='comment' onClick='poster_comment()'>$comment</div>
		<br />
		<div class='button' onClick='poster_close()'>Close</div>
		<div class='button' onClick='poster_save()'>Save</div>"
		.($next ? "<div class='button' onClick='poster_save(\"$next\")'>Save & Next</div>". "<div class='button' onClick='poster_next(\"$next\")'>Skip</div>" : false)
		."</center>
		</div>\n";
		return;
	}
	
	if (!isset($posters)) return;
	
	foreach ($posters as $code =>$po) {
		$s =$this->get_status( $code );
		
		if (!$s) $xclass =false;
		else if (!$s[0] && $s[1] && $s[2]) $xclass =' unmanned';
		else $xclass =$s[0] && $s[1] && $s[2] ? ' ok' : ' warning';

		echo "<div class='poster${xclass}' onClick='select_poster(\"$code\")'>$code</div>\n";
	}
 }
 
 //-----------------------------------------------------------------------------
 function save() {
	file_write_json( APP_PP, $this->PP );
 }
 
 
 //-----------------------------------------------------------------------------
 function save_status( $_s0, $_s1, $_s2, $_s3, $_comment ) {
	$aid =$this->PP[$this->day][$this->session]['posters'][$this->poster]['abstract_id'];
	$this->PPS[$this->poster] =array( $_s0, $_s1, $_s2, $_s3, $_comment, $aid );
 
	file_write_json( $this->cfg['pps_fname'], $this->PPS );
 }
 
 //-----------------------------------------------------------------------------
 function load() {
	$this->PP =file_read_json( APP_PP, true );

	$pps_fname =($this->session ? APP_DATA_PATH .'/' .$this->session .'.json' : false);
	$this->cfg['pps_fname'] =$pps_fname;
	
	if (file_exists( $pps_fname )) {
		$this->PPS =file_read_json( $pps_fname, true );
		
	} else {
		$this->PPS =array();
	}
 }
}


$cfg =config( 'app_poster_police' );

$APP =new PosterPolice();
$APP->config( $cfg );

if (!need_file( APP_PP )) {
	echo_error( "\n\nTry to run spms_importer/make.php!" );
	die;
}

if (!APP_PASSWORD) {
	echo_error( "\n\nSet the login password in the config file!" );
	die;
}

if (!APP_PP_MANAGER) {
	echo_error( "\n\nSet the PosterPolice PersonID (pp_manager) in the config file!" );
	die;
}

$APP->check_auth();

$APP->load();

$APP->handle();

?>