<?php

// 2014.08.12 by Stefano.Deiuri@Elettra.Eu & R.Mueller@gsi.de

//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
class SPMS_Programme {
 var $abstracts;
 var $classes;
 var $programme;
 var $cfg;

 //-----------------------------------------------------------------------------
 function SPMS_Programme() {
 	$this->abstracts =array();
 	$this->classes =array();
 	$this->programme =array();
	$this->programme['classes'] =array();
	
	
	$this->cfg['path'] =OUT_PATH .'/ScientificProgramme';
	$this->cfg['cache_dir'] =TMP_PATH .'/scientific_programme';
	$this->cfg['images'] ='ScientificProgramme/images';
	$this->cfg['programme_base_url'] ='index.php?n=Main.ScientificProgramme';
	$this->cfg['skip_session'] =false;
	$this->cfg['wget'] =true;
	$this->cfg['wget_options'] ='-q';

	$this->cfg['tsz_adjust'] =0;

	$this->cfg['tab_w'] =" width='700'";
	
	$this->cfg['abstracts_fname'] =DATA_PATH .'/scientific_programme/spms-abstracts.json';
	$this->cfg['programme_fname'] =DATA_PATH .'/scientific_programme/spms-programme.json';
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
 function prepare() {
	$path =$this->cfg['path'];
	if (!file_exists( $path )) {
		echo "Create output structure and base files\n";
		exec( "cp -r files/* " .OUT_PATH );
		mkdir( $this->cfg['cache_dir'] );
	}
 }
 
 //-----------------------------------------------------------------------------
 function cleanup() {
	system( 'rm -f ' .$this->cfg['path'] .'/*.html' );
	system( 'rm -f ' .$this->cfg['cache_dir'] .'/*' );
 } 

 //-----------------------------------------------------------------------------
 function save() {
	echo "\n# Save obj data: ";
	
	file_write_json( $this->cfg['programme_fname'], $this->programme );
	echo "programme, ";
	
	file_write_json( $this->cfg['abstracts_fname'], $this->abstracts );
	echo "abstracts.\n";
 }
 
 
 //-----------------------------------------------------------------------------
 function load( $_programme =true, $_abstracts =true ) {
  	if ($_abstracts) {
		$this->abstracts =file_read_json( $this->cfg['abstracts_fname'] );
	}
	
 	if ($_programme) {
		$this->programme =file_read_json( $this->cfg['programme_fname'] );
	}
 }

 
 //-----------------------------------------------------------------------------
 function load_xml( $_url, $_verbose =true ) {
	$wget_options =$this->cfg['wget_options'];
 
	$path =$this->cfg['cache_dir'];
	$xml_fname ='spms_summary.xml';
	if ($this->cfg['wget']) system( "wget $wget_options -O $path/$xml_fname $_url/$xml_fname" );
	$xml =simplexml_load_file( "$path/$xml_fname" );

	$sessions =array();
	
	foreach ($xml->children() as $S) {
		$scode =(string)$S->name->attributes()->abbr;
		$sessions[$scode] =(string)$S->name;
	}

	$n =1;
	$t =count($sessions);
	foreach ($sessions as $code =>$title) {
		if ($this->cfg['skip_session'] && in_array( $code, $this->cfg['skip_session'] )) {
			if ($_verbose) echo "# Skip session $code ($title)\n";
		} else {
			if ($_verbose) echo "# $n/$t Get session $code ($title)\n";
			$xml_fname ="$code.xml";
			if ($this->cfg['wget']) {
				$url ="$_url/xml2.session?sid=$code";
//				echo "Get from $url\n";
				system( "wget $wget_options -O $path/$xml_fname $url" );
			}
			$xml =simplexml_load_file( "$path/$xml_fname" );
			$this->load_session( $xml, $_verbose );
	
			$n ++;
		}
	}	

	sort( $this->programme['classes'] );
 }

 //-----------------------------------------------------------------------------
 function load_session( &$xml, $_verbose =true ) {
  foreach ($xml->children() as $S) {
    $scode =(string)$S->name->attributes()->abbr;
    $date  =(string)$S->date;
    $btime =(string)$S->date->attributes()->btime;

    if (@$S->chairs->chair->iname) {
		$chair =$S->chairs->chair->iname .' ' .$S->chairs->chair->lname;
		$chair_inst =(string)$S->chairs->chair->institutions->institute->full_name;
    } else {
		$chair =false;
		$chair_inst =false;
    }

    $room =substr($scode,-2) == 'GM' ? 'GM' : substr($scode,-1);

    $this->programme['days'][$date]['999999_END'] ='END';
    $session =&$this->programme['days'][$date]["{$btime}_{$room}_{$scode}"];

    $sstime =adjust_time( (string)$S->date->attributes()->btime ); // Session Start TIME
    $setime =adjust_time( (string)$S->date->attributes()->etime );

    $session =array(
		'code' =>$scode,
		'type' =>strtolower((string)$S->location->attributes()->type),
		'class' =>(string)$S->main_class,
		'title' =>(string)$S->name,
		'chair' =>$chair,
		'chair_inst' =>$chair_inst,
		'time_from' =>$sstime,
		'time_to' =>$setime,
		'tsz_from' =>strtotime( "$date $sstime" ) +$this->cfg['tsz_adjust'],
		'tsz_to' =>strtotime( "$date $setime" ) +$this->cfg['tsz_adjust'],
		'room' =>$room,
		'location' =>(string)$S->location,
		'papers' =>false
		);

    if ($_verbose) echo "## Session info: " .$S->date ." ". $sstime ." > " .$setime ." ($session[type])\n"
            ."## Get ".count($S->children()->papers->paper)." papers: ";

    foreach($S->children()->papers->paper as $P) {
		foreach($P->children()->program_codes->program_code as $PC) {
            $pcode =(string)$PC->code;

			if ($pcode && strpos( $pcode, $scode ) === 0) {
				if ($_verbose) echo '.';
				
				$this->abstracts[$pcode] =array(
					'text' =>(string)$P->abstract,
					'footnote' =>(string)$P->footnote,
					'agency' =>(string)$P->agency
					);
				
				$author =false;
				$author_inst =false;
				$type =strtolower((string)$PC->presentation);
				
				if (strpos($type, 'oral') !== false) {
					$this->get_author( $P, 'Presenter', $author, $author_inst );
					if (!$author) $this->get_author( $P, 'Speaker', $author, $author_inst );
					if (!$author) $this->get_author( $P, 'Primary Author', $author, $author_inst );
				} else $this->get_author( $P, 'Primary Author', $author, $author_inst );
				
				if ($_verbose && !$author) echo "(Warning, no Author)";
				
				$pclass =(string)$P->main_class;
				
				$pstime =adjust_time( (string)$PC->start_time );
				$petime =adjust_time( (string)$PC->start_time, (string)$PC->duration );
				
				if (!$pstime && ($session['type'] != 'poster')) $pstime =$sstime;
				
				$session['papers'][$pcode] =array(
					'class' =>$pclass,
					'title' =>(string)$P->title,
					'author' =>$author,
					'author_inst' =>$author_inst,
					'type' =>$type,
					'time_from' =>$pstime,
					'time_to' =>$petime,
					'tsz_from' =>strtotime( "$date $pstime" ) +$this->cfg['tsz_adjust'],
					'tsz_to' =>strtotime( "$date $petime" ) +$this->cfg['tsz_adjust'],
					'abstract' =>(strlen($this->abstracts[$pcode]['text'])>10),
					'abstract_id' =>(string)$P->abstract_id
					);
					
				if ($type == 'poster') {
					$this->get_author( $P, 'Presenter', $author, $author_inst, false );
					$session['papers'][$pcode]['presenter'] =($author ? "$author - $author_inst" : false);
				}
				
				if (!in_array( $pclass, $this->programme['classes'] )) array_push( $this->programme['classes'], $pclass );
			}
		}
	}

    if ($_verbose) echo "\n";
    }
 }

 //-----------------------------------------------------------------------------
 function get_author( &$_P, $_type, &$_author, &$_author_inst, $_full_inst =true ) {
    foreach ($_P->contributors->children() as $A) {
		if ($A->attributes()->type == $_type) {
			$_author =$A->iname .' ' .$A->lname;
			
			if ($_full_inst) {
				$_author_inst =(string)$A->institutions->institute->full_name;
			} else {
				$_author_inst =(string)$A->institutions->institute->full_name->attributes()->abbrev;
				if (!$_author_inst) $_author_inst =(string)$A->institutions->institute->full_name;
			}
			return true;
		}
	}

	$_author =false;
	$_author_inst =false;
	return false;
 }
 
 //-----------------------------------------------------------------------------
 function make_abstracts() {
	echo "Write Abstracts: ";

	foreach ($this->abstracts as $aid =>$A) {
		if (strlen($A['text']) > 10) {
			echo "$aid ";
			$fpa =fopen( $this->cfg['path'] ."/abstract.$aid.html", 'w' );
			
			$page =$A['text'] ."\n"
				.($A['footnote'] ? "<hr noshade size='1' width='60%' /><small>" .str_replace( '**', '<br />**', $A['footnote'] ) ."</small>\n" : false)
				.($A['agency'] ? "<hr noshade size='1' width='60%' /><small>$A[agency]</small>\n" : false)
				;			
			
			fwrite( $fpa, $page );
			fclose( $fpa );
		}
	}
	
	echo "\n\n";
 }

 //-----------------------------------------------------------------------------
 function make_ics() {
//http://www.elettra.trieste.it/events/2012/ipac/programme/programme.ics

	$fp =fopen( $this->cfg['path'] .'/programme.ics', 'w' );

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
	$SHTML =array(); // Sessions HTML
 
	if (!$this->classes) {
		$this->classes =$this->programme['classes'];
	}
	
	$days =array_keys($this->programme['days']);
 
	$dayn =0;
	foreach ($this->programme['days'] as $day =>$sessions) {
		$dayn ++;

		if (!$sessions) break;

		echo "Day $dayn - $day\n";

		$fp_day =fopen( $this->cfg['path'] ."/day$dayn.html", 'w' );

		$menu =false;

		$dow =array( 'Fri', 'Thu', 'Wed', 'Tue', 'Mon' );
		$daynt =0;
		foreach ($days as $d) {
			$daynt ++;
			$d2 =date( 'D, d M', strtotime( $d ));

			$sel =$day == $d;
			
			$menu .="<td class='m" .($sel ? 's' : false) ."'>"
				.($sel ? $d2 : "<a href='" .$this->cfg['programme_base_url'] .(strpos($this->cfg['programme_base_url'],'?') ? '&' : '?') ."day=$daynt'>$d2</a>")
				."</td>";
		}

		fwrite( $fp_day, "<table class='day' " .$this->cfg['tab_w'] ." cellpadding='3' cellspacing='0'>"
			."<tr class='days'>$menu</tr>"
			."</table>\n"
			);
		
		
		echo "\tSort sessions... ";
		ksort( $sessions );
		echo "OK\n";

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
		
			echo "\tSession $sid ($S[time_from] > $S[time_to]) " .($npapers ? "$npapers papers" : false) ."\n";
	
			$code ="<div class='code'>$sid</div>";
			$times ="<span class='timeh'>($S[time_from]" .$this->img('DB') ."$S[time_to])</span>";
	
			$SHTML["_$sid"] =
				"<td width='##W##' class='room$S[room] session'>$code " 
				.($S['chair'] ? "<span class='chair'><i>Chair:</i>&nbsp;$S[chair]</span><br />" : false) 
				.($npapers ? "<a href='javascript:ms(\"$sid\",\"##OSID##\");'>" : false) 
				."$S[title]</a><br />$times</td>";
		
			$SHTML["{$sid}_"]=		
				"<table id='$sid' border='0' cellpadding='3' cellspacing='0' class='prg' " .$this->cfg['tab_w'] ." style='display: none; border-top: none;'>\n"
				."<tr><td colspan='##COLSPAN##' class='room$S[room]'>" .$this->img('SPh5') ."</td></tr>\n";
	
			$SHTML[$sid] =
				"<table border='0' cellpadding='3' cellspacing='0' class='prg'" .$this->cfg['tab_w'] .">\n"
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
	return (count($ps) == 1) ? $html[$sid] : $this->multi_session( $ps, $html );
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
	
	return "<table border='0' cellpadding='3' cellspacing='0' class='prg'" .$this->cfg['tab_w'] ."><tr valign='top'>\n"
		.$a ."</tr></table>\n" .$b;
 }

 //-----------------------------------------------------------------------------
 function event( $_time_from, $_time_to, $_text, $_class ='event' ) {
	return "<table border='0' cellpadding='3' cellspacing='0' class='$_class'" .$this->cfg['tab_w'] ."><tr>"
//		."<th class='fst'>" .$this->img('SPw4') ."</th>"
		."<th class='fst'>" .$this->img('SHADE') ."</th>"
		."<td class='time'>$_time_from" .$this->img('DA') ."<br />$_time_to</td>"
		."<th width='100%'>$_text</th>"
		."</tr></table>";
 }

 //-----------------------------------------------------------------------------
 function img( $_type ) {
	$path =$this->cfg['images'];
	
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


//-----------------------------------------------------------------------------
function adjust_time( $_time, $_duration =false ) {
	if (!$_time) return false;
	$min =substr( $_time, -2 );

	$h =substr( $_time, 0, strlen( $_time ) -2);

	if ($_duration) {
		$min +=$_duration;
		if ($min >= 60) {
			$h ++;
			$min -=60;
		}
	}

	return str_pad( $h, 2, '0', STR_PAD_LEFT ) .':' .str_pad( $min, 2, '0', STR_PAD_LEFT );

	return (strlen($h) == 1 ? '0' : '') ."$h:" .(strlen($min) == 1 ? '0' : '') .$min;

	return (strlen($h) == 1 ? '0' : '') ."$h:$min";
}

?>
