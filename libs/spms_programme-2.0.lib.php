<?php

// 2018.04.12 by Stefano.Deiuri@Elettra.Eu & R.Mueller@gsi.de

//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
class SPMS_Programme extends CWS_OBJ {
 var $abstracts;
 var $classes;
 var $programme;

 //-----------------------------------------------------------------------------
 function __construct() {
 	$this->abstracts =array();
 	$this->classes =array();
 	$this->programme =array();
	$this->programme['classes'] =array();	
	$this->programme['rooms'] =array();	
 }
 
 //-----------------------------------------------------------------------------
 function prepare() {
	if (!file_exists( OUT_PATH .'/programme.php' )) {
		$this->verbose( "Copy default files", 1 );
		exec( "cp -r files/* " .OUT_PATH );
	}
 }
 
 //-----------------------------------------------------------------------------
 function cleanup() {
	system( 'rm -f ' .APP_OUT_PATH .'/*.html' );
 } 
 
 //-----------------------------------------------------------------------------
 function load( $_programme =true, $_abstracts =true ) {
  	if ($_abstracts) {
		$this->abstracts =file_read_json( APP_ABSTRACTS, true );
	}
	
 	if ($_programme) {
		$this->programme =file_read_json( APP_PROGRAMME, true );
	}
 }
 
 //-----------------------------------------------------------------------------
 function make_rooms_css() {
//	$colors =array( '#55efc4', '#81ecec', '#74b9ff', '#a29bfe', '#ffeaa7', '#fab1a0', '#ff7675', '#fd79a8', '#b2bec3', '#636e72' );
//	$colors =array( '#0074D9','#7FDBFF','#39CCCC','#3D9970','#2ECC40','#01FF70','#FFDC00','#FF851B','#FF4136','#85144b','#F012BE','#B10DC9','#AAAAAA','#DDDDDD' );
	$colors =array( '#7FDBFF','#39CCCC','#3D9970','#2ECC40','#01FF70','#FFDC00','#FF851B','#FF4136','#85144b','#F012BE','#B10DC9','#AAAAAA','#DDDDDD' );
	 
	arsort( $this->programme['rooms'] );
	print_r( $this->programme['rooms'] );
	 
	 
	$c =0;
	$css =false;
	foreach ($this->programme['rooms'] as $room =>$n) {
		if ($n) $css .=".room$room { background: " .$colors[$c] ."; }\n";
		$c ++;
	}
	
	$this->verbose( "\n# Write programme-rooms.css", 1 );
	file_write( OUT_PATH .'/programme-rooms.css', $css );
 }
	 
	 
 //-----------------------------------------------------------------------------
 function make_abstracts() {
	$this->verbose( "\n# Save " .count($this->abstracts) ." abstracts... ", 1, false );

	foreach ($this->abstracts as $aid =>$A) {
		if (strlen($A['text']) > 10) {
			$this->verbose( "$aid ", 3, false );
			$fpa =fopen( APP_OUT_PATH ."/abstract.$aid.html", 'w' );
			
			$page =$A['text'] ."\n"
				.($A['footnote'] ? "<hr noshade size='1' width='60%' /><small>" .str_replace( '**', '<br />**', $A['footnote'] ) ."</small>\n" : false)
				.($A['agency'] ? "<hr noshade size='1' width='60%' /><small>$A[agency]</small>\n" : false)
				;			
			
			fwrite( $fpa, $page );
			fclose( $fpa );
		}
	}
	
	$this->verbose_ok();
 }

 //-----------------------------------------------------------------------------
 function make_ics() {
//http://www.elettra.trieste.it/events/2012/ipac/programme/programme.ics

	$fp =fopen( APP_ICS, 'w' );

//	$timezone =";TZID=CDT";
	$timezone =false;
	$tz =false;
	
	fwrite( $fp, "BEGIN:VCALENDAR
METHOD:PUBLISH
X-WR-CALDESC:IPAC'12 Scientific Programme
X-WR-CALNAME:IPAC'12 Scientific Programme
PRODID:-//Stefano Deiuri/SPMS Programme 1.0//EN
VERSION:2.0
" );

/*
BEGIN:VTIMEZONE
TZID:CDT
BEGIN:DAYLIGHT
TZNAME:CDT
TZOFFSETFROM:-0600
TZOFFSETTO:-0500
END:DAYLIGHT
END:VTIMEZONE
" );
*/
	
//	$time_created =date('Ymd') .'T' .date('His') .$tz;
//	$time_updated =date('Ymd') .'T' .date('Hi') .'00' .$tz;
	$time_created =vevent_date( time() );
	$time_updated =$time_created;


	foreach ($this->programme['days'] as $day =>$sessions) {
		if (!$sessions) break;

		echo "\nDay $day ";

		ksort( $sessions );
		
//		$d =date_parse( $day );
//		$day2 =$d['year'] .str_pad( $d['month'], 2, '0', STR_PAD_LEFT ) .str_pad( $d['day'], 2, '0', STR_PAD_LEFT );

		foreach ($sessions as $id =>$S) {

			if (strpos($S['type'], 'poster') !== false) {
				$uid ='postersession-'.md5( print_r($S,true) );
				echo 'P';

//				$time_start ="${day2}T" .str_replace( ':', '', $S['time_from'] ) .'00' .$tz;
//				$time_end ="${day2}T" .str_replace( ':', '', $S['time_to'] ) .'00' .$tz;
				$time_start =vevent_date($S['tsz_from']);
				$time_end =vevent_date($S['tsz_to']);

				$event ="DTSTAMP:$time_created
LAST-MODIFIED:$time_created
UID:$uid@ipac12.org
DTSTART$timezone:$time_start
DTEND$timezone:$time_end
STATUS:CONFIRMED
SUMMARY:$S[code] Poster session
LOCATION:$S[location]
TRANSP:OPAQUE
DESCRIPTION:Poster session
";
				fwrite( $fp, str_replace( ',', '\,', "BEGIN:VEVENT\n" .$event ."END:VEVENT\n" ));

			} else {
				if (is_array($S['papers'])) {
					foreach ($S['papers'] as $pid =>$P) {
						if (strpos(strtolower($S['title']), 'poster') === false) {
							$uid ='event-' .md5( print_r($P,true) );
							echo '+';
					
//							print_r( $P );
					
							$timeto =$P['tsz_to'] ? $P['tsz_to'] : $S['tsz_to'];
							$time_start =vevent_date($P['tsz_from']);
							$time_end =vevent_date($timeto);
//							$time_start ="${day2}T" .str_replace( ':', '', $P['time_from'] ) .'00' .$tz;
//							$time_end ="${day2}T" .str_replace( ':', '', $timeto ) .'00' .$tz;
					
							if ($time_start != $time_end) $event ="DTSTAMP:$time_created
LAST-MODIFIED:$time_created
UID:$uid@ipac12.org
DTSTART$timezone:$time_start
DTEND$timezone:$time_end
STATUS:CONFIRMED
SUMMARY:$pid $P[title] ($P[author])
LOCATION:$S[location]
TRANSP:OPAQUE
DESCRIPTION:Session: $S[title]
";
							fwrite( $fp, str_replace( ',', '\,', "BEGIN:VEVENT\n" .$event ."END:VEVENT\n" ));
							
						} else {
							echo '-';
						}
					}
				}
			}
		}

	}

	fwrite( $fp, "END:VCALENDAR\n" );
	fclose( $fp );
	
	echo "\n\nWrite ics file.\n\n";
 }
 //-----------------------------------------------------------------------------
 function make() {
	$this->verbose();
	$this->verbose( "# Save day pages" );

	$SHTML =array(); // Sessions HTML
 
	if (!$this->classes) {
		$this->classes =$this->programme['classes'];
	}
	
	$days =array_keys($this->programme['days']);
 
	$dayn =0;
	foreach ($this->programme['days'] as $day =>$sessions) {
		$dayn ++;

		if (!$sessions) break;

		$this->verbose( "## day $dayn page ($day)", 2 );

		$fp_day =fopen( APP_OUT_PATH ."/day$dayn.html", 'w' );

		$menu =false;

		$dow =array( 'Fri', 'Thu', 'Wed', 'Tue', 'Mon' );
		$daynt =0;
		foreach ($days as $d) {
			$daynt ++;
			$d2 =date( 'D, d M', strtotime( $d ));

			$sel =$day == $d;
			
			$menu .="<td class='m" .($sel ? 's' : false) ."'>"
				.($sel ? $d2 : "<a href='" .APP_BASE_URL .(strpos( APP_BASE_URL, '?' ) ? '&' : '?') ."day=$daynt'>$d2</a>")
				."</td>";
		}

		fwrite( $fp_day, "<table class='day' " .APP_TAB_W ." cellpadding='3' cellspacing='0'>"
			."<tr class='days'>$menu</tr>"
			."</table>\n"
			);
		
		
		$this->verbose( "\tSort sessions... ", 3, false );
		ksort( $sessions );
		$this->verbose( "OK", 3 );

		$ps =array(); // Paralel Sessions
		$ltf =false; // Last Time
		$lbc =false;

		foreach ($sessions as $id =>$S) {
			if ($ltf && ($ltf != $S['time_from'])) {
				
				$page =$this->session( $ps, $sid, $SHTML );

				if ($S['time_from'] == '11:00') $page .=$this->event( $lte, $S['time_from'], 'Coffee Break' );
				else if ($S['time_from'] == '14:00') $page .=$this->event( $lte, $S['time_from'], 'Lunch Break' );

				fwrite( $fp_day, $page );

				$ps =array();
				$SHTML =array();
			}
			
//			print_r( $S );
			
			if ($id == '999999_END') break;
						
			$sid =$S['code'];
			$npapers =is_array($S['papers']) ? count($S['papers']) : false;
		
			$this->verbose( "\tSession $sid ($S[time_from] > $S[time_to]) " .($npapers ? "$npapers papers" : false), 3 );
	
			$code ="<div class='code'>$sid</div>";
			$times ="<span class='timeh'>($S[time_from]" .$this->img('DB') ."$S[time_to])</span>";
	
			$SHTML["_$sid"] =
				"<td width='##W##' class='room$S[room] session'>$code " 
				.($S['chair'] ? "<span class='chair'><i>Chair:</i>&nbsp;$S[chair]</span><br />" : false) 
				.($npapers ? "<a href='javascript:ms(\"$sid\",\"##OSID##\");'>" : false) 
				."$S[title]</a><br />$times</td>";
		
			$SHTML["{$sid}_"]=		
				"<table id='$sid' border='0' cellpadding='3' cellspacing='0' class='prg' " .APP_TAB_W ." style='display: none; border-top: none;'>\n"
				."<tr><td colspan='##COLSPAN##' class='room$S[room]'>" .$this->img('SPh5') ."</td></tr>\n";
	
			$SHTML[$sid] =
				"<table border='0' cellpadding='3' cellspacing='0' class='prg'" .APP_TAB_W .">\n"
				."<tr><td colspan='3' class='room$S[room] session'>$code $S[title]"
				.($S['chair'] ? "<br /><span class='chair'><i>Chair:</i> $S[chair]</span> <span class='inst'>($S[chair_inst])</span>" : false)
				.($npapers ? false : "<br />$times") 
				."</td></tr>\n";

			$fst =true;
			if ($npapers) {
				foreach ($S['papers'] as $pid =>$P) {
					$rspan =" rowspan='" .count($S['papers']) ."'";

					$row ="<tr>" 
						.($fst ? "<td valign='bottom' width='10' class='room$S[room] shade' $rspan>" .$this->img('SHADE') ."</td>" : false) 		
						.($P['time_from'] ? "<td class='time' align='center' valign='top'>$P[time_from]" .$this->img('DA') ."</td>" : false)
						."<td" .($P['time_from'] ? "" : " colspan='2'") ." width='100%' class='paper'><span class='code2'>$pid</span>$P[author] <span class='inst'>($P[author_inst])</span>"
						."<br /><b>"
						.($P['abstract'] ? "<a href='javascript:ab(\"$pid\");'>$P[title]</a>" : $P['title'])
						."</b><div id='$pid' class='abstract'></div></td></tr>\n";
				
					$SHTML[$sid] .=$row;
					$SHTML["{$sid}_"] .=$row;
		
					$fst =false;
				}
			}
			$SHTML["{$sid}_"] .="</table>";
			$SHTML[$sid] .="</table>";

			array_push( $ps, $sid );
			$ltf =$S['time_from'];
			$lte =$S['time_to'];
		}

		fclose( $fp_day );
	}
 }

 //-----------------------------------------------------------------------------
 function session( &$ps, $sid, &$html ) {
	if (APP_SESSIONS == 'collapsed') return $this->multi_session( $ps, $html );
	 
	return (count($ps) == 1) ? $html["_${sid}"] : $this->multi_session( $ps, $html );
 }

 //-----------------------------------------------------------------------------
 function multi_session( $_codes, &$html ) {
	$width =round(100/count($_codes));

	$a =$b =false;

	foreach ($_codes as $c) {
		$a .="\t" .$html["_{$c}"] ."\n";
		$b .=$html["{$c}_"] ."\n";
	}

	$a =str_replace( '##W##', "$width%", $a );
	$a =str_replace( '##OSID##', implode(',',$_codes), $a );

	$b =str_replace( '##COLSPAN##', (count($_codes) +1), $b );
	
	return "<table border='0' cellpadding='3' cellspacing='0' class='prg'" .APP_TAB_W ."><tr valign='top'>\n"
		.$a ."</tr></table>\n" .$b;
 }

 //-----------------------------------------------------------------------------
 function event( $_time_from, $_time_to, $_text, $_class ='event' ) {
	return "<table border='0' cellpadding='3' cellspacing='0' class='$_class'" .APP_TAB_W ."><tr>"
//		."<th class='fst'>" .$this->img('SPw4') ."</th>"
		."<th class='fst'>" .$this->img('SHADE') ."</th>"
		."<td class='time'>$_time_from" .$this->img('DA') ."<br />$_time_to</td>"
		."<th width='100%'>$_text</th>"
		."</tr></table>";
 }

 //-----------------------------------------------------------------------------
 function img( $_type ) {
	$path =APP_IMG_PATH;
	
	switch ($_type) {
		case 'SHADE': 	return "<img src='$path/sh.png' width='10' height='30' />";
		case 'DA':		return "<br /><img src='$path/da.gif' width='5' height='15' />";
		case 'DB':		return " <img src='$path/db.gif' align='absmiddle' /> ";
		case 'SPw4':	return "<img src='$path/spacer.gif' width='4' height='1' />";
		case 'SPh5':	return "<img src='$path/spacer.gif' width='1' height='5' />";
	}
 }
}



//-----------------------------------------------------------------------------
function vevent_date( $_date, $_z =true ) {
 return date('Ymd',$_date) .'T' .date('His',$_date) .($_z ? 'Z' : false);
}

?>
