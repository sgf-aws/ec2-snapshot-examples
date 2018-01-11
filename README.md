# ec2-snapshot-examples

This sample code performs consistent snapshots on EC2 Linux server instances.

This code uses [EC2 Consistent Snapshot](https://github.com/alestic/ec2-consistent-snapshot) to flushing MySQL to disk, freeze the XFS filesystem, initiate the snapshot, then unfreeze the filesystem, then unlock MySQL tables.

This code uses helper scripts to lookup the EC2 Volume ID for the specified volume (required parameter for EC2 Consistent Snapshot) and to purge stale snapshots.

If you are not concerned about consistent snapshots, [Serverless Code](https://serverlesscode.com/post/lambda-schedule-ebs-snapshot-backups/) has published an [excellent guide](https://serverlesscode.com/post/lambda-schedule-ebs-snapshot-backups/) that describes how to schedule snapshots of all servers with a "Backup" tag. Part two of that article describes how to schedule snapshot purging after certain number of days.

**Disclaimer: This code is currently used in a production environment, but offered without warranty of any kind. This is meant to be an example only. Be sure you understand how the code works, since your environment will likely require minor (or major) code modifications. Use at your own risk.**

## USAGE on Linux (Debian Stretch) with MySQL (or MariaDB)

###Permissions

Each EC2 instance must be allowed to perform certain AWS API actions. We need to create an IAM policy, attach the policy to an IAM group, and then attach that IAM group to each server's IAM role.

Create an IAM policy (e.g. policy-ec2-mysql-snapshots) allowing actions required for snapshot management. Customize this example IAM policy:

```policy-ec2-mysql-snapshot.json```

Create an IAM group (e.g. group-ec2-mysql-snapshots) and attach the policy above.

Create an IAM role (e.g. role-ec2-mysql-snapshots) and attach the group above.

Modify each EC2 server and add the IAM role above. If the server already uses an IAM role, DO NOT CHANGE the IAM role! Instead, add the IAM group above to the assigned IAM role.

###Scripts

This example depends on several scripts. You will need to review and customize each script.

Save [EC2 Consistent Snapshot](https://github.com/alestic/ec2-consistent-snapshot) to the following location:

```/usr/local/sbin/ec2-consistent-snapshot```

Save "EC2 Get VolumeID" helper script to the following location:

```/usr/local/sbin/ec2-get-volumeid.php```

Save "EC2 Purge Snapshots" helper script to the following location:

```/usr/local/sbin/ec2-purge-snapshots.php```

Save "EC2 MySQL Snapshot" Cron Script to the following location and customize as necessary. At a minimum, you must specify your AWS region (e.g. "us-east-1") and volume path (e.g. "/data").

```/etc/cron.daily/ec2-mysql-snapshot```

Make each script executable

```
chmod 755 /usr/local/sbin/ec2-consistent-snapshot
chmod 755 /usr/local/sbin/ec2-get-volumeid.php
chmod 755 /usr/local/sbin/ec2-purge-snapshots.php
chmod 755 /etc/cron.daily/ec2-mysql-snapshot
```

Manually run the following command to perform a consistent snapshot. Review the Snapshots in your EC2 console to confirm a snapshot occurred.

```/etc/cron.daily/ec2-mysql-snapshot```

