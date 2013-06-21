<?php
#include "conf/config.php";

    $globcount = microtime(true);

function dbconnect(){
    include('conf/config.php');
    $link = mysqli_connect($config['MYSQL']['HOST'], $config['MYSQL']['USER'], $config['MYSQL']['PWD']) or die("Could not connect : " . mysqli_error($link));
    mysqli_select_db($link, $config['MYSQL']['DBNAME']) or die("Could not select database");
    $GLOBALS['link']=$link;
}

function get_user_info($userid) { 
    $query="SELECT `firstname`,`lastname`,`username` FROM `swstaff` where `staffid` = '".$userid."';";
    dbconnect();
    global $link;
        $starttime = microtime(true);
    $result = mysqli_query($link, $query, MYSQLI_USE_RESULT) or die("Query failed (info)[$link] : " . mysqli_error($link));
        $endtime = microtime(true); $duration = $endtime - $starttime; 
    $row=mysqli_fetch_row($result);
    return "".$row['0']." ".$row['1']." (".$row['2'].")<!-- $userid ($duration ms) -->";
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
    return "no data";
}

$design="<table border=\"2\" cellpadding=\"2\" width=\"100%\">";
$design.="<tr> <td width=\"20%\" >user</td> <td>chat</td> <td>ticket</td> <td>phone</td> </tr>";

foreach (explode(",", $config['enabledID']) as $user) {
    $design.="<tr><td>".get_user_info($user)."</td><td>".get_user_chat($user)."</td> <td>".get_user_ticket($user)."</td> <td>".get_user_phone($user)."</td> </tr>";
}

$design.="</table>";
echo $design;
    $endglobcount = microtime(true); $globduration = $endglobcount - $globcount; echo "generated $globduration";
