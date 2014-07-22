
PWD=`pwd`

ROOT=/var/www/html/jacow-spms-cws
LOG=$ROOT/tmp/cron.log

echo >> $LOG
date >> $LOG
echo >> $LOG

cd $ROOT/chart_abstracts
./make.php >> $LOG 2>&1

cd $ROOT/chart_registrants
./make.php >> $LOG 2>&1

cd $PWD

