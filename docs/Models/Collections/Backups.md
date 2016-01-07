# Terminus\Models\Collections\Backups

### cancelBackupSchedule
##### Description:
    Cancels an environment's regular backup schedule

##### Return:
    [bool] True if operation was successful

---

### create
##### Description:
    Creates a backup

##### Parameters:
    [array] $arg_params Array of args to dictate backup choices,
    -which may have the following keys:
    -- type: string: Sort of operation to conduct (e.g. backup)
    -- keep-for: int: Days to keep the backup for
    -- element: string: Which aspect of the arg to back up

##### Return:
    [Workflow]

---

### getBackupByFileName
##### Description:
    Fetches backup for a specified filename

##### Parameters:
    [string] $filename Name of the file name to filter by

##### Return:
    [Backup]

##### Throws:
    TerminusException

---

### getBackupsByElement
##### Description:
    Lists all backups for a specific element.

##### Parameters:
    [string] $element Name of the element type to filter by

##### Return:
    [Backup[]]

---

### getBackupSchedule
##### Description:
    Retrieves an environment's regular backup schedule

##### Return:
    [array] $schedule Elements as follows:
    -- daily_backup_time: string
    -- weekly_backup_day: string

---

### getFinishedBackups
##### Description:
    Filters the backups for only ones which have finished

##### Parameters:
    [string] $element Element requested (i.e. code, db, or files)

##### Return:
    [Backup[]] An array of Backup objects

##### Throws:
    TerminusException

---

### setBackupSchedule
##### Description:
    Sets an environment's regular backup schedule

##### Parameters:
    [int] $day_number A numerical of a day of the week

##### Return:
    [bool] True if operation was successful

---

