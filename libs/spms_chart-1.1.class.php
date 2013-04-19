<?php

// 2013.03.01 bY Stefano.Deiuri@Elettra.Trieste.it

//-----------------------------------------------------------------------------
class SPMS_Chart {
 //-----------------------------------------------------------------------------
 function SPMS_Chart( $_spms_server, $_chk =false ) {
	$this->cfg =array(
		'spms_server' =>$_spms_server,
		'chk' =>$_chk,
		'width' =>800,
		'height' =>200,
		'startdate' =>false,
		'showmaxvalue' =>false,
		'out_path' =>OUT_PATH,
		'skip_format_check' =>false, // usefull for regstats
		'debug' =>true,
		'template_js' =>'GoogleChartTemplate.js',
		'template_html' =>'GoogleChartTemplate.html'
		);
 }
 
 //-----------------------------------------------------------------------------
 function Config( $_name, $_value ) {
	$this->cfg[$_name] =$_value;
 }

 //-----------------------------------------------------------------------------
 function GoogleChart( $_type, $_what =false, $_var =false ) {
	extract( $this->cfg );
	
	$var =$_var;
	$maxvalue =false;

	$maxvaluecolumn =$showmaxvalue ? " data.addColumn('number', 'Max number of $var');\n" : false;
	
	$url ="$spms_server/xtract.$_type" .($_what ? "?what=$_what" : false);
	echo "Get data from: $url.. ";
	if ($chk) $url .=($_what ? '&' : '?') .'chk=' .$chk;
	$csv =file( $url );
	if ($debug) file_write( "../tmp/$_var.csv", $csv );
	
	$i =0;
	$n =0;
	$dataok =false;
	$addrow =false;
	foreach ($csv as $line) {
		if (substr( $line, 0, 1 ) != '#') {
			list( $date, $value ) =explode( ', ', trim($line) );

			if ($value) {
				if ($i == 0 && $startdate) $addrow .=" data.addRow([new Date($startdate),0" .($showmaxvalue && $maxvalue ? ",$maxvalue" : false) ."]);\n";
			
				list( $dy, $dm, $dd ) =explode( '-', $date );
				$dm --;
				$dd +=0;

				$n +=$value;
				$addrow .=" data.addRow([new Date($dy,$dm,$dd),$n" .($showmaxvalue && $maxvalue ? ",$maxvalue" : false) ."]);\n";
				
				$i ++;
			}
		} else {
			$key2 =$_type .($_what ? "_$_what" : false);
		
			list( $key, $value ) =explode( ':', substr(trim($line),1) );
			switch ($key) {
				case 'total_abstracts': $maxvalue =$value; break;
				case "$key2": 
					if ($value == 'end') {
						if (!$maxvalue) $maxvalue =$n;
						$dataok =true;
					}
					break;
			}
		}
	}
	
	if ($skip_format_check && !$dataok && $n) {
		if (!$maxvalue) $maxvalue =$n;
		$dataok =true;
	}
	
	echo ($dataok ? "OK ($i records)" : "ERROR: bad data");
	echo "\n";

	foreach (array( 'html', 'js' ) as $type) {
		$tmpl =$this->cfg['template_'.$type];
		if ($tmpl) {
			$fname =$this->cfg['out_path'] .'/Chart-' .$var .'.' .$type;
			echo "Save file $fname... ";
			$template =file_read( $tmpl );
			eval( "\$out =\"$template\";" );
			echo file_write( $fname, $out ) ? "OK" : "Error";
			echo "\n";
		}
	}
	
	echo "\n";
 }
}


?>
