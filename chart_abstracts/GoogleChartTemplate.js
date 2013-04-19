google.load('visualization', '1', {packages: ['corechart']});

function drawVisualization() {
 var data = new google.visualization.DataTable();
 data.addColumn('date', 'Date');
 data.addColumn('number', 'Number of ${var}');
${maxvaluecolumn}
${addrow}

 new google.visualization.LineChart(document.getElementById('chart_${var}')).
	draw(data, {curveType: 'function', 
		width: ${width}, height: ${height}, legend: 'none', colors: ['#990000'],
		vAxis: {title: '${var}', maxValue: ${maxvalue} }}
		);
}

google.setOnLoadCallback(drawVisualization);
