# Employee Table Alterations Required

## Summary
The add employee form collects several fields that are **NOT currently in the employees table**. Below are the fields that need to be added to align the database with the UI.

---

## Fields Missing from Database Table

### 1. **employment_status** ⚠️ CRITICAL
- **Status**: Currently being inserted in code but column doesn't exist in table
- **Type**: `VARCHAR(50)` or `ENUM('Probationary','Regular','Suspended','Terminated')`
- **Null**: YES
- **Description**: Employment status (Probationary, Regular, Suspended, Terminated)
- **Used in**: Form validation (required field), database insert

### 2. **contact_person_alt**
- **Type**: `VARCHAR(150) NULL`
- **Description**: Alternate/Emergency contact person name
- **Used in**: Form field (optional)

### 3. **relationship_alt**
- **Type**: `VARCHAR(50) NULL`
- **Description**: Relationship to alternate contact person
- **Used in**: Form field (optional)

### 4. **contact_person_number_alt**
- **Type**: `VARCHAR(20) NULL`
- **Description**: Alternate contact person phone number
- **Used in**: Form field (optional)

### 5. **contact_person_address_alt**
- **Type**: `TEXT NULL`
- **Description**: Alternate contact person address
- **Used in**: Form field (optional)

### 6. **hr_remarks**
- **Type**: `TEXT NULL` or `VARCHAR(500) NULL`
- **Description**: HR notes, remarks, or flags
- **Used in**: Form field (optional, maxlength="300")

### 7. **status_summary**
- **Type**: `VARCHAR(50) NULL` or `ENUM('Completed','Incomplete','For Follow-Up')`
- **Description**: Status summary field
- **Used in**: Form field (optional)

### 8. **character_references_json**
- **Type**: `TEXT NULL`
- **Description**: Character references stored as JSON (similar to trainings_json)
- **Structure**: Array of objects with: name, occupation, company, contact
- **Used in**: Form field (optional, multiple entries)

---

## SQL ALTER TABLE Statements

Run these SQL statements to add the missing columns to your `employees` table:

```sql
-- Add employment_status (CRITICAL - currently being inserted but column missing)
ALTER TABLE employees 
ADD COLUMN employment_status VARCHAR(50) NULL 
AFTER status;

-- Add alternate contact person fields
ALTER TABLE employees 
ADD COLUMN contact_person_alt VARCHAR(150) NULL 
AFTER contact_person_number;

ALTER TABLE employees 
ADD COLUMN relationship_alt VARCHAR(50) NULL 
AFTER contact_person_alt;

ALTER TABLE employees 
ADD COLUMN contact_person_number_alt VARCHAR(20) NULL 
AFTER relationship_alt;

ALTER TABLE employees 
ADD COLUMN contact_person_address_alt TEXT NULL 
AFTER contact_person_number_alt;

-- Add HR remarks
ALTER TABLE employees 
ADD COLUMN hr_remarks TEXT NULL 
AFTER philhealth_no;

-- Add status summary
ALTER TABLE employees 
ADD COLUMN status_summary VARCHAR(50) NULL 
AFTER hr_remarks;

-- Add character references JSON
ALTER TABLE employees 
ADD COLUMN character_references_json TEXT NULL 
AFTER employment_history_json;
```

---

## Alternative: Single ALTER Statement

You can also run all alterations in a single statement:

```sql
ALTER TABLE employees 
ADD COLUMN employment_status VARCHAR(50) NULL AFTER status,
ADD COLUMN contact_person_alt VARCHAR(150) NULL AFTER contact_person_number,
ADD COLUMN relationship_alt VARCHAR(50) NULL AFTER contact_person_alt,
ADD COLUMN contact_person_number_alt VARCHAR(20) NULL AFTER relationship_alt,
ADD COLUMN contact_person_address_alt TEXT NULL AFTER contact_person_number_alt,
ADD COLUMN hr_remarks TEXT NULL AFTER philhealth_no,
ADD COLUMN status_summary VARCHAR(50) NULL AFTER hr_remarks,
ADD COLUMN character_references_json TEXT NULL AFTER employment_history_json;
```

---

## Code Updates Required

After adding the database columns, you need to update the code to save these fields:

### 1. Update `pages/add_employee.php` (around line 453-575)

Add these fields to the `$employee_data` array:

```php
// Add after line 571 (after 'employment_status')
'contact_person_alt' => !empty($_POST['contact_person_alt']) ? strtoupper(trim($_POST['contact_person_alt'])) : null,
'relationship_alt' => !empty($_POST['relationship_alt']) ? trim($_POST['relationship_alt']) : null,
'contact_person_number_alt' => !empty($_POST['contact_person_number_alt']) ? preg_replace('/[^0-9]/', '', $_POST['contact_person_number_alt']) : null,
'contact_person_address_alt' => !empty($_POST['contact_person_address_alt']) ? strtoupper(trim($_POST['contact_person_address_alt'])) : null,
'hr_remarks' => !empty($_POST['hr_remarks']) ? trim($_POST['hr_remarks']) : null,
'status_summary' => !empty($_POST['status_summary']) ? trim($_POST['status_summary']) : null,
'character_references_json' => (function () {
    $references = $_POST['character_references'] ?? [];
    if (!is_array($references)) return null;
    $out = [];
    foreach ($references as $ref) {
        if (!is_array($ref)) continue;
        $name = trim((string)($ref['name'] ?? ''));
        $occupation = trim((string)($ref['occupation'] ?? ''));
        $company = trim((string)($ref['company'] ?? ''));
        $contact = trim((string)($ref['contact'] ?? ''));
        if ($name === '' && $occupation === '' && $company === '' && $contact === '') continue;
        $out[] = [
            'name' => strtoupper($name),
            'occupation' => strtoupper($occupation),
            'company' => strtoupper($company),
            'contact' => $contact
        ];
    }
    return empty($out) ? null : json_encode($out, JSON_UNESCAPED_UNICODE);
})(),
```

### 2. Update `includes/database.php` - `add_employee()` function

**Update the `$columns` array** (around line 593-611):

Add these columns after the existing ones:
```php
$columns = [
    // ... existing columns ...
    'contact_person_number',
    'contact_person_alt',        // ADD THIS
    'relationship_alt',          // ADD THIS
    'contact_person_number_alt', // ADD THIS
    'contact_person_address_alt', // ADD THIS
    'blood_type', 'religion', 
    'hr_remarks',                // ADD THIS
    'status_summary',            // ADD THIS
    'employment_status', 'status',
    'character_references_json', // ADD THIS (after employment_history_json)
    'created_by', 'created_by_name'
];
```

**Update the `$params` array** (around line 615-680):

Add these parameters in the same order as columns:
```php
$params = [
    // ... existing params ...
    $data['contact_person_number'] ?? null,
    $data['contact_person_alt'] ?? null,        // ADD THIS
    $data['relationship_alt'] ?? null,          // ADD THIS
    $data['contact_person_number_alt'] ?? null, // ADD THIS
    $data['contact_person_address_alt'] ?? null, // ADD THIS
    $data['blood_type'] ?? null,
    $data['religion'] ?? null,
    $data['hr_remarks'] ?? null,                // ADD THIS
    $data['status_summary'] ?? null,            // ADD THIS
    $data['employment_status'] ?? null,
    $data['status'] ?? 'Active',
    $data['character_references_json'] ?? null, // ADD THIS (after employment_history_json)
    $data['created_by'] ?? null,
    $data['created_by_name'] ?? null
];
```

### 3. Update `includes/database.php` - `ensure_employee_columns()` function

Add these columns to the ensure_employee_columns call (around line 549-588):

```php
ensure_employee_columns([
    // ... existing columns ...
    'employment_status' => 'VARCHAR(50) NULL',
    'contact_person_alt' => 'VARCHAR(150) NULL',
    'relationship_alt' => 'VARCHAR(50) NULL',
    'contact_person_number_alt' => 'VARCHAR(20) NULL',
    'contact_person_address_alt' => 'TEXT NULL',
    'hr_remarks' => 'TEXT NULL',
    'status_summary' => 'VARCHAR(50) NULL',
    'character_references_json' => 'TEXT NULL',
]);
```

---

## Notes

1. **employment_status** is the most critical field as it's already being inserted in the code (`includes/database.php` line 609, 676) but the column doesn't exist in the table. This will cause INSERT errors.

2. The alternate contact person fields (`contact_person_alt`, `relationship_alt`, etc.) are currently collected in the form but not saved to the database. After adding columns and updating code, they will be saved.

3. `character_references` data is collected in the form but not currently stored. It should be stored as JSON similar to `trainings_json` and `employment_history_json`.

4. `hr_remarks` and `status_summary` are also collected but not saved. After adding columns and updating code, they will be saved.

5. Make sure the order of columns in the `$columns` array matches the order in the `$params` array in `includes/database.php`.

---

## Verification

After running the ALTER statements, verify the columns were added:

```sql
DESCRIBE employees;
-- or
SHOW COLUMNS FROM employees;
```

You should see all the new columns listed in the table structure.
