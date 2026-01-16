# Employee Creation Flow: Page 1 and Page 2

## How Both Pages Save to the Same Employee Record

### **Page 1: Create Employee (INSERT)**
1. User fills out Page 1 form with basic employee information
2. Form submits via POST to `add_employee.php`
3. System calls `add_employee()` function which executes:
   ```sql
   INSERT INTO employees (...) VALUES (...)
   ```
4. After successful INSERT, system gets the new employee ID:
   ```php
   $new_employee_id = $pdo->lastInsertId();
   ```
5. Employee ID is stored in session:
   ```php
   $_SESSION['employee_created_id'] = $new_employee_id;
   ```
6. Page redirects with employee ID in URL:
   ```
   ?page=add_employee&success=1
   ```
7. Page 1 shows button to proceed to Page 2 with employee ID:
   ```
   ?page=add_employee_page2&employee_id={ID}
   ```

### **Page 2: Update Employee (UPDATE)**
1. User clicks "Proceed to Page 2" button
2. Page 2 receives employee ID from:
   - URL parameter: `$_GET['employee_id']`
   - Session: `$_SESSION['employee_created_id']`
3. System verifies employee exists:
   ```sql
   SELECT id FROM employees WHERE id = ?
   ```
4. User fills out Page 2 form with additional information
5. Form submits via POST to `add_employee_page2.php`
6. System executes UPDATE query:
   ```sql
   UPDATE employees SET 
       vacancy_source = ?,
       referral_name = ?,
       ... (all page 2 fields)
   WHERE id = ?
   ```
7. The `WHERE id = ?` clause ensures it updates the SAME employee record created in Page 1

## Key Points

✅ **Same Employee ID**: Both pages use the same employee ID to link the records
✅ **Page 1 = INSERT**: Creates new record
✅ **Page 2 = UPDATE**: Updates existing record
✅ **Session Persistence**: Employee ID stored in session to maintain connection
✅ **URL Parameter**: Employee ID also passed in URL for reliability

## Database Flow

```
Page 1 Submission:
┌─────────────────────────────────┐
│ INSERT INTO employees (...)     │ → Creates record with ID = 5
│ Returns: lastInsertId() = 5    │
└─────────────────────────────────┘
         │
         │ Stores ID=5 in session
         ▼
Page 2 Submission:
┌─────────────────────────────────┐
│ UPDATE employees SET ...        │ → Updates record WHERE id = 5
│ WHERE id = 5                    │
└─────────────────────────────────┘
```

## Verification

To verify both pages are saving to the same employee:

1. Create employee on Page 1
2. Note the employee ID from the success message or database
3. Go to Page 2 and submit
4. Check database: `SELECT * FROM employees WHERE id = {ID}`
5. You should see:
   - Page 1 fields (first_name, surname, employee_no, etc.)
   - Page 2 fields (vacancy_source, signature_1, fingerprints, etc.)
   - All in the SAME record

## Troubleshooting

If data from Page 2 is not appearing:

1. **Check employee ID**: Ensure `employee_id` is being passed correctly
2. **Check UPDATE query**: Verify the UPDATE is executing successfully
3. **Check database columns**: Ensure Page 2 fields exist in employees table
4. **Check session**: Verify `$_SESSION['employee_created_id']` is set
5. **Check logs**: Look for errors in error.log or database logs
