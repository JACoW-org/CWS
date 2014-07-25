CWS - JACOW Conference Website Scripts

The JACoW Conference Website Scripts (written by Stefano Deiuri) is a set of PHP scripts that extracts data from SPMS and generates a series of HTML and JavaScript files as an output. 


= The structure of the scripts is as follows

jacow-spms-cws
├── chart_abstracts/ - Directory with files for generating the “Abstracts” chart
├── chart_papers/ - Directory with files for generating the “Papers” chart
├── chart_registrants/ - Directory with files for generating the “Registrants” chart
├── participants/ - Directory with files for generating the “Participants” page
├── scientific_programme/ - Directory with files for generating the “Scientific Program” page
├── html/ - This directory contains by default all the output files
├── libs/ - Common .php files required by all the scripts
├── tmp/ - Temporary directory for e.g. data downloaded from SPMS
├── README - This readme
├── index.html - An index file with URLs to all content generated into the “html” directory
└── conference.php - script configuration information (like SPMS location and passphrases, output directory)


= How to use the scripts

Each of the directories for generating a page or a chart contains a file “make.php”. This file is NOT INTENDED for executing it through a webserver, it is enough to have php-cli and execute it from time to time using e.g. Cron Jobs. Depending on the type of scripts the time between executions can vary. I suggest to updated the abstracts chart every 20 minutes, other Charts like the scientific program should be generated once per day.

Example how to execute the script from the command line to generate an “Abstracts” chart:
$ cd chart_abstracts/
$ php make.php 
Get data from: http://oraweb.cern.ch/pls/ipac2014/xtract.abstractsubmissions.. OK (38 records)
Save file ../html/Chart-Abstracts.html... OK
Save file ../html/Chart-Abstracts.js... OK
$ _

After that the file “../html/Chart-Abstracts.html” can be embedded into the conference website. Example using an iframe:

<iframe src ="html/Chart-Abstracts.html" scrolling="no" width="550px" height="220px" frameborder="0" name="abstracts_chart" id="abstracts_chart">
  <p>Your Browser does not support embedded Frames (iframes).
  You can access the page here: <a href="html/Chars-Abstracts.html">Abstracts Submitted</a></p>
</iframe>

The conference.php file contains several options. One can change the output directory (“OUT_PATH”) and the temporary directory (“TMP_PATH”) of the scripts to accommodate the structure of the conferences webserver. Also the charts width and height (“CHART_WIDTH”, “CHART_HEIGHT”) can be changed to fit the conference website. 
Also the URL to the SPMS instance to extract the data and a passphrase (the same entered in SPMS) need to be placed in the conference.php file, as well as the name of the confernce.



