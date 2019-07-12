
// 2019.05.16 bY Stefano.Deiuri@Elettra.Eu

var cfg ={
	version: 4,
	mode: 'full',
	change_page_delay: 10, // seconds
	reload_data_delay: 120, // seconds	
	cols: 12,
	rows: 36
	};
	
var active_page =0;

var ndots =0;

var data_ts =0;

var timer =0;

var n_page =0;

var edots =[];

var init ={
	cache: true,
	edots: true,
	};

$(document).ready( function() {
	if (navigator.platform.indexOf('arm') != -1) {
		console.log( 'Enable SLOW mode!' )
		cfg.mode ='slow';
	}
	
	load_data();
	show_page();
	setInterval( update_timer, 100 );
	});

//---------------------------------------------------------------------------------------------
function show_page() {
	duration =500;
	
	console.log( `Show page ${active_page}` );
	
	if (active_page) {
		switch (cfg.mode) {
			case 'slow':
				$(`div[page=${active_page}]`).hide();
				break;
			default:
				$(`div[page=${active_page}]`).fadeOut(duration);
		}
	}
	
	active_page ++;
	if (active_page == (n_page+1)) active_page =1;

	switch (cfg.mode) {
		case 'slow':
			$(`div[page=${active_page}]`).show();
			break;
		default:
			$(`div[page=${active_page}]`).delay(duration).fadeIn(duration);
	}
	
	if (n_page) $('#activepage').html( active_page ); 

	setTimeout( show_page, cfg.change_page_delay *1000 );

	timer =cfg.change_page_delay *10;
	$('#timer').css( 'left', 910 );
//	$('#timer2').css( 'top', -80 );
}

//---------------------------------------------------------------------------------------------
function update_timer() {
 var w =Math.floor((timer *20) /cfg.change_page_delay);
 var l =960 -w/2;
 $('#timer').css( 'width', w );
 $('#timer').css( 'left', l );
 
// var y =-80 +(130 - Math.floor((timer *13) /cfg.change_page_delay));
// $('#timer2').css( 'top', y );
 
 timer --;
 
 var d =new Date();
 $('#clock').html( `${pad(d.getHours())}:${pad(d.getMinutes())}<span style='color:#bbb;'>:${pad(d.getSeconds())}</span>` );
}

//---------------------------------------------------------------------------------------------
function load_data() {
	
	init.cache =false;
	
	$.getJSON( 'get_data.php', { ts: data_ts } )
		.done(function(obj) {
			if (obj.error) {
				console.log( 'DATA ERROR!' );
				setTimeout( load_data, (cfg.reload_data_delay /2) *1000 );
				return;
			}
			
			console.log( 'Load data ' +obj.ts );

			var edots_updated =false;
			
			if (obj.edots != undefined) {
				console.log( "  Process edots" );

				for (id in obj.edots) {
					status =obj.edots[id];
					
					if (status == '') status ='nofiles';
					else if (status == 'qaok') status ='g';
					
					edots[id] =status;
					
					console.log( `    ${id.substring(1)} (${status})` );

					if (status == 'removed') {
//						init.edots =true;
					}

					
					edots_updated =true;
				}
			}

			data_ts =obj.ts;
			
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
function update_edots( obj ) {
	console.log( "Update edots" );
	
	if (init.edots) {
		console.log( "   Draw dots" );
		init.edots =false;
		
		var i =0;
		var page =1;
		var html ='';
		var rows =[];
		var row_id;
		
		var dpp =cfg.cols *cfg.rows; // dot per page
	
		var empty_cell ="<dot class='empty'>&nbsp;</dot>";
	
		for (paper_id in obj) {
	
			status =obj[paper_id];
			
			if (status != 'removed') {
				if (i == dpp) {
					html ='';
					for (j =0; j <cfg.rows; j++) html +=rows[j] +(j > (cfg.rows-7) ?  empty_cell : '');
					
					$(`div[page=${page}]`).html( html );

					rows =[];
					i =0;
					page ++;
				}

/*				
				if (status == '') status ='nofiles';
				else if (status == 'qaok') status ='g';
*/				
				html +=`<dot class='b_${status}' id='${paper_id}'>${paper_id.substring(1)}</dot>`;
				
				row_id =i%cfg.rows;
				
				if (rows[ row_id ] == undefined) rows[ i%cfg.rows ] ='';
				
//				if (i >= (cfg.rows-4) && i < cfg.rows)
	

				rows[ i%cfg.rows ] +=`<dot class='b_${status}' id='${paper_id}'>${paper_id.substring(1)}</dot>`;
				
				i ++;
				
				if (dpp -i < 7) i =dpp;
			}
		}

		if (i) {
			if (i < dpp) {
				for (j =i; j <dpp; j ++) {
					if (rows[ j%cfg.rows ] == undefined) rows[ j%cfg.rows ] ='';
					rows[ j%cfg.rows ] +=empty_cell;				
				}
			}
			
			html ='';
			for (j =0; j <cfg.rows; j++) html +=rows[j];
			
			$(`div[page=${page}]`).html( html );
		}
	
		n_page =page;
		
		$('#activepage').html( 1 ); 
		$('#npages').html( `/${n_page}` ); 
	
	} else {
		for (paper_id in obj) {
			status =obj[paper_id];
/*			
			if (status == '') status ='nofiles';
			else if (status == 'qaok') status ='g';
*/			
			$(`#${paper_id}`).attr( 'class', `b_${status}` );
		}						
	}
}	


//-----------------------------------------------------------------------------
function pad( number ) {
 if (number < 10) return '0' + number;
 return number;
}
