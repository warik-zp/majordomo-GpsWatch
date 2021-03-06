<?php

global $ajax;
global $op;
global $period;
global $to;
global $from;

$colors=array('red', 'blue', 'green', 'orange', 'brown', 'gray', 'yellow', 'white');

function getDirection($bearing) {
  $cardinalDirections = array(
    array('N', 337.5, 22.5),
    array('NE', 22.5, 67.5),
    array('E', 67.5, 112.5),
    array('SE', 112.5, 157.5),
    array('S', 157.5, 202.5),
    array('SW', 202.5, 247.5),
    array('W', 247.5, 292.5),
    array('NW', 292.5, 337.5)
  );

  $count = count($cardinalDirections);

  for ($i = 0; $i < $count; $i++) {
    if ($bearing >= $cardinalDirections[$i][1] && $bearing < $cardinalDirections[$i][2]) {
      $direction = $cardinalDirections[$i][0];
      $i = $count;
    }
  }
  return $direction;
}

$qry=1;
if ($period=='week') {
    $qry.=" AND (UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(ADDED))<7*24*60*60";
    $to=date('Y-m-d');
    $from=date('Y-m-d', time()-7*24*60*60);
} elseif ($period=='month') {
    $qry.=" AND (UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(ADDED))<31*24*60*60";
    $to=date('Y-m-d');
    $from=date('Y-m-d', time()-31*24*60*60);
} elseif ($period=='custom') {
    $qry.=" AND ADDED>=DATE('".$from." 00:00:00')";
    $qry.=" AND ADDED<=DATE('".$to." 23:59:59')";
} elseif ($period=='day') {
    $qry.=" AND (UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(ADDED))<1*24*60*60";
    $to=date('Y-m-d');
    $from=date('Y-m-d', time()-1*24*60*60);
} else {
    $period='today';
    $qry.=" AND (TO_DAYS(NOW())=TO_DAYS(ADDED))";
    $to=date('Y-m-d');
    $from=$to;
}

$out['DEVICES']=SQLSelect("SELECT * FROM gw_device ORDER BY NAME");//TO_DAYS(NOW())-TO_DAYS(gpsdevices.UPDATED)<=30
$total=count($out['DEVICES']);
for($i=0;$i<$total;$i++) {
    $latest_point=SQLSelectOne("SELECT * FROM gw_log WHERE DEVICE_ID='".$out['DEVICES'][$i]['ID']."' ORDER BY ADDED DESC");
    $out['DEVICES'][$i]['LAT']=$latest_point['LAT'];
    $out['DEVICES'][$i]['LON']=$latest_point['LON'];
    $out['DEVICES'][$i]['COLOR']=$colors[$i];
}


if ($ajax) {
    
    if (!headers_sent()) {
        header ("HTTP/1.0: 200 OK\n");
        header ('Content-Type: text/html; charset=utf-8');
    }
    
    if ($op=='getmarkers') {
        $data=array();
        $markers=$out['DEVICES'];
        $total=count($markers);
        for($i=0;$i<$total;$i++) {
            $markers[$i]['HTML']="<b>".$markers[$i]['NAME']."</b></br>";
            if ($markers[$i]['ONLINE']==0)
                $markers[$i]['HTML'].="Online:No</br>";
            else
            {
                $markers[$i]['HTML'].="Online:Yes</br>";
                $markers[$i]['HTML'].="Ip:".$markers[$i]['LAST_IP']."</br>";
            }
            $markers[$i]['HTML'].="Last time online:".$markers[$i]['LAST_ONLINE']."</br>";
            if ($markers[$i]['ONHAND']==0)
                $markers[$i]['HTML'].="On hand:No</br>";
            else
                $markers[$i]['HTML'].="On hand:Yes</br>";
            $markers[$i]['HTML'].="Battery:".$markers[$i]['BATTERY']."%";
            $data['MARKERS'][]=$markers[$i];
        }
        echo json_encode($data);
    }
    
    if ($op=='getroute') {
        global $device_id;
        $device=SQLSelectOne("SELECT * FROM gw_device WHERE ID='".(int)$device_id."'");
        $log=SQLSelect("SELECT * FROM gw_log WHERE DEVICE_ID='".(int)$device_id."' AND ".$qry." ORDER BY ADDED");
        $total=count($log);
        $coords=array();
        $points=array();
        for($i=0;$i<$total;$i++) {
            $coords[]=array($log[$i]['LAT'], $log[$i]['LON']);
            $hint  = "Date: ".$log[$i]['ADDED']."<br>";
            $hint .= "Speed: ".$log[$i]['SPEED']."km/h<br>";
            $hint .= "Direction: ". getDirection($log[$i]['DIRECTION'])." (".$log[$i]['DIRECTION']." degree)<br>";
            $hint .= "Altitude:".$log[$i]['ALT']."m";
            $points[]=array($log[$i]['ID'],
                    'LAT'=>$log[$i]['LAT'],
                    'LON'=>$log[$i]['LON'],
                    'PROVIDER'=>$log[$i]['PROVIDER'] ,
                    'TITLE' => $log[$i]['ADDED'],
                    'HINT' => $hint);
        }
        $res=array();
        if ($total) {
            $res['FIRST_POINT']=$points[0];
            $res['LAST_POINT']=$points[count($points)-1];
            $res['PATH']= $coords;
            $res['POINTS']= $points;
        }
        echo json_encode($res);
    }
    
    if ($op=='getlocations') {
        $res=array();
        $res['LOCATIONS']=SQLSelect("SELECT * FROM gpslocations");
        echo json_encode($res);
    }
    
    exit;
}

$latest_point=SQLSelectOne("SELECT * FROM gpslog ORDER BY ADDED DESC");
$out['LATEST_LAT']=$latest_point['LAT'];
$out['LATEST_LON']=$latest_point['LON'];

$out['TO']=$to;
$out['FROM']=$from;
$out['PERIOD']=$period;

?>