var sopen =false;
var aopen =false;

function ms( _s, _all ) {
	if (aopen) {
		$(aopen).innerHTML ='';
		$(aopen).style.display ='none';
		aopen =false;
	}

	x =_all.split(',');

	if (sopen == _s) _s =false;
	for (i =0; i <x.length; i ++) {
		if (x[i] != 'EMPTY') $(x[i]).style.display =(x[i] == _s) ? 'block' : 'none';
	}
	sopen =_s;
}

function ab( _a ) {
	if (aopen) {
		$(aopen).innerHTML ='';
		$(aopen).style.display ='none';
		if (aopen == _a) {
			aopen =false;
			return;
		}
	}

	aopen =_a;
	$(aopen).style.display ='block';

	new Ajax.Updater( 
		_a, 
		'ScientificProgramme/abstract.' +_a +'.html'
		);

	aopen =_a;
}
