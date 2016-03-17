# Terminus\Helpers\FileHelper

### destinationIsValid
##### Description:
    Ensures that the given destination is valid

##### Parameters:
    [string] $destination Location of directory to ensure viability of
    [bool]   $make        True to create destination if it does not exist

##### Return:
    [string] Same as the parameter

##### Throws:
    TerminusException

---

### getFilenameFromUrl
##### Description:
    Get file name from a URL

##### Parameters:
    [string] $url A valid URL

##### Return:
    [string] The file name from the given URL

---

### loadAsset
##### Description:
    Loads a file of the given name from the assets directory.

##### Parameters:
    [string] $file Relative file path from the assets dir

##### Return:
    [string] Contents of the asset file

##### Throws:
    TerminusException

---

### sqlFromZip
##### Description:
    Removes ".gz" from a filename

##### Parameters:
    [string] $filename Name of file from which to remove ".gz"

##### Return:
    [string] Param string, ".gz" removed

---

