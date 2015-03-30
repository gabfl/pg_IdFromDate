pg_IdFromDate
=============

pg_IdFromDate is a set of PostgreSQL functions that you can install on any PostgreSQL databases.

The objectif is to be able to find very efficiently an ID corresponding to a row with a date, even on very large tables.

**Installation**

To install the methodes necessary for pg_IdFromDate, you can just dump on your database the content of sql_fonctions/sql_fonctions.php from this repository.

**Basic examples**

```sql
gab@gab # SELECT pg_IdFromDate('test', 'date', '2014-04-03 15:32:19');
 get_id_from_date 
------------------
             7260
(1 row)
Time: 10.080 ms

[perso] gab@gab # SELECT * FROM test WHERE id = 7260;
  id  |            date            |            some_data             
------+----------------------------+----------------------------------
 7260 | 2014-04-03 15:32:19 | 2c85e4e13ca4a42f9fbd101efe5ca3f9
(1 row)
Time: 0.477 ms
```

**Test table**

A test table is available in example/example.sql
A few other PostgreSQL functions used for pg_IdFromDate for the calculation of the closest ID from a date are also explaines in the example file.
