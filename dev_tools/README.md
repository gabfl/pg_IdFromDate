# pg_IdFromDate(): Developer tools

pg_IdFromDate uses a range of PostgreSQL methods written in PL/pgSQL which are dependencies to to main function pg_IdFromDate().

This page Readme is dedicated to developers who want to get a better understanding of pg_IdFromDate() dependencies functions.

### MIN_ID('table_name')

MIN_ID('table_name') will return the smallest ID in the table "table_name":
```sql
gab@gab # SELECT MIN_ID('test');
 min_id 
--------
   6603
(1 row)
```

### MAX_ID('table_name')

MAX_ID('table_name') will return the highest ID in the table "table_name":
```sql
gab@gab # SELECT MAX_ID('test');
 max_id 
--------
  13155
(1 row)
```

### EPOCH_FROM_ID('table_name', 'date_column_name', 'selected_id', '[operator]')

EPOCH_FROM_ID('table_name', 'date_column_name', 'selected_id', '[operator]') will return the date converted to an epoch of the column "date_column_name" of the row "selected_id" of the table "table_name".

[operator] can be either "=" or ">=". pg_IdFromDate() will always all this function with the operator "=". However, if the searched ID is inexistant or the date is NULL, the function will be called again with the operator ">=" to find the closest ID.
```sql
gab@gab # SELECT EPOCH_FROM_ID('test', 'date', 6614, '=');
 epoch_from_id 
---------------
    1404031981
(1 row)

gab@gab # SELECT EPOCH_FROM_ID('test', 'date', 6114, '=');
 epoch_from_id 
---------------
        {NULL}
(1 row)

gab@gab # SELECT EPOCH_FROM_ID('test', 'date', 6114, '>=');
 epoch_from_id 
---------------
    1403992381
(1 row)
```

### DATA_RANGES('table_name')

DATA_RANGES('table_name') will suggest a range of ID to test in a loop method to find the closest ID from the date needed. It could be described as a brute forcing attack of the table.
```sql
gab@gab # SELECT DATA_RANGES('test');
 data_ranges 
-------------
       13155
       11859
       11202
       10545
        9888
        9231
        8574
        7917
        7260
        6603
(10 rows)
```
