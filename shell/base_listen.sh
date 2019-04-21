#!/bin/bash
#env
base_dir=/WWW/YII/
php_cmd=/usr/bin/php
#listen_pid=ps -ef|grep base_listen|grep -v grep|wc -l

var1=yii
var2=baseListen
#var string

cd  $base_dir/
#while true
#do
$php_cmd   $var1  $var2  > /dev/null 2>&1   &
$php_cmd   $var1  $var2 receiveOrder  > /dev/null 2>&1   &
$php_cmd   $var1  $var2 yiiBaseDo  > /dev/null 2>&1   &
#sleep 4
#done