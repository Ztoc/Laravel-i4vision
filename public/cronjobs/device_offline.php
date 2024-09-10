<?php
/*error_reporting(E_ALL);
ini_set("display_errors", 1);*/
include("inc/functions.php");

/* TEST
$clientname = "se bruchsal";
$description = "St. Vinzenz Himedia Android Box";
$device_code = "MjAyMTEwMjAxNzEzMzgxMTQ";

$subject = "Warnung! i4Vision Device $description ist offline";
$message = "Sie sind als Supervisor hinterlegt für die Organisation: $clientname<br>Wir teilen Ihnen daher auf diesem Weg mit, dass die<br><br><table><tr><td>i4Vision Device</td><td style='width:10%'></td><td>$description</td></tr><tr><td>mit der Kennung</td><td style='width:10%'></td><td>$device_code</td></tr></table><br><br>aktuell keinen Heartbeat mehr sendet.<br>Möglicherweise besteht keine Internetverbindung.<br>Bitte prüfen Sie ob mit dem Gerät alles in Ordnung ist und schalten Sie ggf. einmal aus und nach 20 sec. wieder ein.<br><br>Es erfolgt keine weitere Warnung mehr bis das Gerät wieder online ist.<br><br><br>i4Vision - Supervisor Service";
sendmail("web@i4vision.de","i4Vision","rs@goering.de",$subject,$message);
die();*/




/* Ersetzt durch View
$query = "SELECT device.id, device.`description`, device.`device_code`, clients.description as clientname, clients.supervisor_email
from device
cross join clients
on device.client_id = clients.id
WHERE timestampdiff(MINUTE, timestamp_last_accessed, current_timestamp)> device_heartbeat_minutes*2
and current_time > device_up_time and CURRENT_TIME < device_down_time
and device_heartbeat_minutes >0
and supervisor_warning = 0";*/
$query = "SELECT * FROM view_deviceoffline";

//predump(dbselect($query));

$counter = 0; $mailsSent = 0;

$offlines = dbselect($query);
foreach($offlines as $offline){
	$counter++;
	
	$description = $offline['description'];
	$device_code = $offline['device_code'];
	$clientname = $offline['clientname'];
	$supervisor = $offline['supervisor_email'];
	
	$subject = "Warnung! i4Vision Device $description ist offline";
	$message = "Sie sind als Supervisor hinterlegt für die Organisation: $clientname<br>Wir teilen Ihnen daher auf diesem Weg mit, dass die<br><br><table><tr><td>i4Vision Device</td><td style='width:10%'></td><td>$description</td></tr><tr><td>mit der Kennung</td><td style='width:10%'></td><td>$device_code</td></tr></table><br><br>aktuell keinen Heartbeat mehr sendet.<br>Möglicherweise besteht keine Internetverbindung.<br>Bitte prüfen Sie ob mit dem Gerät alles in Ordnung ist und schalten Sie ggf. einmal aus und nach 20 sec. wieder ein.<br><br>Es erfolgt keine weitere Warnung mehr bis das Gerät wieder online ist.<br><br><br>i4Vision - Supervisor Service";

	if(sendmail("web@i4vision.de","i4Vision",$supervisor,$subject,$message)){
		$mailsSent++;
		dbexecute("update device set supervisor_warning = 1 where id = ".$offline['id']);
	}
}
//echo "Counter: ".$counter.", MailsSent:".$mailsSent;