# pg_IdFromDate()

pg_IdFromDate is a set of PostgreSQL functions that you can install on any PostgreSQL database.

It uses dichotomy to **efficiently find a row's ID that matches a date searched**; even on very big data tables.

Instead of scanning the table row by row or using an index, buckets or rows are created to find the row matching a date **in a few milliseconds, even with several hundred million of rows**.

### Installation

* Install all methods from [sql/sql_functions.sql](sql/sql_functions.sql)
* If you want to use a sample data set, create the table in [sample_table/sample_table.sql](sample_table/sample_table.sql)
* Try it yourself with the examples below

### Use pg_IdFromDate()

pg_IdFromDate() requires 3 inputs: the table name, the name of the date column and the timestamp to search.

For example, if you have the following table:
```sql
gab@gab # SELECT * FROM test LIMIT 5 OFFSET 1000;
  id  |        date         |            some_data             
------+---------------------+----------------------------------
 1001 | 2014-09-03 01:10:21 | 03269e353d813c02520028031ccc6e97
 1002 | 2014-09-03 01:11:21 | 41dc7a48d3fd73822218f6e7f7b34c6c
 1003 | 2014-09-03 01:12:21 | 27aa297cbe3fabff3719f6acdd30697d
 1004 | 2014-09-03 01:13:21 | f56bbc19568140669c09def548ef6f21
 1005 | 2014-09-03 01:14:21 | 46375fe21561f921d0bb4e48b76728ac
(5 rows)
```

You might want a simple method to find the ID of the row with a certain date.

pg_IdFromDate() allows you to do that very quickly, even on very large tables with the following query:
```sql
gab@gab # SELECT pg_IdFromDate('test', 'date', '2014-08-09 16:53:00');
 pg_idfromdate 
---------------
      10179784
(1 row)
Time: 8.027 ms

gab@gab # SELECT pg_IdFromDate('test', 'date', '2014-08-09');
 pg_idfromdate 
---------------
      10178771
(1 row)
Time: 49.500 ms

gab@gab # SELECT pg_IdFromDate('test', 'date', (NOW() - interval '1 week')::timestamp);
 pg_idfromdate 
---------------
      10509560
(1 row)
Time: 28.850 ms
```

You can also use the result as a subquery in other queries, for example:
```sql
-- To select all rows where the date is > 2014-08-09
SELECT * FROM test WHERE id > (SELECT pg_IdFromDate('test', 'date', '2014-08-09'));

-- Select all rows where dates are between 2014-08-09 and 2014-10-01
SELECT * FROM test WHERE id > (SELECT pg_IdFromDate('test', 'date', '2014-08-09')) AND id < (SELECT pg_IdFromDate('test', 'date', '2014-10-01'));

-- Delete all rows where the date is < 2014-08-09
DELETE FROM test WHERE id < (SELECT pg_IdFromDate('test', 'date', '2014-08-09'));
```

### Benchmark on a table > 100 million rows

Method                            | "...WHERE date = '[DATE]';" | pg_IdFromDate() | Result                        
----------------------------------|-----------------------------|-----------------|--------------------------------
Querying a timestamp (no index)   | 25,225.50 ms                | 7.07 ms         | pg_IdFromDate() is **3,568x faster**
Querying a date (no index)        | 41,174.18 ms                | 6.56 ms         | pg_IdFromDate() is **6,276x faster**
Querying a timestamp (+ index)    | 12.44 ms                    | 7.11 ms         | pg_IdFromDate() is **2x faster**
Querying a date (+ index)         | 43,361.46 ms                | 7.27 ms         | pg_IdFromDate() is **5,964x faster**

Two detailed benchmarks (tables > 10 and > 100 million rows) with a detailed list of queries are available on our [Benchmark readme](benchmark/README.md).

### Limitations

* This tool is experimental and there is no guaranteed correct outcome
* If "id" and the "date" column are not strictly in the same chronological order, the output might be an invalid ID (for example if a date is modified and is not above and below the dates of the preceding and following rows).
* The "id" needs to be called "id" and be a unique primary key of the table.

### Author

**Gabriel Bordeaux**

+ [Website](http://www.gab.lc/) 
+ [Twitter](https://twitter.com/gabrielbordeaux)
