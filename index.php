<?php
include "conf/config.php";
require_once 'Cache/Lite.php';

$id=mktime(0, date("i")/10, date("H"), date("m")  , date("d"), date("Y"));
if (isset($_GET['id'])){ $id=time();}

$opts = array(
    'cacheDir' => 'c:/tmp',
    'automaticSerialization' => true,
    'lifeTime' => 600 );

$globcount = microtime(true);

function dbconnect(){
    include('conf/config.php');
    $link = mysqli_connect($config['MYSQL']['HOST'], $config['MYSQL']['USER'], $config['MYSQL']['PWD']) or die("Could not connect : " . mysqli_error($link));
    mysqli_select_db($link, $config['MYSQL']['DBNAME']) or die("Could not select database");
    $GLOBALS['link']=$link;
}

function dbas(){
    include "conf/config.php";
    $linkas = mysqli_connect($config['MYSQLas']['HOST'], $config['MYSQLas']['USER'], $config['MYSQLas']['PWD'], $config['MYSQLas']['DBNAME']) or die("Could not connect : " . mysqli_error($linkas). "<br> mysql connect error: ".mysqli_connect_error($linkas));
    mysqli_select_db($linkas, $config['MYSQLas']['DBNAME']) or die("Could not select database");
    $GLOBALS['linkas']=$linkas;
}

function get_user_info($userid) { 
    $query="SELECT `firstname`,`lastname`,`username` FROM `swstaff` where `staffid` = '".$userid."';";
    dbconnect();
    global $link;
    $starttime = microtime(true);
    $result = mysqli_query($link, $query, MYSQLI_USE_RESULT) or die("Query failed (info)[$link] : " . mysqli_error($link));
    $endtime = microtime(true); $duration = $endtime - $starttime; 
    $row=mysqli_fetch_row($result);
    return "".$row['0']." ".$row['1']." (".$row['2'].")<!-- ".$userid." ($duration ms) -->";
}

function get_user_chat($userid) {
    $query="select count(`chatobjectid`) from `swchatobjects` where `chatstatus` = '3' and `staffpostactivity` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) and `staffid` = '".$userid."';";
    dbconnect();
    global $link;
    $starttime = microtime(true);
    $result = mysqli_query($link, $query, MYSQLI_USE_RESULT) or die("Query failed (chat) : " . mysqli_error($link));
    $endtime = microtime(true); $duration = $endtime - $starttime;
    $row=mysqli_fetch_row($result);
    return $row['0']."<!-- $duration -->";
}

function get_user_ticket($userid) {
    $query="select count(*) from `swticketauditlogs` where `dateline` > unix_timestamp(curdate()) and `creatorid` = '".$userid."' and `actionmsg` = \"Ticket status changed from: Open to: In Progress\";";
    dbconnect();
    global $link;
    $starttime = microtime(true);
    $result = mysqli_query($link,$query, MYSQLI_USE_RESULT) or die("Query failed (ticket): " . mysqli_error($link));
    $endtime = microtime(true); $duration = $endtime - $starttime;
    $row=mysqli_fetch_row($result);
    return $row['0']."<!-- $duration -->";
}

function get_user_phone($userid) {
    include "conf/config.php";
    $query="SELECT count(*)  FROM `cdr` WHERE `calldate` > curdate() and `disposition` = 'ANSWERED' and `dstchannel` like 'SIP/".$sip[$userid]."%'";
    dbas();
    global $linkas;
    $starttime = microtime(true);
    $result = mysqli_query($linkas, $query, MYSQLI_USE_RESULT) or die("Query failed (chat) : " . mysqli_error($linkas)."|||".mysqli_connect_error($linkas));
    $endtime = microtime(true); $duration = $endtime - $starttime;
    $row = mysqli_fetch_row($result);
    return $row[0]."<!-- $duration -->";
}

$Cache_Lite = new Cache_Lite($options);

if ($design = $Cache_Lite->get($id)) {
echo($design);
}
else {

$design="<table border=\"2\" cellpadding=\"2\" width=\"100%\">";
$design.="<tr> <td width=\"20%\" >user</td> <td>chat</td> <td>ticket</td> <td>phone</td> </tr>";

foreach (explode(",", $config['enabledID']) as $user) {
$chat=get_user_chat($user);
$ticket=get_user_ticket($user);
$phone=get_user_phone($user);

$design.="<tr><td>".get_user_info($user)."</td>";
if ($chat==0)	{$design.="<td bgcolor=\"#FFD9D9\">".$chat."</td>";}else { $design.="<td>".$chat."</td>";} 
if ($ticket==0)	{$design.="<td bgcolor=\"#FFD9D9\">".$ticket."</td>";}else {$design.="<td>".$ticket."</td>";} 
if ($phone==0)	{$design.="<td bgcolor=\"#FFD9D9\">".$phone."</td>";}else {$design.="<td>".$phone."</td>";}
$design.=" </tr>\r\n";
}

$design.="</table>";
$endglobcount = microtime(true); $globduration = $endglobcount - $globcount; 
$design.= "generated $globduration<br>last update: ".gmdate("Y-m-d H:i:s ",$id);
echo $design;
$Cache_Lite->save($design);
}