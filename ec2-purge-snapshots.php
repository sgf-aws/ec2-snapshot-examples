#!/usr/bin/php -f
<?php

$cfg_max_snapshots = 2; // keep 1 complete snapshots (plus 1 new/pending snap)

echo date('Y-m-d H:i:s') . ' Purging stale snapshots (max:' . $cfg_max_snapshots . ')' . "\n";

// Retrieve current IPv4 address for eth0 (e.g. 172.1.2.3)
$cmd = "/sbin/ifconfig eth0 | grep 'inet addr:' | cut -d: -f2 | awk '{ print $1}'";
$my_ipv4 = trim(shell_exec($cmd));
echo date('Y-m-d H:i:s') . ' + Instance IP: ' . $my_ipv4 . "\n";

// Query instance by IPv4 address. This should only return ONE result.
$cmd = 'aws ec2 describe-instances --filters "Name=private-ip-address,Values=' . $my_ipv4 . '"';
$instances = json_decode(shell_exec($cmd));

// Extract Instance ID
$my_instanceId = trim($instances->Reservations[0]->Instances[0]->InstanceId);
echo date('Y-m-d H:i:s') . ' + Instance ID: ' . $my_instanceId . "\n";

// Query volumes attached to this instance
$cmd = 'aws ec2 describe-volumes --filters "Name=attachment.instance-id,Values=' . $my_instanceId . '"';
$volumes = json_decode(shell_exec($cmd));

// Build list of Volume IDs associated with this instance
foreach($volumes->Volumes as $volume) {
	$my_volumeId = $volume->VolumeId;
	echo date('Y-m-d H:i:s') . ' + Volume ID: ' . $my_volumeId . "\n";

	// Query completed snapshots attached to this volume
	$cmd = 'aws ec2 describe-snapshots --owner self --filters "Name=volume-id,Values=' . $my_volumeId . '" "Name=status,Values=completed"';
	$snapshots = json_decode(shell_exec($cmd));
	$snapshot_count = count($snapshots->Snapshots);
	echo date('Y-m-d H:i:s') . '   + Snapshot Count: ' . $snapshot_count . "\n";

	// If more than 3 completed snapshots exist, purge oldest snapshot(s)
	if ($snapshot_count > $cfg_max_snapshots) {
		foreach($snapshots->Snapshots as $snapshot) {
			$snapshot_time_U = strtotime($snapshot->StartTime);
			$snapshot_time_Ymd = date('Y-m-d H:i:s',$snapshot_time_U);
			$snapshot_times[$snapshot_time_U] = $snapshot->SnapshotId;
			echo date('Y-m-d H:i:s') . '   + Snapshot ID: ' . $snapshot->SnapshotId . ' [' . $snapshot_time_Ymd . ']' . "\n";
		}

		// Sort snapshots by snapshot time (newest to oldest)
		krsort($snapshot_times);
		//	Array
		//	(
		//		[1416822448] => snap-20f5e291
		//		[1416735644] => snap-698dc5d8
		//		[1416648757] => snap-6a59dbdb
		//		[1416562483] => snap-03d713b2
		//	)

		// Pop oldest snapshot ID off of the end of array.
		$delete_snapshot_id = array_pop($snapshot_times);
		echo date('Y-m-d H:i:s') . '   + Deleting Snapshot ID: ' . $delete_snapshot_id . "\n";

		// Purge oldest snapshot (only purge one snapshot per run)
		$cmd = 'aws ec2 delete-snapshot --snapshot-id ' . $delete_snapshot_id;
		$result = json_decode(shell_exec($cmd));
	}
}

echo date('Y-m-d H:i:s') . ' Finished purging stale snapshots' . "\n";

