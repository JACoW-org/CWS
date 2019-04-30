<?php

// 2019.04.29  by Stefano.Deiuri@Elettra.Eu & R.Mueller@gsi.de

//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
class SPMS_Importer extends CWS_OBJ {
 var $abstracts;
 var $classes;
 var $programme;

 //-----------------------------------------------------------------------------
 function __construct( $_cfg =false ) {
 	$this->abstracts =array();
 	$this->classes =array();
 	
	$this->programme =array( 'classes' =>array(), 'rooms' =>array() );
	
/*	
	foreach (explode( ',', APP_ROOMS ) as $room) {
		$this->programme['rooms'][$room] =0;
	}
*/	

	if ($_cfg) $this->config( $_cfg );

 }

 //-----------------------------------------------------------------------------
 function cleanup() {
	system( 'rm -f ' .APP_TMP_PATH .'/*' );
 } 

 //-----------------------------------------------------------------------------
 function save_programme() {
	$this->verbose( "# Save Programme Data (" .APP_PROGRAMME .")... ", 1, false );
	if (file_write_json( APP_PROGRAMME, $this->programme )) $this->verbose_ok();
	else $this->verbose_error( "Unable to write file " .APP_PROGRAMME );
 }
 
 //-----------------------------------------------------------------------------
 function save_abstracts() {
	$this->verbose( "# Save Abstracts Data (" .APP_ABSTRACTS .")... ", 1, false );
	if (file_write_json( APP_ABSTRACTS, $this->abstracts )) $this->verbose_ok();
	else $this->verbose_error( "Unable to write file " .APP_ABSTRACTS );
 }
 
 //-----------------------------------------------------------------------------
 function save_po() {
	$this->verbose( "# Save Proceeding Office Data (" .APP_PO .")... ", 1, false );
	$PO =false;

	$nums =false;
	
	foreach ($this->programme['days'] as $day =>$odss) { // ObjDaySessions
		foreach ($odss as $id =>$os) { // ObjSession
			if (is_array($os)) {				
				foreach ($os['papers'] as $pid =>$op) { // ObjPoster
					$nums[ $op['primary_code'] ] ++;
									
					$PO[$pid] =array(
						'code' =>$pid,
						'primary_code' =>$op['primary_code'],
						'title' =>$op['title'],
						'abstract_id' =>$op['abstract_id']
						);
				}
			}
		}
	}
	
	if ($PO) {
		file_write_json( APP_PO, $PO );	 
		
		echo "(" .$nums['Y'] .") ";
		
		$this->verbose_ok();
		
//		print_r( $nums );
		
	} else {
		$this->verbose_error( "(No data)" );
	}
 } 
 
 //-----------------------------------------------------------------------------
 function export_citations( $_fname =false ) {
	
	$out_fname =$_fname ? $_fname : APP_CITATIONS;
	 
	$this->verbose( "# export_citations (" .$out_fname .")... ", 1, false );
	$citations =false;

	foreach ($this->programme['days'] as $day =>$odss) { // ObjDaySessions
		foreach ($odss as $id =>$os) { // ObjSession
			if (is_array($os)) {				
				foreach ($os['papers'] as $pid =>$op) { // ObjPoster
					$citations[] =array(
						'paper' =>$pid,
						'authors' =>$op['authors'],
						'title' =>$op['title']
						);
				}
			}
		}
	}
	
	if ($citations) {
		$fp =fopen( $out_fname, 'w' );
		$headers =true;
		foreach ($citations as $cit) {
			if ($headers) {
				fputcsv( $fp, array_keys( $cit ) );
				$headers =false;
			}
			fputcsv( $fp, $cit );
		}
		fclose( $fp );

		$this->verbose_ok( "(" .count($citations) .") " );
		
	} else {
		$this->verbose_error( "(No data)" );
	}
 }
 
 //-----------------------------------------------------------------------------
 function export_status_paper_data( $_path ) {
	$this->verbose( "# Save Paper Status Data (" .$_path ."/SESSID.json)... ", 1, false );

	foreach ($this->programme['days'] as $day =>$odss) { // ObjDaySessions
		foreach ($odss as $id =>$os) { // ObjSession
			if (is_array($os)) {				
				$PO =false;
				$sid =$os['code'];
				foreach ($os['papers'] as $pid =>$op) { // ObjPoster					
					$PO[$pid] =array(
						'code' =>$pid,
						'title' =>$op['title'],
						'abstract_id' =>$op['abstract_id']
						);
				}
				
				if ($PO) {
					file_write_json( "$_path/$sid.json", $PO );	 
					$this->verbose_ok( "$sid ", 2, false );
				} else {
					$this->verbose_error( "!$sid ", 2, false );
				}
			}
		}
	}

	$this->verbose_ok();
 }
 
 //-----------------------------------------------------------------------------
 function save_posters() {
	$this->verbose( "# Save Poster Police Data (" .APP_POSTERS .")... ", 1, false );

	$PP =false;
	
	foreach ($this->programme['days'] as $day =>$odss) { // ObjDaySessions
		foreach ($odss as $id =>$os) { // ObjSession
			if (strpos( $os['type'], 'oster' ) !== false) {
				$sid =$os['code'];
				
				$PP[$day][$sid] =array( 
					'code' =>$sid,
					'type' =>$os['type'],
					'title' =>$os['title'],
					'location' =>$os['location']
					);			
					
				foreach ($os['papers'] as $pid =>$op) { // ObjPoster					
					$pn =substr( $pid, -3 );
					$PP[$day][$sid]['posters'][$pn] =array(
						'code' =>$pid,
						'title' =>$op['title'],
						'presenter' =>$op['presenter'],
						'abstract_id' =>$op['abstract_id']
						);
				}
			}
		}
	}	
	
	if ($PP) {
		if (file_write_json( APP_POSTERS, $PP )) $this->verbose_ok();
		else $this->verbose_error();
	}
 }
 
 

 
 
 //-----------------------------------------------------------------------------
 function load( $_programme =true, $_abstracts =true ) {
  	if ($_abstracts) {
		$this->abstracts =file_read_json( APP_ABSTRACTS );
	}
	
 	if ($_programme) {
		$this->programme =file_read_json( APP_PROGRAMME );
	}
 }

 
 //-----------------------------------------------------------------------------
 function load_xml( $_url, $_verbose =true ) {
	$wget_options =WGET_OPTIONS;
 
	$path =APP_TMP_PATH;
	
	$this->verbose( "# Get summary... ", 1, false );
	$xml_fname ='spms_summary.xml';
	if ($this->cfg['wget']) system( "wget $wget_options -O $path/$xml_fname $_url/$xml_fname" );
	$xml =simplexml_load_file( "$path/$xml_fname", 'SimpleXMLElement' );

//	print_r( $xml );	
	
	$sessions =array();
	
	foreach ($xml->children() as $S) {
//		print_r( $S );
		
		$scode =@(string)$S->name->attributes()->abbr;
		if ($scode) $sessions[$scode] =(string)$S->name;
	}


	$n =1;
	$t =count($sessions);
	if ($t) $this->verbose_ok();
	else {
		$this->verbose_error( "ERROR (No sessions)" );
		return false;
	}
	
	$this->verbose( "# Get $t sessions " );
	foreach ($sessions as $code =>$title) {
		if (is_array(APP_SKIP_SESSIONS) && in_array( $code, APP_SKIP_SESSIONS )) {
			$this->verbose( "# Skip session $code ($title)" );
			
		} else {
			$this->verbose(array( 
				1 =>"$code ",
				2 =>"- $n/$t Get session ($title)\n"
				));
			
			$xml_fname ="$code.xml";
			if ($this->cfg['wget']) {
				$url ="$_url/xml2.session_data?sid=$code";
//				echo "Get from $url\n";
				system( "wget $wget_options -O $path/$xml_fname $url" );			
			}
			
			$xml_source =implode( "\n", file( "$path/$xml_fname" ));
			$xml =simplexml_load_string( $xml_source, 'SimpleXMLElement', LIBXML_ERR_FATAL );

			$err =libxml_get_last_error();
			if ($err) {
				$this->verbose_error( "!! Session $code xml error on line " .$err->line .": " .trim($err->message));
				libxml_clear_errors();
			}
			
			if (!$xml) {
				$err =libxml_get_last_error();
				$this->verbose_error( "!! Session $code xml error on line " .$err->line .": " .trim($err->message));
				libxml_clear_errors();
				
			} else {
				$this->load_session( $xml, $_verbose );
			}
	
			$n ++;
		}
	}	

	sort( $this->programme['classes'] );
	
	return true;
 }

 //-----------------------------------------------------------------------------
 function load_session( &$xml, $_verbose =true ) {
  foreach ($xml->children() as $S) {
	$scode =(string)$S->name->attributes()->abbr;
	
	if (count($S->children()->papers->paper) == 0) {
		$this->verbose( "!! Empty session", 1 );
		
	} else {
		$date  =(string)$S->date;
		$btime =(string)$S->date->attributes()->btime;

		if (@$S->chairs->chair->iname) {
			$chair =$S->chairs->chair->iname .' ' .$S->chairs->chair->lname;
			$chair_inst =(string)$S->chairs->chair->institutions->institute->full_name;
		} else {
			$chair =false;
			$chair_inst =false;
		}

		$room =preg_replace("/[^a-zA-Z0-9]+/", "", (string)$S->location);
		if (isset($this->programme['rooms'][$room])) $this->programme['rooms'][$room] ++;
		else $this->programme['rooms'][$room] =1;
		
		$this->programme['days'][$date]['999999_END'] ='END';
		$session =&$this->programme['days'][$date]["{$btime}_{$room}_{$scode}"];

		$sstime =$this->adjust_time( (string)$S->date->attributes()->btime ); // Session Start TIME
		$setime =$this->adjust_time( (string)$S->date->attributes()->etime );

		$session =array(
			'code' =>$scode,
			'type' =>strtolower((string)$S->location->attributes()->type),
			'class' =>(string)$S->main_class,
			'title' =>(string)$S->name,
			'chair' =>$chair,
			'chair_inst' =>$chair_inst,
			'time_from' =>$sstime,
			'time_to' =>$setime,
			'tsz_from' =>strtotime( "$date $sstime" ) +APP_TSZ_ADJUST,
			'tsz_to' =>strtotime( "$date $setime" ) +APP_TSZ_ADJUST,
			'room' =>$room,
			'location' =>(string)$S->location,
			'papers' =>false
			);

		$this->verbose( "## Session info: " .$S->date ." ". $sstime ." > " .$setime ." ($session[type])\n"
				."## Get " .count($S->children()->papers->paper) ." papers", 3, false );

		foreach($S->children()->papers->paper as $P) {
			foreach($P->children()->program_codes->program_code as $PC) {
						$pcode =(string)$PC->code;
				
				if ($pcode && strpos( $pcode, $scode ) === 0) {
					$this->verbose( '.', 3, false );
					
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
					
					if (!$author) $this->verbose_error( "(Warning, no Author)" );
					
					$authors =$this->get_authors( $P );
										
					$pclass =(string)$P->main_class;
					
					$pstime =$this->adjust_time( (string)$PC->start_time );
					$petime =$this->adjust_time( (string)$PC->start_time, (string)$PC->duration );
					
					if (!$pstime && ($session['type'] != 'poster')) $pstime =$sstime;
					
					$session['papers'][$pcode] =array(
						'class' =>$pclass,
						'title' =>(string)$P->title,
						'author' =>$author,
						'author_inst' =>$author_inst,
						'authors' =>$authors,
						'type' =>$type,
						'time_from' =>$pstime,
						'time_to' =>$petime,
						'tsz_from' =>strtotime( "$date $pstime" ) +APP_TSZ_ADJUST,
						'tsz_to' =>strtotime( "$date $petime" ) +APP_TSZ_ADJUST,
						'abstract' =>(strlen($this->abstracts[$pcode]['text'])>10),
						'abstract_id' =>(string)$P->abstract_id,
						'primary_code' =>(string)$PC->code->attributes()->primary
						);
						
					if ($type == 'poster') {
						$this->get_author( $P, 'Presenter', $author, $author_inst, false );
						$session['papers'][$pcode]['presenter'] =($author ? "$author - $author_inst" : false);
					}
					
					if (!in_array( $pclass, $this->programme['classes'] )) array_push( $this->programme['classes'], $pclass );
				}
			}
		}

		$this->verbose_ok( ' OK', 3 );
		}
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
 function get_authors( &$_P ) {
	$pauthor =false;
	$coauthors =false;
	 
    foreach ($_P->contributors->children() as $A) {
		if ($A->attributes()->type == 'Primary Author') {
			$pauthor =$A->iname .' ' .$A->lname;
		}
		if ($A->attributes()->type == 'Co-Author') {
			$coauthors .=($coauthors ? ", " : false) .$A->iname .' ' .$A->lname;
		}
	}

	return $pauthor .($coauthors ? ", " : false) .$coauthors;
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
 } 
 
 //-----------------------------------------------------------------------------
 function xtract( $_type, $_what =false, $_parse =false ) {
// print_r(get_defined_constants(true));
 
	$chk =$_type == 'regstats' ? false : true;
	 
	$check_data =$_type == 'regstats' ? false : true;
	 
	$url =SPMS_URL .'/xtract.'.$_type .($_what ? '?what='.$_what : false);
	echo "Get data from: $url.. ";
		
	if ($chk) $url .=($_what ? '&' : '?') .'chk=' .SPMS_PASSPHRASE;
	 
	$data =file( $url );
	
	$xtract =$_type .($_what ? '_' .$_what : false);
	
	file_write( TMP_PATH .'/xtract_' .$xtract, $data );
	 
	if (count( $data ) < 3) {
		$this->verbose_error( "ERROR: no data" );
		return false;
	}
	 
	if ($check_data) {
		if (trim($data[0]) != "#$xtract:begin") {
			$this->verbose_error( "ERROR: no valid data [1]" );
			return false;
		}
		 
		if (trim(end($data)) != "#$xtract:end") {
			$this->verbose_error( "ERROR: no valid data [2]" );
			return false;
		}
	}
	 
	$ret =false;
	foreach ($data as $line) {
		if (substr($line, 0, 1 ) != '#') {
			if ($_parse) {
				$x =array_map( "trim", explode( ',', $line ) );
				
				$key =array_shift( $x );
				$ret[$key] =$x;
				
			} else {
				$ret[] =trim($line);
			}
		}
	}
	 
	$this->verbose_ok( "OK (" .count($ret) ." records)" );
	 
	return $ret;
 } 

 //-----------------------------------------------------------------------------
/* 
 function GoogleChart_old() {
	extract( $this->cfg );
	
	list( $type, $what ) =explode( ',', $xtract );
	
	$var =$y_title;

	if ($startdate && strpos( $startdate, '-' )) $startdate =strtr( $startdate, '-', ',' );
	
	$csv =$this->xtract( $type, $what );
	
	if (!$csv) {
		$this->verbose_error( "ERROR: no data" );
		return;
	}
	
	$i =0;
	$n =0;
	$addrow =false;
	foreach ($csv as $line) {
			list( $date, $value ) =explode( ', ', trim($line) );

			if ($value) {
				if ($i == 0 && $startdate) $addrow .=" data.addRow([new Date($startdate),0]);\n";
			
				list( $dy, $dm, $dd ) =explode( '-', $date );
				$dm --;
				$dd +=0;

				$n +=$value;
				$addrow .=" data.addRow([new Date($dy,$dm,$dd),$n]);\n";
				
				$i ++;
			}
	}

	$this->verbose_ok( "OK ($i records)" );
	
	echo "\n";

	$width =CHART_WIDTH;
	$height =CHART_HEIGHT;
	
	$color1 =$this->cfg['colors']['primary'];
	$color2 =$this->cfg['colors']['secondary'];
	
	$js =APP_OUT_JS;
			
	foreach (array( 'html', 'js' ) as $ftype) {
		$tmpl =$this->cfg['chart_'.$ftype];
		if ($tmpl) {
			$template =file_read( $tmpl );
			
			eval( "\$out =\"$template\";" );
			
			$fname =$this->cfg['out_path'] .'/' .($ftype == 'html' ? APP_OUT_HTML : APP_OUT_JS);
			echo "Save file $fname... ";
			
			if (file_write( $fname, $out ))  $this->verbose_ok();
			else  $this->verbose_error( "Unable to write file $fname" );
			
			echo "\n";
		}
	}
	
	echo "\n";
 }
*/ 
 
 
 //-----------------------------------------------------------------------------
 function GoogleChart() {
	extract( $this->cfg );
	
	list( $type, $what ) =explode( ',', $xtract );
	
	$var =$y_title;

	if ($startdate && strpos( $startdate, '-' )) $startdate =strtr( $startdate, '-', ',' );
	
	$data =$this->xtract( $type, $what, true );
	
	if (!$data) {
		$this->verbose_error( "ERROR: no data" );
		return;
	}
	
	$i =0;
	$n =0;
	$addrow =false;
	foreach ($data as $date =>$value) {
			if ($value[0]) {
				if ($i == 0 && $startdate) $addrow .=" data.addRow([new Date($startdate),0]);\n";
			
				list( $dy, $dm, $dd ) =explode( '-', $date );
				$dm --;
				$dd +=0;

				$n +=$value[0];
				$addrow .=" data.addRow([new Date($dy,$dm,$dd),$n]);\n";
				
				$i ++;
			}
	}

//	$this->verbose_ok( "OK ($i records)" );
	
	echo "\n";

	$width =CHART_WIDTH;
	$height =CHART_HEIGHT;
	
	$color1 =$this->cfg['colors']['primary'];
	$color2 =$this->cfg['colors']['secondary'];
	
	$js =APP_OUT_JS;
			
	foreach (array( 'html', 'js' ) as $ftype) {
		$tmpl =$this->cfg['chart_'.$ftype];
		if ($tmpl) {
			$template =file_read( $tmpl );
			
			eval( "\$out =\"$template\";" );
			file_write( $this->cfg['out_path'] .'/' .($ftype == 'html' ? APP_OUT_HTML : APP_OUT_JS), $out, 'w', true, $ftype );
			
			echo "\n";
		}
	}
 }

}

?>