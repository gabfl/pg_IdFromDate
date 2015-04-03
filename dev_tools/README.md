# pg_IdFromDate(): Developer tools

pg_IdFromDate uses a range of PostgreSQL methods written in PL/pgSQL which are dependencies to the main function pg_IdFromDate().

This page Readme is dedicated to developers who want to get a better understanding of pg_IdFromDate() dependencies functions.

### pg_IFD_MIN_ID('table_name')

pg_IFD_MIN_ID('table_name') will return the smallest ID in the table "table_name":
```sql
gab@gab # SELECT pg_IFD_MIN_ID('test');
 pg_ifd_min_id 
---------------
             1
(1 row)
Time: 0.551 ms
```

### pg_IFD_MAX_ID('table_name')

pg_IFD_MAX_ID('table_name') will return the highest ID in the table "table_name":
```sql
gab@gab # SELECT pg_IFD_MAX_ID('test');
 pg_ifd_max_id 
---------------
      10519201
(1 row)
Time: 0.893 ms
```

### pg_IFD_EPOCH_FROM_ID('table_name', 'date_column_name', 'selected_id', '[operator]')

pg_IFD_EPOCH_FROM_ID('table_name', 'date_column_name', 'selected_id', '[operator]') will return the date converted to an epoch of the column "date_column_name" of the row "selected_id" of the table "table_name".

[operator] can be either "=" or ">=". pg_IdFromDate() will always call this function with the operator "=". However, if the searched ID is inexistant or the date is NULL, the function will be called again with the operator ">=" to find the closest valid ID.
```sql
gab@gab # SELECT pg_IFD_EPOCH_FROM_ID('test', 'date', 6614, '=');
 pg_ifd_epoch_from_id 
----------------------
            797212982
(1 row)
Time: 1.490 ms

gab@gab # SELECT pg_IFD_EPOCH_FROM_ID('test', 'date', 6114, '=');
 pg_ifd_epoch_from_id 
----------------------
            797182982
(1 row)
Time: 1.645 ms

gab@gab # SELECT pg_IFD_EPOCH_FROM_ID('test', 'date', 6114, '>=');
 pg_ifd_epoch_from_id 
----------------------
            797182982
(1 row)
Time: 5.746 ms
(1 row)
```

### pg_IFD_MIDDLE_ID('id_low', 'id_high')

pg_IFD_MIDDLE_ID('id_low', 'id_high') will calculate the ID in the middle of 2 IDs in order to create two "buckets" (first half and second half of the range of IDs we will check).

The process is independent from the table searched; it's just a mathematical calculation and it might return a "missing" ID from the table.
```sql
gab@gab # SELECT pg_IFD_MIDDLE_ID(10000, 20000);
 pg_ifd_middle_id 
------------------
            15000
(1 row)
Time: 0.257 ms

gab@gab # SELECT pg_IFD_MIDDLE_ID(7504629, 7904679);
 pg_ifd_middle_id 
------------------
          7704654
(1 row)
Time: 0.259 ms
```

### pg_IFD_FETCH_ROW_FROM_SMALL_RANGE('table_name', 'date_column_name', 'id_low', 'id_high', 'timestamp_needed')

pg_IFD_FETCH_ROW_FROM_SMALL_RANGE('table_name', 'date_column_name', 'id_low', 'id_high', 'timestamp_needed') compared the "timestamp needed" to all the rows in the table between "id_low" and "id_high".

This process is done at the end of the dichotomy to avoid multiple queries on a small range of rows. Instead of created buckets of rows until there are no more rows to analyze, we will use this method for the last 50 rows. This method is the one returning the ID closest or matching the timestamp searched.

The tolerance is 12 hours and NULL will be returned if this tolerance is not met.
```sql
gab@gab # SELECT pg_IFD_FETCH_ROW_FROM_SMALL_RANGE('test', 'date', 7504629, 7504679, '2009-07-08 23:28:02');
 pg_ifd_fetch_row_from_small_range 
-----------------------------------
                           7504659
(1 row)
Time: 1.439 ms

gab@gab # SELECT pg_IFD_FETCH_ROW_FROM_SMALL_RANGE('test', 'date', 7504629, 7504679, '1980-07-08 23:28:02');
 pg_ifd_fetch_row_from_small_range 
-----------------------------------
                            {NULL}
(1 row)
Time: 1.402 ms
```
