# Terminus\Outputters\JSONFormatter

### __construct
##### Description:
    Object constructor. Sets the JSON options property

##### Parameters:
    [int] $options The json_encode options bitmask

---

### formatDump
##### Description:
    Formats any kind of value as a raw dump

##### Parameters:
    [mixed] $object An object to dump via print_r

##### Return:
    [string]

---

### formatRecord
##### Description:
    Format a single record or object

##### Parameters:
    [array|object] $record       A key/value array or object
    [array]        $human_labels A key/value array mapping the keys in
    -the record to human labels

##### Return:
    [string]

---

### formatRecordList
##### Description:
    Format a list of records of the same type.

##### Parameters:
    [array] $records      A list of arrays or objects.
    [array] $human_labels An array mapping record keys to human names

##### Return:
    [string]

---

### formatValue
##### Description:
    Formats a single scalar value with an optional human label.

##### Parameters:
    [mixed]  $value       A scalar value to format
    [string] $human_label A human readable label for that value

##### Return:
    [string]

---

### formatValueList
##### Description:
    Format a list of scalar values

##### Parameters:
    [array]  $values      The values to format
    [string] $human_label A human name for the entire list. If each value
    -needs a separate label, then formatRecord should be used.

##### Return:
    [void]

---

