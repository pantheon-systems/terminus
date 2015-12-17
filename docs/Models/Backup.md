# Terminus\Models\Backup

### backupIsFinished
##### Description:
    Determines whether the backup has been completed or not

##### Return:
    [bool] True if backup is completed.

---

### getBucket
##### Description:
    Returns the bucket name for this backup

##### Return:
    [string]

---

### getDate
##### Description:
    Returns the date the backup was completed

##### Return:
    [string] Y-m-d H:i:s completion time or "Pending"

---

### getElement
##### Description:
    Returns the element type of the backup

##### Return:
    [string] code, database, files, or null

---

### getInitiator
##### Description:
    Returns the type of initiator of the backup

##### Return:
    [string] Either "manual" or "automated"

---

### getSizeInMb
##### Description:
    Returns the size of the backup in MB

##### Return:
    [string] A number (int or float) followed by 'MB'.

---

### getUrl
##### Description:
    Gets the URL of a backup

##### Return:
    [string]

---

