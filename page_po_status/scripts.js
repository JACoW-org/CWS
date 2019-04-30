
// 2019.04.30 bY Stefano.Deiuri@Elettra.Eu

var cfg ={
	version: 3,
	mode: 'full',
	lstore: true,
	col1w: 1080,
	pageh: 1030,
	change_page_delay: 10, // seconds
	reload_data_delay: 30, // seconds	
	history_date_start: '2019-04-29'
	};

var n_page =3;
var active_page =0;
var change_page =true;

var history_chart =false;

var rates_page_ok =false;

var ndots =0;

var data_ts =0;

var stats_history =[], stats_title ={}, history_colors =[], stats =[], edots =[], editors =[], editors_qa =[];

var init ={
	cache: true,
	stats: true,
	edots: true,
	history: true
	};

var local_torage_prefix ='postatus_';
	
//---------------------------------------------------------------------------------------------
function getLocalStorageObj( _id ) {
	json =localStorage.getItem( local_torage_prefix +_id );
	if (json == undefined || json == '') return undefined;
	return JSON.parse( json );
}

//---------------------------------------------------------------------------------------------
function setLocalStorageObj( _id, _obj )	{
	localStorage.setItem( local_torage_prefix +_id, JSON.stringify(_obj) );
}	

//---------------------------------------------------------------------------------------------
function removeLocalStorageObj( _id )	{
	localStorage.removeItem( local_torage_prefix +_id );
}	
	
$(document).ready( function() {
	if (navigator.platform.indexOf('arm') != -1) {
		console.log( 'Enable SLOW mode!' )
		cfg.mode ='slow';
	}
	
	if (typeof localStorage == undefined) {
		console.log( 'Disable localStorage' )
		cfg.lstore =false;
	
	} else {
		var lcfg =getLocalStorageObj( 'cfg' );
		
		if (lcfg != undefined) {
			if (lcfg.version == cfg.version) {
				console.log( "Load configuration from localStorage" );
				cfg =lcfg;
				
			} else {
				lcfg =undefined;
			}
		}
		
		if (lcfg == undefined) {
			console.log( "Save configuration to localStorage" );
			setLocalStorageObj( 'cfg', cfg );
		} 
	}
	
	show_page();
	setInterval( update_timer, 100 );	
});

//---------------------------------------------------------------------------------------------
function show_page() {
	duration =500;
	
	console.log( 'Show page ' +active_page );
	
	if (active_page) {
		switch (cfg.mode) {
			case 'slow':
				$('div[page=' +active_page +']').hide();
				break;
				
			default:
				$('div[page=' +active_page +']').fadeOut(duration);
		}
	}
	
	if (change_page == false) {
		$('#activepage').html( active_page ); 
		$('div[page=' +active_page +']').show();
		return;
	}
	
	active_page ++;
	if (active_page == (n_page+1)) active_page =1;

	switch (cfg.mode) {
		case 'slow':
			$('div[page=' +active_page +']').show();
			break;
			
		default:
			$('div[page=' +active_page +']').delay(duration).fadeIn(duration);
	}

	if (n_page) $('#activepage').html( active_page ); 

	setTimeout( show_page, cfg.change_page_delay *1000 );

	timer =cfg.change_page_delay *10;
	$('#timer').css( 'left', 910 );
}

//---------------------------------------------------------------------------------------------
function update_timer() {
 var w =Math.floor((timer *20) /cfg.change_page_delay);
 var l =960 -w/2;
 $('#timer').css( 'width', w );
 $('#timer').css( 'left', l );
 
 timer --;
 
 var d =new Date();
 $('#clock').html( `${pad(d.getHours())}:${pad(d.getMinutes())}<span style='color:#bbb;'>:${pad(d.getSeconds())}</span>` );
}

//---------------------------------------------------------------------------------------------
function load_data() {
	if (cfg.lstore) {
		var local_ts =getLocalStorageObj( 'local_ts' );
		
		if (local_ts != undefined && init.cache) {
			console.log( 'Load data from localStorage ' +local_ts );
			
			data_ts =local_ts;
			
			for (id in localStorage) {
				x =id.split('_');
				
				if (`${x[0]}_` == local_torage_prefix) {
					if (x[1] == 'dot') edots[ x[2] ] =getLocalStorageObj( 'dot_'+x[2] );
					else if (x[1] == 'history') stats_history[ x[2] ] =getLocalStorageObj( 'history_'+x[2] );
				}
			}
		}
	}
	
	init.cache =false;
	
	$.getJSON( 'get_data.php', { ts: data_ts } )
		.done(function(obj) {
			if (obj.error) {
				console.log( 'DATA ERROR!' );
				setTimeout( load_data, (cfg.reload_data_delay /2) *1000 );
				return;
			}
			
			console.log( `Load data ${obj.ts}` );
			
			history_colors =obj.colors;
			stats_title =obj.labels;
			
			var history_updated =false;
			var edots_updated =false;
			
			if (obj.history != undefined) {
				console.log( "  Process history" );

				if (cfg.lstore) setLocalStorageObj( 'local_ts', obj.ts );

				for (id in obj.history) {
					console.log( '    ' +id );
					stats_history[id] =obj.history[id];
					
					if (cfg.lstore) setLocalStorageObj( 'history_' +id, obj.history[id] );
					history_updated =true;
				}
			}

			if (obj.edots != undefined) {
				console.log( "  Process edots" );

				if (cfg.lstore) setLocalStorageObj( 'local_ts', obj.ts );

				for (id in obj.edots) {
					status =obj.edots[id];
					edots[id] =status;
					
					console.log( '    ' +id +' (' +status +')' );

					if (cfg.lstore) {
						if (status == 'removed') removeLocalStorageObj( 'dot_' +id );
						else setLocalStorageObj( 'dot_' +id, status );
					}
					
					if (status == 'removed') {
						init.edots =true;
					}
					
					edots_updated =true;
				}
			}
			
			if (obj.editors != undefined) {
				console.log( "  Process editors" );

				editors =[];
				
				var i =0;
				for (id in obj.editors) {
					ed =obj.editors[id];
					editors_qa[`${pad100(ed.qa)}|${ed.name}`] ={ name: ed.name, qa: ed.qa };
					
					if (i < 10) {
						editors[ed.name] =ed;
						i ++;
					}
					
					editors_updated =true;
				}
			}

			last_history_tm =Object.keys(stats_history).pop();
			stats =stats_history[last_history_tm];
			
			data_ts =obj.ts;
			
			if (history_updated || init.stats) {
				ndots =stats.total;
			
				update_history( stats_history );
				update_stats( stats );
				
				if (stats.processed > 10) {
					update_rates( stats_history );
					update_editors( editors );
					update_editors_qa( editors_qa );
					n_page =rates_page_ok ? (stats.qaok ? 7 : 5) : 2;
					
				} else {
					n_page =2;
				}
				
				$('#npages').html( '/' +n_page );
			}

			if (edots_updated || init.edots) {
				update_edots( edots );
			}
			
			setTimeout( load_data, cfg.reload_data_delay *1000 );
			})
			
		.fail(function(XMLHttpRequest, textStatus, errorThrown) {
			console.log( "load data FAIL!" );
			console.dir( XMLHttpRequest );
			console.dir( textStatus );
			console.dir( errorThrown );
			
			setTimeout( load_data, cfg.reload_data_delay *1000 );
		});		
		
}

//---------------------------------------------------------------------------------------------
function update_history( obj ) {
	console.log( "Update history" );
	
	var history_chart_options ={ 
			isStacked: true,
			legend: 'none', 
			lineWidth: 2,
			width: 1180,
			height: cfg.pageh -20,
			chartArea: { left: 100, right:0, top: 20, bottom: 20, width: '100%', height: '100%' },
			colors: history_colors,
			vAxis: {title: 'Papers', minValue: 0, maxValue: 1500, ticks: [ 500, 1000, 1500 ] }
			};

	var data =new google.visualization.DataTable();
	data.addColumn('date','Date');
	data.addColumn('number','Files');
	data.addColumn('number','Processing');
	data.addColumn('number','QAOK');
	data.addColumn('number','Green');
	data.addColumn('number','Yellow');
	data.addColumn('number','Red');
	data.addColumn('number','NoFiles');
	
	var g, qaok;
	
	var d, d_start =new Date( cfg.history_date_start );

	for (tm2 in obj) {
			t =tm2.split('-');
			if (t[2] && t[3]) {
				x =obj[tm2];
				qaok =x.qaok;
				if (qaok > x.g) qaok =x.g;
				g =(x.g -qaok);
				
				d =new Date(t[0],(t[1]-1),t[2],t[3],59);

				if (d > d_start)
					data.addRow([
						d, 
						(x.files == 0 ? undefined : x.files), 
						(x.a == 0 ? undefined : x.a), 
						(qaok == 0 ? undefined : qaok), 
						(g == 0 ? undefined : g), 
						(x.y == 0 ? undefined : x.y), 
						(x.r == 0 ? undefined : x.r), 
						(x.nofiles == 0 ? undefined : x.nofiles), 
						]);
			}
	}
	
	if (history_chart == false) history_chart =new google.visualization.AreaChart(document.getElementById('history'));

	history_chart.draw( data, history_chart_options );
}		

//---------------------------------------------------------------------------------------------
function update_stats( obj ) {
	console.log( "Update stats" );
	
	if (init.stats) {
		init.stats =false;
		
		var html ='';
		
		for (name in stats_title) {
			html +=`
<tr>
	<th>${stats_title[name]}</th>
	<th></th>
	</tr>
<tr>
	<td id='number_${name}' class='${name}'>0</td>
	<td id='percent_${name}' vertical-align='middle' class='percent'>&nbsp;</td>
	</tr>`;
		}
				
		$('#stats').html( `<table class='stats'>${html}</table>` );
	}
			
	for (id in obj) {
		val =obj[id];
		$('#number_'+id).html( val );
		
		percent ='';
		switch (id) {
			case 'a': 
				break;
				
			case 'y':
				val =obj.g +obj.y +obj.r;
				
			case 'nofiles':
			case 'files':
				p =val *100 /ndots;
				percent =Math.round( p );

//				console.log(`${id} - ${p} - ${percent}`);

				if (p && p < 1) percent ='<small>&lt;</small> 1<small>%</small>'; 
				else if (percent) percent +='<small>%</small>';
				break;
				
			case 'g':
				percent =Math.round( obj.qaok *100 /val );
				if (percent) percent +='<small>%</small>';
				break;
				
			case 'r':
				percent =Math.round( obj.processed *100 /(ndots -obj.nofiles) );
				if (percent) percent =`(${percent}<small>% of available</small>)`;
				break;
		}
		
		if (percent) $('#percent_'+id).html( percent );		
	}	
}	


//---------------------------------------------------------------------------------------------
function update_edots( obj ) {
	console.log( "Update edots" );
	
	if (init.edots) {
		console.log( "   Draw dots" );
		init.edots =false;
		
		var i =0;
		var html ='';
		
		var canvas_size =Math.min( cfg.col1w, cfg.pageh );
	
		dot_size =Math.floor( canvas_size /Math.sqrt( ndots ));
		dots_x_row =Math.ceil( canvas_size /dot_size );
		
//		console.log( `dot_size =${dot_size}; dots_x_row =${dots_x_row}; ndots =${ndots};` );
	
		for (paper_id in obj) {
			if (!i) html +="<tr>";
	
			status =obj[paper_id];
			
			if (status != 'removed') {
				if (status == '') status ='nofiles';
		
				html +=`<td class='b_${status}' title='${paper_id}' id='${paper_id}'></td>`;
				if (i < dots_x_row) i ++;
				else {
					html +="</tr>";
					i =0;
				}
			}
		}
		
		$('#edots').html( `<table class='edots'>${html}</table>` );
		$('table.edots td').css({ 'width': `${dot_size}px`, 'height': `${dot_size}px` });
	
	} else {
		for (paper_id in obj) {
			status =obj[paper_id];
			if (status == '') status ='nofiles';
			$('#'+paper_id).attr( 'class', `b_${status}` );
		}						
	}
}	


//---------------------------------------------------------------------------------------------
function update_editors( obj ) {
	console.log( `Update editors` );

	var eds =Object.keys( obj );
	if (eds.length < 4) {
		console.log( "  No data!" );
		return;
	}
		
	
	var chart_scale;
	
	var j =eds.length;
	var ne =j > 8 ? 9 : j;
	var tm1, tm2;
	html ="<tr><th colspan='2'>Editors Top 10</th></tr>";
	
	var n =1;
	for (name in editors) {	
		ed =editors[name];
		val =ed.complete;
		key =name.split(' ')[0]+'&nbsp;'+val;
		

		if (val > 0) {
			if (n == 1) chart_scale =600 /val;

//			console.log( `${name} ${val} | ${ed.dots.g} | ${ed.dots.y} | ${ed.dots.r}` );

			switch (n) {
				case 1: medal ='🥇'; break;
				case 2: medal ='🥈'; break;
				case 3: medal ='🥉'; break;
				default: medal ='';
			}

			html +=`
<tr><td class='p${n}'><small>${medal}</small>${key}</td>
	<td><table class='gyr' width='${(chart_scale *val)}px'><tr>`;

			if (ed.dots.g > 0) html +=`<td width='${ed.dots.g}%' class='b_g'>&nbsp;</td>`;
			if (ed.dots.y > 0) html +=`<td width='${ed.dots.y}%' class='b_y'>&nbsp;</td>`;
			if (ed.dots.r > 0) html +=`<td width='${ed.dots.r}%' class='b_r'>&nbsp;</td>`;

			html +=`
	</tr></table></td>
	</tr>`;

			n ++;
		}

		rates_page_ok =true;
	}
	
	if (rates_page_ok) $('#rates_editors').html( `<table class='rates rates_editors'>${html}</table>` );
}


//---------------------------------------------------------------------------------------------
function update_editors_qa( obj ) {
	console.log( `Update editors QA` );

	var eds =Object.keys( obj ).sort().reverse().slice( 0, 10 );
	if (eds.length < 4) {
		console.log( "  No data!" );
		return;
	}
		
	var chart_scale;
	
	var j =eds.length;
	var ne =j > 9 ? 10 : j;
	var tm1, tm2;
	var html ="<tr><th colspan='2'>Editors QA Top 10</th></tr>";
	
	var n =0;
	for (n =0; n <ne; n ++) {	
		ed =obj[eds[n]];
		val =ed.qa;
		key =ed.name.split(' ')[0]+'&nbsp;'+val;

		if (val > 0) {
			if (n == 0) chart_scale =600 /val;

			switch (n +1) {
				case 1: medal ='🥇'; break;
				case 2: medal ='🥈'; break;
				case 3: medal ='🥉'; break;
				default: medal ='';
			}

			html +=`
<tr><td class='p${n+1}'><small>${medal}</small>${key}</td>
	<td>
		<table class='gyr' width='${(chart_scale *val)}px'><tr><td class='b_qaok'>&nbsp;</td></tr></table>
		</td>
	</tr>`;
		}

		rates_page_ok =true;
	}
	
	if (rates_page_ok) $('#rates_editors_qa').html( `<table class='rates rates_editors'>${html}</table>` );
}


//---------------------------------------------------------------------------------------------
function update_rates( obj ) {
	console.log( "Update rates" );
	
	var tms =Object.keys( obj ).slice( -9 );
	
//	console.dir( tms );

	var j =tms.length;
//	var ne =j > 8 ? 9 : j;
	var tm1, tm2;
	var html ='';
	var chart_scale =15;
	var serie =[];
	
	for (var i =j -1; i > 0; i --) {	
		tm2 =tms[i];
		tm1 =tms[i -1];
		
//		console.log( `${i} ${tm1} ${tm2}` );

		val =obj[tm2].processed -obj[tm1].processed;
		
		serie[ parseInt(tm2.substr( 11, 12 )) ] =val;
		
		if (val > 0) rates_page_ok =true;
	}
	
	$('#rates').html( rates_chart( '', tms[j-1].substr( 0, 10 ), serie, 900 ));
	
	
	
	var days_rates =[], days_rates_qaok =[], day;
	
	for (tm in obj) {
		day =tm.substr( 5, 5 );
		days_rates[day] =obj[tm].processed; 
		days_rates_qaok[day] =obj[tm].qaok; 
	}
	
	var days =Object.keys( days_rates );
	
	j =days.length;
	ne =j > 8 ? 9 : j;
	var day1, day2;
	
	serie =[];
	for (var i =1; i <ne; i++) {	
		day2 =days[j -i];
		day1 =days[j -i -1];
		serie[ day2 ] =days_rates[day2] -days_rates[day1];
	}
	
	$('#rates_days').html( rates_chart( 'rates_days', 'Papers Daily Rates', serie, 900 ));
	
	
	serie =[];
	for (var i =1; i <ne; i++) {	
		day2 =days[j -i];
		day1 =days[j -i -1];
		serie[ day2 ] =days_rates_qaok[day2] -days_rates_qaok[day1];
	}
	
	$('#rates_days_qaok').html( rates_chart( 'rates_days rates_days_qaok', 'QA Daily Rates', serie, 900 ));
}


//---------------------------------------------------------------------------------------------
function rates_chart( _class, _title, _serie, _max_width ) {
	var html =`<tr><th colspan='2'>${_title}</th></tr>`;
	
	var max =0;
	for (key in _serie) {
		val =_serie[key];
		if (val > max) max =val;
	}

	var scale =_max_width /max;

	var zero_code;

	for (key in _serie) {
		val =_serie[key];
	
		switch (key) {
			case '12':
				zero_code =String.fromCodePoint(0x1F374);
				break;
				
			default:
				zero_code ="&sdot;&sdot;&sdot;";
				break;
		}

		html +=`<tr><td>${key}</td>`
			+(val > 0 ? `<td style='background-size:${(scale *val)}px 100%;'>${val}</td>` : `<td>${zero_code}</td>`)
			+"</tr>";
	}

	return `<table class='rates ${_class}'>${html}</table>`;
}




//-----------------------------------------------------------------------------
function pad( number ) {
 if (number < 10) return '0' + number;
 return number;
}

//-----------------------------------------------------------------------------
function pad100( number ) {
 if (number < 10) return '00' + number;
 if (number < 100) return '0' + number;
 return number;
}

google.load('visualization', '1', {packages: ['corechart']});
google.setOnLoadCallback( load_data );
