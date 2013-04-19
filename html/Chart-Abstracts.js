google.load('visualization', '1', {packages: ['corechart']});

function drawVisualization() {
 var data = new google.visualization.DataTable();
 data.addColumn('date', 'Date');
 data.addColumn('number', 'Number of Abstracts');

 data.addRow([new Date(2012,3,16),1]);
 data.addRow([new Date(2012,4,2),2]);
 data.addRow([new Date(2012,4,22),3]);
 data.addRow([new Date(2012,4,28),6]);
 data.addRow([new Date(2012,4,29),8]);
 data.addRow([new Date(2012,4,30),17]);
 data.addRow([new Date(2012,4,31),22]);
 data.addRow([new Date(2012,5,1),29]);
 data.addRow([new Date(2012,5,2),32]);
 data.addRow([new Date(2012,5,8),33]);
 data.addRow([new Date(2012,5,13),34]);
 data.addRow([new Date(2012,5,15),35]);
 data.addRow([new Date(2012,5,20),37]);
 data.addRow([new Date(2012,5,25),40]);
 data.addRow([new Date(2012,5,26),41]);
 data.addRow([new Date(2012,9,21),42]);
 data.addRow([new Date(2012,9,27),43]);
 data.addRow([new Date(2012,9,28),44]);
 data.addRow([new Date(2012,9,31),46]);
 data.addRow([new Date(2012,10,1),47]);
 data.addRow([new Date(2012,10,2),48]);
 data.addRow([new Date(2012,10,4),51]);
 data.addRow([new Date(2012,10,6),56]);
 data.addRow([new Date(2012,10,7),57]);
 data.addRow([new Date(2012,10,8),58]);
 data.addRow([new Date(2012,10,10),60]);
 data.addRow([new Date(2012,10,11),64]);
 data.addRow([new Date(2012,10,12),66]);
 data.addRow([new Date(2012,10,13),70]);
 data.addRow([new Date(2012,10,14),71]);
 data.addRow([new Date(2012,10,15),74]);
 data.addRow([new Date(2012,10,16),75]);
 data.addRow([new Date(2012,10,17),76]);
 data.addRow([new Date(2012,10,19),83]);
 data.addRow([new Date(2012,10,20),88]);
 data.addRow([new Date(2012,10,21),93]);
 data.addRow([new Date(2012,10,22),97]);
 data.addRow([new Date(2012,10,23),104]);
 data.addRow([new Date(2012,10,24),107]);
 data.addRow([new Date(2012,10,25),113]);
 data.addRow([new Date(2012,10,26),131]);
 data.addRow([new Date(2012,10,27),152]);
 data.addRow([new Date(2012,10,28),178]);
 data.addRow([new Date(2012,10,29),203]);
 data.addRow([new Date(2012,10,30),258]);
 data.addRow([new Date(2012,11,1),269]);
 data.addRow([new Date(2012,11,2),285]);
 data.addRow([new Date(2012,11,3),523]);
 data.addRow([new Date(2012,11,4),1127]);
 data.addRow([new Date(2012,11,5),1745]);
 data.addRow([new Date(2012,11,6),1794]);
 data.addRow([new Date(2012,11,7),1812]);
 data.addRow([new Date(2012,11,8),1816]);
 data.addRow([new Date(2012,11,9),1820]);
 data.addRow([new Date(2012,11,10),1824]);
 data.addRow([new Date(2012,11,11),1826]);
 data.addRow([new Date(2012,11,12),1828]);
 data.addRow([new Date(2012,11,13),1838]);
 data.addRow([new Date(2012,11,21),1839]);
 data.addRow([new Date(2013,0,6),1840]);
 data.addRow([new Date(2013,0,9),1841]);
 data.addRow([new Date(2013,1,6),1842]);
 data.addRow([new Date(2013,1,14),1843]);
 data.addRow([new Date(2013,1,15),1849]);
 data.addRow([new Date(2013,1,16),1852]);
 data.addRow([new Date(2013,1,18),1861]);
 data.addRow([new Date(2013,1,21),1863]);
 data.addRow([new Date(2013,1,22),1866]);
 data.addRow([new Date(2013,1,25),1867]);
 data.addRow([new Date(2013,1,26),1871]);
 data.addRow([new Date(2013,1,28),1872]);


 new google.visualization.LineChart(document.getElementById('chart_Abstracts')).
	draw(data, {curveType: 'function', 
		width: 550, height: 200, legend: 'none', colors: ['#990000'],
		vAxis: {title: 'Abstracts', maxValue: 1872 }}
		);
}

google.setOnLoadCallback(drawVisualization);
