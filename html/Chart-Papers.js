google.load('visualization', '1', {packages: ['corechart']});

function drawVisualization() {
 var data = new google.visualization.DataTable();
 data.addColumn('date', 'Date');
 data.addColumn('number', 'Number of Papers');



 new google.visualization.LineChart(document.getElementById('chart_Papers')).
	draw(data, {curveType: 'function', 
		width: 800, height: 200, legend: 'none', colors: ['#990000'],
		vAxis: {title: 'Papers', maxValue: 1870 }}
		);
}

google.setOnLoadCallback(drawVisualization);
