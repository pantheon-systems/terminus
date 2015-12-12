# Terminus\Models\Collections\Backups

### cancelBackupSchedule
##### Description:
    Cancels an environment's regular backup schedule

##### Return:
    [boolean] True if operation was successful

---

### create
##### Description:
    Creates a backup

##### Parameters:
    [array] $arg_params Array of args to dictate backup choices
    -[string]  type     Sort of operation to conduct (e.g. backup)
    -[integer] keep-for Days to keep the backup for
    -[string]  element  Which aspect of the arg to back up

##### Return:
    [Workflow] $workflow

---

### getBackupByFileName
##### Description:
    Lists all backups

##### Parameters:
    [string] $filename Name of the file name to filter by

##### Return:
    [array] $backup

---

### getBackupsByElement
##### Description:
    Lists all backups

##### Parameters:
    [string] $element Name of the element type to filter by

##### Return:
    [array] $backups

---

### getBackupSchedule
##### Description:
    Retrieves an environment's regular backup schedule

##### Return:
    [array] $schedule Elements as follows:
    -[string]  daily_backup_time
    -[string]  weekly_backup_day

---

### getFinishedBackups
##### Description:
    Filters the backups for only ones which have finished

##### Parameters:
    [string] $element Element requested (i.e. code, db, or files)

##### Return:
    [array] $backups An array of stdClass objects representing backups

---

### setBackupSchedule
##### Description:
    Sets an environment's regular backup schedule

##### Parameters:
    [integer] $day_number A numerical of a day of the week

##### Return:
    [boolean] True if operation was successful

---

