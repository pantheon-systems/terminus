# Terminus\Outputters\Outputter

### __construct
##### Description:
    Object constructor. Sets writer and formatter properties.

##### Parameters:
    [OutputWriterInterface]    $writer    Writer object to set
    [OutputFormatterInterface] $formatter Formatter object to set

---

### getFormatter
##### Description:
    Retrieves the set formatter object

##### Return:
    [OutputFormatterInterface]

---

### getWriter
##### Description:
    Retrieves the set writer object

##### Return:
    [OutputWriterInterface]

---

### line
##### Description:
    Display a message in the CLI and end with a newline
    TODO: Clean this up. There should be no direct access to STDOUT/STDERR

##### Parameters:
    [string] $message Message to output before the new line

##### Return:
    [void]

---

### outputDump
##### Description:
    Outputs any variable type as a raw dump

##### Parameters:
    [object|array] $object Item to dump information on

##### Return:
    [void]

---

### outputRecord
##### Description:
    Formats a single record or object

##### Parameters:
    [array|object] $record       A key/value array or object
    [array]        $human_labels A key/value array mapping the keys in
    -the record to human labels

##### Return:
    [void]

---

### outputRecordList
##### Description:
    Formats a list of records of the same type

##### Parameters:
    [array] $records      A list of arrays or objects.
    [array] $human_labels An array that maps record keys to human names

##### Return:
    [void]

---

### outputValue
##### Description:
    Formats a single scalar value with an optional human label

##### Parameters:
    [mixed]  $value       The scalar value to format
    [string] $human_label The human readable label for the value

##### Return:
    [void]

---

### outputValueList
##### Description:
    Formats a list of scalar values

##### Parameters:
    [array]  $values      The values to format
    [string] $human_label One human name for the entire list. If each
    -value needs a separate label, then formatRecord should be used.

##### Return:
    [void]

---

### setFormatter
##### Description:
    Sets the formatter which converts the output to a useful string

##### Parameters:
    [OutputFormatterInterface] $formatter Formatter selected for use

##### Return:
    [void]

---

### setWriter
##### Description:
    Sets the writer which sends the output to its final destination

##### Parameters:
    [OutputWriterInterface] $writer Writer selected for use

##### Return:
    [void]

---

