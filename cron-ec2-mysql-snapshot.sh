#!/bin/sh

# Place this ec2 snapshot script in daily cron folder and make executable.
# (e.g. "chmod 755 /etc/cron.daily/ec2-mysql-snapshot")
# 
# This script depends on helper scripts to lookup the EC2 Volume ID for the 
# snapshot directory and purge stale snapshots. Those scripts must also be 
# executable. Customize paths as necessary.
# 
# NOTE: This script expects your MySQL data to reside on a dedicated
#       XFS-formatted volume (e.g. /data).
#
# Provided by SGF AWS user group without warranty. Use at your own risk.

# User Variables
AWS_REGION="us-east-1"
DIR_SNAPSHOT="/data"
DIR_SNAPSHOT_VOLID=`/usr/local/sbin/ec2-get-volumeid.php $DIR_SNAPSHOT`
MYSQL_CONFIG="/root/.my.cnf"
MYSQL_MASTER_STATUS_FILE="$DIR_SNAPSHOT/mysql-master-status.txt"
LOG_EC2_SNAPSHOT_CREATE="/var/log/ec2-snapshot.log"
LOG_EC2_SNAPSHOT_PURGE="/var/log/ec2-snapshot-purge.log"

# Initiate Snapshot of MySQL Volume
/usr/local/sbin/ec2-consistent-snapshot --region $AWS_REGION --freeze-filesystem $DIR_SNAPSHOT --mysql --mysql-defaults-file $MYSQL_CONFIG --mysql-master-status-file $MYSQL_MASTER_STATUS_FILE --mysql-master-status-desc --description "$(hostname): ec2-consistent-snapshot" $DIR_SNAPSHOT_VOLID 2>&1 >> $LOG_EC2_SNAPSHOT_CREATE

# Purge Stale Snapshots
/usr/local/sbin/ec2-purge-snapshots.php 2>&1 >> $LOG_EC2_SNAPSHOT_PURGE

