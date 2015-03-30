# pg_IdFromDate(): Example

### Create a sample table

Create a sample table from sample_table.sql.

### Installation

* Install all methods from sql/sql_fonctions.php
* If you want to use a sample data set, create the table in sample_table/sample_tablesample_table.sql
* Try it yourself with the examples bellow

### Use pg_IdFromDate()

pg_IdFromDate() requiers 3 inputs: the table name, the name of the date column and the timestamp to search.

For example, if you have the following table:
```sql
[perso] gab@gab # SELECT * FROM test LIMIT 5 OFFSET 1000;
  id  |            date            |            some_data             
------+----------------------------+----------------------------------
 7603 | 2014-08-09 13:53:00.786386 | 97d7d73b7495a1d81f002e787462da97
 7604 | 2014-08-09 14:53:00.786386 | 127c29d00f2a563aacd6605a5d62767e
 7605 | 2014-08-09 15:53:00.786386 | 2e9424d87929f035197696134c1bd100
 7606 | 2014-08-09 16:53:00.786386 | 434aca7d7a4616265d291aad94ebb848
 7607 | 2014-08-09 17:53:00.786386 | 56405aa0889b1fe3bc5ad2abec185a58
(5 rows)
```

You might want a simple method to find the ID of the row with a certain date.

pg_IdFromDate() allows you to do that very quickly, even on very large tables with the following query:
```sql
gab@gab # SELECT pg_IdFromDate('test', 'date', '2014-08-09 16:53:00');
 pg_idfromdate 
---------------
          7606
(1 row)

Time: 9.270 ms

gab@gab # SELECT pg_IdFromDate('test', 'date', '2014-08-09');
 pg_idfromdate 
---------------
          7590
Time: 9.270 ms

gab@gab # SELECT pg_IdFromDate('test', 'date', (NOW() - interval '1 week')::timestamp);
 pg_idfromdate 
---------------
         13012
(1 row)
Time: 12.116 ms
```

### Limitations

* This tool is experimental and there is no guaranteed correct outcome
* If "id" and the "date" column are not strictly in the same chronological order, the output might be an invalid ID (for example if a date is modified and his not above and bellow the dates of the preceding and following rows.)
