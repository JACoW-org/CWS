<?php

// 2019.09.06 bY Stefano.Deiuri@Elettra.Eu

$cws_config =array(
	'global' =>array(	
		'conf_name'			=>'', // IPAC XX
		'conf_url'			=>'', // https://ipac_xx.org/
		'spms_url'			=>'', // https://spms.kek.jp/pls/ipac_xx
		'spms_passphrase'	=>'', 
		'cws_timezone'		=>'', // Australia/Melbourne
		
		'root_url'			=>'', // https://www.test.eu/ipac_xx
		'root_path'			=>'', // /var/www/html/ipac_xx';

		'location'			=>'', // Malmö, Sweden
		'date_start'		=>'', // 2099-05-19
		'date_end'			=>'', // 2099-05-23

		'data_path'			=>'{root_path}/data',
		'out_path'			=>'{root_path}/html',
		'tmp_path'			=>'{root_path}/tmp',
		
		'cron_enabled'		=>false,
		
		'wget_options'		=>'-q',
		
		'debug'				=>false,
		'colored_output'	=>false,
		'verbose'			=>2,

		'chart_type'		=>'LineChart', // LineChart or AreaChart
		'chart_width'		=>800,
		'chart_height'		=>300,
		
		// Colors
		'color_primary'		=>'#0062a3',
		'color_secondary'	=>'#d73d06',
		'color_r' 			=>'#FF4136',
		'color_y' 			=>'#FFDC00',
		'color_g' 			=>'#2ECC40',
		'color_a' 			=>'#990099',
		'color_nofiles' 	=>'#555555',
		'color_files' 		=>'#7FDBFF',
		'color_removed' 	=>'#000000',
		'color_qaok'		=>'#0074D9',
		
		// Labels
		'label_files'		=>'Ready for processing',
		'label_a'			=>'Assigned to an Editor',
		'label_g'			=>'Paper successfully processed',
		'label_y'			=>'Please check your e-mail',
		'label_r'			=>'Please check your e-mail',
		'label_nofiles' 	=>'No valid files uploaded yet'
	),
	
	//-------------------------------------------------------------------------------------------------
	'data_bak' =>array(
		'name'			=>'Data Backup',
		'cron'			=>'*:00',
	),

	//-------------------------------------------------------------------------------------------------
	'spms_importer' =>array(
		'name'				=>'SPMS Importer',
		'cron'				=>'*:00',
		
		'wget'				=>true,
		'skip_sessions'			=>false,
		
		'tmp_path'			=>'{tmp_path}/spms',
		
		// out
		'abstracts'			=>'{app_data_path}/abstracts.json',
		'programme'			=>'{app_data_path}/programme.json',
		'posters'			=>'{app_data_path}/posters.json',
		'po'				=>'{app_data_path}/po.json',
		'citations'			=>'{out_path}/citations.csv',
		'transp'			=>'{out_path}/transparencies.csv'
	),
	
	//-------------------------------------------------------------------------------------------------
	'spms_stats_importer' =>array(
		'name'				=>'SPMS Statistics Importer',
		'cron'				=>'*:*',
		
		'edot_xml_url'			=>'{spms_url}/edot.xml',
		'editors_xml_url'		=>'{spms_url}/rpt_activity.xml',
		
		// in
		'in_po'				=>'{data_path}/po.json',

		// out
		'editors_xml'			=>'{tmp_path}/editors.xml',
		'editors'			=>'{data_path}/editors.json',
		'edot_xml'			=>'{tmp_path}/edot.xml',
		'papers_history'		=>'{data_path}/papers_history.json',
		'edot'				=>'{data_path}/edots.json',
		'stats'				=>'{data_path}/stats.json',
		'stats_last'			=>'{data_path}/stats_last.json'
	),
		
	//-------------------------------------------------------------------------------------------------
	'make_colors_css' =>array(
		'name'				=>'Colors Style Sheet',
		'cron'				=>'*:05',
	),

	//-------------------------------------------------------------------------------------------------
	'make_chart_abstracts' =>array(
		'name'				=>'Chart Abstracts Submission',
		'cron'				=>'*:05',
		
		'xtract'			=>'abstractsubmissions',
		'y_title'			=>'Abstracts',
		'startdate'			=>false, // ex. Y-m-d '2017-1-1', m =(month -1) 1 = feb
		
		// in
		'chart_js'			=>'chart.js',
		'chart_html'		=>'chart.html',

		// out
		'out_js'			=>'chart_abstracts.js',
		'out_html'			=>'chart_abstracts.html'
	),
		
	//-------------------------------------------------------------------------------------------------
	'make_chart_papers' =>array(
		'name'				=>'Chart Papers Submission',
		'cron'				=>'*:05',
		
		'xtract'			=>'filesuploaded,papercode',
		'y_title'			=>'Papers',
		'startdate'			=>false,
		
		// in		
		'chart_js'			=>'chart.js',
		'chart_html'		=>'chart.html',
		
		// out
		'out_js'			=>'chart_papers.js',
		'out_html'			=>'chart_papers.html'
	),		
		
	//-------------------------------------------------------------------------------------------------
	'make_chart_registrants' =>array(
		'name'				=>'Chart Registrants',
		'cron'				=>'*:05',
		
		'xtract'			=>'regstats',
		'y_title'			=>'Registrants',
		'startdate'			=>false,
		
		// in		
		'chart_js'			=>'chart.js',
		'chart_html'		=>'chart.html',

		// out
		'out_js'			=>'chart_registrants.js',
		'out_html'			=>'chart_registrants.html'
	),		
		
	//-------------------------------------------------------------------------------------------------
	'make_page_participants' =>array(
		'name'				=>'Page Registrants',
		'cron'				=>'*:05',
		
		// chart
		'xtract'			=>'regstats',
		'y_title'			=>'Registrants',
		'startdate'			=>false,

		// list
		'xtract2'			=>'attendees',
		'chart_var'			=>'Registrants',
		
		// in		
		'chart_js'			=>'chart.js',
		'template_html'		=>'template.html',
		'css'				=>'participants.css',
		
		// out
		'out_js'			=>'chart_participants.js',
		'out_css'			=>'participants.css',		
		'out_html'			=>'participants.html'
	),
		
	//-------------------------------------------------------------------------------------------------
	'make_page_programme' =>array( 
		'name'			=>'Programme',
		'cron'			=>'*:05',
		
		'img_path'		=>'programme/images',
		'base_url'		=>'programme.php',
		'tab_w'			=>" width='750'",
		
		'default_page'	=>'html/programme.php',
		
		'sessions'		=>'collapsed',
		
		'tsz_adjust'	=>0,
	
		// in
		'abstracts'		=>'{app_data_path}/abstracts.json',
		'programme'		=>'{app_data_path}/programme.json',

		// out
		'out_path'		=>'{out_path}/programme',
		'ics'			=>'{app_out_path}/programme.ics'
	),
		
	//-------------------------------------------------------------------------------------------------
	'app_paper_status' =>array(
		'name'			=>'App Paper Status',

		'echo_mode'		=>'web',

		'data_path'		=>'{data_path}/{app}',
		'tmp_path'		=>'{tmp_path}/{app}',

		'default_page'	=>'{app}/index.php',
		
		'colors_css'	=>true,
		
		'label_removed'	=>'Removed',


		// in
		'in_template_html'	=>'template.html',
		'in_po'			=>'{data_path}/po.json',
		'in_edot'		=>'{data_path}/edots.json',

		// out
		'log'			=>'{app_data_path}/usage.log'
	),
		
	//-------------------------------------------------------------------------------------------------
	'app_poster_police' =>array(
		'name'			=>'App Poster Police',
		'dummy_mode'	=>false,
		'pp_manager'	=>false, // PosterPolice PersonID
		'password'		=>false,

		'verbose'		=>false,
		'echo_mode'		=>'web',
		
		'data_path'		=>'{data_path}/{app}',

		'default_page'		=>'{app}/index.php',
		
		// in
		'in_pp'			=>'{data_path}/posters.json',
		
		// out
		'sync_url'		=>'{spms_url}/xtract.posterpolicesetstatus'
	),
	
	//-------------------------------------------------------------------------------------------------
	'page_edots' =>array(
		'name'				=>'Paper Processing Status (Dotting Board)',

		'echo_mode'			=>'web',
		
		'default_page'		=>'{app}/index.html',
		
		'colors_css'		=>true,		
		
		'change_page_delay' =>10, // seconds
		'reload_data_delay' =>120, // seconds			
		'board_rows'		=>false,
		'board_cols'		=>false,

		'paper_status_url' 	=>false,
		'paper_status_qrcode' 	=>false,

		// in
		'in_edot'			=>'{data_path}/edots.json'
	),	
		
	//-------------------------------------------------------------------------------------------------
	'page_po_status' =>array(
		'name'			=>'Proceeding Office Status Page',

		'echo_mode'		=>'web',

		'default_page'	=>'{app}/index.html',

		'colors_css'	=>true,
		
		'history_date_start' =>false,
		
		'label_g'		=>'GREEN DOT (successfully processed)',
		'label_y'		=>'YELLOW DOT (wait author approval)',
		'label_r'		=>'RED DOT (unsuccessfully processed)',
		
		// in
		'in_editors'	=>'{data_path}/editors.json',
		'in_edot'		=>'{data_path}/edots.json',
		'in_stats'		=>'{data_path}/stats.json'
	),
		
	//-------------------------------------------------------------------------------------------------
	'barcode' =>array(
		'name'			=>'BarCode Page',
		
		'echo_mode'		=>'web',
		
		'apk'			=>'JACoW_BarCode.apk',
		
		'data_url'		=>'{root_url}/data/{app}', 
		'qrcode_url'	=>'{root_url}/html/{app}', 

		'default_page'	=>'{app}/index.php',
		
		// out
		'out_path'		=>'{out_path}/{app}',
		'qrcode_path'	=>'{out_path}/{app}',
		'data_path'		=>'{data_path}/{app}',
		'log'			=>'{app_data_path}/usage.log',
		
		// in
		'template_html'	=>'template.html',
		'po'			=>'{data_path}/po.json'
	)
	
	);

if (!$cws_config['global']['root_path']) require( 'conference-config.php' );

define( 'ROOT_PATH', $cws_config['global']['root_path'] );


	
//----------------------------------------------------------------------------
function require_lib( $_name, $_version ) {
 $fname =ROOT_PATH .'/libs/' .$_name .'-' .$_version .'.lib.php';
 
 if (!file_exists($fname)) {
	echo "\n\nERROR: unable to load lib $_name-$_version\n\nPlease check the ROOT_PATH!\n\n";
 }
 
 require_once( $fname );
}
	
?>
