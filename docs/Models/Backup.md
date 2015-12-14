# Terminus\Models\Backup

### backupIsFinished
##### Description:
    Determines whether the backup has been completed or not

##### Return:
    [boolean]] $is_finished

---

### getBucket
##### Description:
    Retruns the bucket name for this backup

##### Return:
    [string] $bucket

---

### getDate
##### Description:
    Returns the date the backup was completed

##### Return:
    [string] $date Y-m-d H:i:s completion time or "Pending"

---

### getElement
##### Description:
    Retruns the element type of the backup

##### Return:
    [string] $type code, database, files, or null

---

### getInitiator
##### Description:
    Retruns the type of initiator of the backup

##### Return:
    [string] $initiator Either "manual" or "automated"

---

### getSizeInMb
##### Description:
    Returns the size of the backup in MB

##### Return:
    [string] $size_string

---

### getUrl
##### Description:
    Gets the URL of a backup

##### Return:
    [string] $response['data']->url

---

