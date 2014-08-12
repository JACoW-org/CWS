<?php

// 2014.08.12 by Stefano.Deiuri@Elettra.Eu

require( '../conference.php' );
require( '../libs/jacow-1.0.lib.php' );
require( '../libs/spms_programme-1.5.class.php' );

$PPOBJ =new SPMS_PosterPolice;
$PPOBJ->load();

$PPOBJ->handle();



//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
class SPMS_PosterPolice {
 var $PP; // PosterPolicy
 var $PPS; // PosterPolicyStatus
 var $cfg;
 
 //-----------------------------------------------------------------------------
 function SPMS_PosterPolice() {
	$this->PP =false;
	$this->PPS =false;
	
	$this->day =0;
	$this->session =0;
	$this->poster =0;	
	
	$this->cfg['pp_fname'] =DATA_PATH .'/poster_policy/spms-pp.json';
	$this->cfg['pps_fname'] =DATA_PATH .'/poster_policy/spms-pps.json';
	$this->cfg['programme_fname'] =DATA_PATH .'/scientific_programme/spms-programme.json';
 }

 
 //-----------------------------------------------------------------------------
 function draw_begin() {
	$day =$this->day;
	$session =$this->session;
	$poster =$this->poster;
	
	$s =$this->get_status();
	array_pop( $s );
	$comment =$s ? array_pop( $s ) : '';
	$status =$s ? implode( ',', $s ) : '-1,-1,-1,-1';
	
	echo "
<html>
<head>
	<title>" .CONF_NAME ." PosterPolicy</title>
	<link rel='stylesheet' href='style.css' type='text/css' />	
	
	<script>
	var poster_status =[ $status ];
	var comment ='$comment';
	var sync =false;
	
	function select_day( _day ) {
		document.location ='$_SERVER[PHP_SELF]' +(_day ? '?day=' +_day : '');
	}
	
	function select_session( _session ) {
		if (sync) return;
		document.location ='$_SERVER[PHP_SELF]?day=$day' +(_session ? '&session=' +_session : '');
	}
	
	function session_sync( _session ) {
		sync =true;
		document.location ='$_SERVER[PHP_SELF]?cmd=session_sync&day=$day&session=' +_session;
	}
	
	function select_poster( _poster ) {
		document.location ='$_SERVER[PHP_SELF]?day=$day&session=$session&poster=' +_poster;
	}
	
	function change_poster_status( _id ) {
		s =poster_status[ _id ];
		obj =document.getElementById( 'status' +_id );
		
		if (s != 1) {
			poster_status[ _id ] =1;
			obj.className ='On';
			
		} else {
			poster_status[ _id ] =0;
			obj.className ='Off';
		}
	}
	
	function poster_comment() {
		comment =window.prompt( 'Comments', (comment ? comment : '') );
		if (comment) {
			obj =document.getElementById( 'comment' );
			obj.innerHTML =comment;
			obj.className ='comment_set';
		}
	}
	
	function poster_save( _next ) {
		if (poster_status[0] == -1 || poster_status[1] == -1 || poster_status[2] == -1 || poster_status[3] == -1) {
			alert( 'Please set all flags!' );
			return;
		}

		document.location ='$_SERVER[PHP_SELF]?day=$day&session=$session&poster=$poster&save=1'
			+'&status0=' +poster_status[0] 
			+'&status1=' +poster_status[1] 
			+'&status2=' +poster_status[2] 
			+'&status3=' +poster_status[3] 
			+(_next ? '&next=1' : '')
			+(comment ? '&comment=' +encodeURIComponent(comment).replace(/[!'()]/g, escape).replace(/\*/g, \"%2A\") : '')
			;
	}
	
	function poster_next( _next ) {
		document.location ='$_SERVER[PHP_SELF]?day=$day&session=$session&poster=' +_next;
	}

	function poster_close() {
		document.location ='$_SERVER[PHP_SELF]?day=$day&session=$session';
	}
	</script>
</head>
<body>
	";
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
	if ($_GET['export']) {
		$this->export();
		return;
	}
 
	if ($_GET['day']) $this->day =$_GET['day'];
	if ($_GET['session']) $this->session =$_GET['session'];
	if ($_GET['poster']) $this->poster =$_GET['poster'];
	
	if ($_GET['cmd'] == 'session_sync') {
		$this->draw_begin();
		$this->select_day();
		$this->session_sync();
		$this->draw_end();
		return;
	}
 
	if ($_GET['save']) {
		$this->save_status( $_GET['status0'], $_GET['status1'], $_GET['status2'], $_GET['status3'], $_GET['comment'] );
		if ($_GET['next']) {
			$this->poster =str_pad( ++$this->poster, 3, '0', STR_PAD_LEFT );
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
	set_time_limit( 600 );

	echo "<div class='session_selected' onClick='select_session(false)'>" .$this->session ."</div>\n";
	
	foreach ($this->PPS[$this->session] as $pcode =>$s) {
		$url =SPMS_URL ."/xtract.posterpolicesetstatus?"
			."chk=" .PASSPHRASE
			."&pid=" .PP_PID
			."&aid=$s[5]"
			."&pp=" .($s[1] ? 'Y' : 'N')
			."&pm=" .($s[0] ? 'Y' : 'N')
			."&ps=" .($s[2] ? 'Y' : 'N')
			."&pt=" .($s[3] ? 'Y' : 'N')
			."&co=" .urlencode($s[4]);

		$fileresult =file( $url );
		$ok =(trim($fileresult[0]) == 'OK' ? 'ok' : 'error');
//		echo "$pcode... " .($ok[0] == 'OK' ? 'OK' : 'Error') ."<br />";

		echo "<div class='sync_$ok'>$pcode</div>\n";
	}

//	print_r( $fileresult );

	echo "<center><h1 class='maintitle'>Sync completed!</h1></center>\n";
		
 } 
 
 
 
 //-----------------------------------------------------------------------------
 function select_day() {
	if ($this->day) {
		echo "<div class='day_selected' onClick='select_day(false)'>" .$this->day ."</div>\n";
		return;
	}
 
	echo "<h1 class='maintitle'>" .CONF_NAME ." Poster Police</h1>\n";
	foreach ($this->PP as $day =>$do) {
		echo "<div class='day' onClick='select_day(\"$day\")'>$day</div>\n";
	}
 }

 
 //-----------------------------------------------------------------------------
 function session_stats( $_code =false ) {
	$sess =$_code ? $_code : $this->session;
 
	$tp =count( $this->PP[$this->day][$sess]['posters'] );
	$tpc =count( $this->PPS[$sess] );
	$percent =round( $tpc * 100 / $tp );
	
	return array( $tp, $tpc, $percent );
 }
 
 
 //-----------------------------------------------------------------------------
 function select_session() {
	if ($this->session) {
		list( $tp, $tpc, $percent ) =$this->session_stats();
		
		echo "<div class='session_selected' onClick='select_session(false)'>" .$this->PP[$this->day][$this->session]['location'] .' (' .$this->session  .')'
			."<span style='float: right'>$tpc / $tp</span></div>\n"
			."<div class='stats' style='width: $percent%'></div>\n";

		return;
	}

	foreach ($this->PP[$this->day] as $code =>$co) {
		list( $tp, $tpc, $percent ) =$this->session_stats( $code );
		echo "<div class='session' onClick='select_session(\"$code\")'>$co[location] ($code)" 
			.($percent >= 100 ? "<div class='syncbutton' onClick='session_sync(\"$code\")'>Sync</div>" : false) 
			."</div>\n"
			."<div class='stats' style='width: $percent%'></div>\n";
	}
 } 

 
 //-----------------------------------------------------------------------------
 function get_status( $_code =false ) {
	$pc =($_code ? $_code : $this->poster); // PosterCode
	if (!isset( $this->PPS[$this->session][$pc])) return false;
	return $this->PPS[$this->session][$pc];
 }

 
 //-----------------------------------------------------------------------------
 function select_poster() {
	if ($this->poster) {
		$p =$this->PP[$this->day][$this->session]['posters'][$this->poster];
		
		$s =$this->get_status();
		if ($s) {
			$class0  =$s[0] ? 'On' : 'Off';
			$class1  =$s[1] ? 'On' : 'Off';
			$class2  =$s[2] ? 'On' : 'Off';
			$class3  =$s[3] ? 'On' : 'Off';
			$class_comment =$s[4] ? 'comment_set' : 'comment';
			$comment =$s[4] ? $s[4] : 'Comments';
		} else {
			$class0 =$class1 =$class2 =$class3 ='Switch';
			$class_comment ='comment';
			$comment ='Comments';
		}
		
		$next =str_pad( $this->poster +1, 3, '0', STR_PAD_LEFT );
		$last =!isset($this->PP[$this->day][$this->session]['posters'][$next]);
				
		echo "<div class='poster_selected'>
		<h1>" .$this->poster ."</h1>
		<h1>$p[title]</h1>
		<h2>$p[presenter]</h2>
		<center>
		<div class='$class0' id='status0' onClick='change_poster_status(0)'>Manned</div>
		<div class='$class1' id='status1' onClick='change_poster_status(1)'>Posted</div>
		<div class='$class2' id='status2' onClick='change_poster_status(2)'>Satisfactory</div>
		<div class='$class3' id='status3' onClick='change_poster_status(3)'>Picture</div>
		<br />
		<div class='$class_comment' id='comment' onClick='poster_comment()'>$comment</div>
		<br />
		<div class='button' onClick='poster_close()'>Close</div>
		<div class='button' onClick='poster_save()'>Save</div>"
		.(!$last ? "<div class='button' onClick='poster_save(1)'>Save & Next</div>". "<div class='button' onClick='poster_next(\"$next\")'>Skip</div>" : false)
		."</center>
		</div>\n";
		return;
	}
	
	foreach ($this->PP[$this->day][$this->session]['posters'] as $code =>$po) {
		$s =$this->get_status( $code );
		
		if (!$s) $xclass =false;
		else if (!$s[0] && $s[1] && $s[2] && $s[3]) $xclass =' unmanned';
		else $xclass =$s[0] && $s[1] && $s[2] && $s[3] ? ' ok' : ' warning';

		echo "<div class='poster${xclass}' onClick='select_poster(\"$code\")'>$code</div>\n";
	}
 }
 
 
 //-----------------------------------------------------------------------------
 function config( $_var =false, $_val =false ) {
	if ($_var == false) {
		print_r( $this->cfg );
		return;
	}
 
	$this->cfg[$_var] =$_val;
 }
 

 //-----------------------------------------------------------------------------
 function save() {
	file_write_json( $this->cfg['pp_fname'], $this->PP );
 }

 
 //-----------------------------------------------------------------------------
 function save_status( $_s0, $_s1, $_s2, $_s3, $_comment ) {

	$aid =$this->PP[$this->day][$this->session]['posters'][$this->poster]['abstract_id'];
	$this->PPS[$this->session][$this->poster] =array( $_s0, $_s1, $_s2, $_s3, $_comment, $aid );
 
	file_write_json( $this->cfg['pps_fname'], $this->PPS );
 }
 
 
 //-----------------------------------------------------------------------------
 function load() {
	$import =true;
 
	if (file_exists( $this->cfg['pp_fname'] )) {
		$this->PP =file_read_json( $this->cfg['pp_fname'], true );
		$import =false;
	}
	
	if (file_exists( $this->cfg['pps_fname'] )) {
		$this->PPS =file_read_json( $this->cfg['pps_fname'], true );
	}

	if (!$import) return;
 
	if (!isset($_GET['importxml'])) {
		echo "<pre>\n\nThe first execution of this script could take a long time, please wait..\n\n<a href='index.php?importxml=1'>Continue</a></pre>";
		die;
	}
  
	$PRG =new SPMS_Programme;
	if (!file_exists( TMP_PATH .'/scientific_programme/spms_summary.xml' )) {
		echo "<pre>\nImport data from SPMS... ";
		$PRG->load_xml( SPMS_URL, false );
		$PRG->save();
		echo "OK\n\n<a href='index.php'>Continue</a></pre>";
		
		die;
	} else {
		$PRG->load( true, false );
	}

	foreach ($PRG->programme->days as $day =>$odss) { // ObjDaySessions
		foreach ($odss as $id =>$os) { // ObjSession
			if (strpos( $os->type, 'oster' ) !== false) {
				$sid =$os->code;
				
				$this->PP[$day][$sid] =array( 
					'code' =>$sid,
					'type' =>$os->type,
					'title' =>$os->title,
					'location' =>$os->location
					);			
					
				foreach ($os->papers as $pid =>$op) { // ObjPoster					
					$pn =substr( $pid, -3 );
					$this->PP[$day][$sid]['posters'][$pn] =array(
						'code' =>$pid,
						'title' =>$op->title,
						'presenter' =>$op->presenter,
						'abstract_id' =>$op->abstract_id
						);
				}
			}
		}
	}
	
	$this->save();
 }
}

?>
