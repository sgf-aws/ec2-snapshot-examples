#!/usr/bin/php -f
<?php

$mount_path = $argv[1];

if (trim($mount_path) == '') {
  echo 'Syntax: ' . $argv[0] . ' <path>' . "\n";
  exit();
}

$cmd = "mount | grep ' on $mount_path ' | awk '{print $1}'";
$mount_device = trim(shell_exec($cmd));
if (trim($mount_device) == '') {
  echo 'Error_Mountpoint_Does_Not_Exist' . "\n";
  exit();
}
//echo date('Y-m-d H:i:s') . ' + Mount Device: ' . $mount_device . "\n";

// Retrieve current IPv4 address for eth0 (e.g. 172.1.2.3)
$cmd = "/sbin/ifconfig eth0 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
$my_ipv4 = trim(shell_exec($cmd));
//echo date('Y-m-d H:i:s') . ' + Instance IP: ' . $my_ipv4 . "\n";

// Query instance by IPv4 address. This should only return ONE result.
$cmd = 'aws ec2 describe-instances --filters "Name=private-ip-address,Values=' . $my_ipv4 . '"';
$instances = json_decode(shell_exec($cmd));

// Extract Instance ID
$my_instanceId = trim($instances->Reservations[0]->Instances[0]->InstanceId);
//echo date('Y-m-d H:i:s') . ' + Instance ID: ' . $my_instanceId . "\n";

// Query volumes attached to this instance
$cmd = 'aws ec2 describe-volumes --filters "Name=attachment.instance-id,Values=' . $my_instanceId . '"';
$volumes = json_decode(shell_exec($cmd));

// Build list of Volume IDs associated with this instance
foreach($volumes->Volumes as $volume) {
	$my_volumeId = $volume->VolumeId;
	//echo date('Y-m-d H:i:s') . ' + Volume ID: ' . $my_volumeId . "\n";

	// /dev/sdb -> /dev/xvdb
	$my_volumeDevice = $volume->Attachments[0]->Device;
	if (substr($my_volumeDevice,0,7) == '/dev/sd')
		$my_volumeDevice = '/dev/xvd' . substr($my_volumeDevice,7,1);
	//echo date('Y-m-d H:i:s') . ' + Volume Device: ' . $my_volumeDevice . "\n";

	//echo "COMPARE:" . substr($mount_device,0,9) . " VS " . substr($my_volumeDevice,0,9) . "\n";
	if (substr($mount_device,0,9) == substr($my_volumeDevice,0,9)) {
        	//echo "MATCH:" . substr($mount_device,0,9) . "\n";
		echo $my_volumeId . "\n";
	}
}

