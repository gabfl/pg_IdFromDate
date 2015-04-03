# pg_IdFromDate(): Benchmark

## Benchmark with a table > 10 million rows

We searched the same timestamp / dates on the same medium sized table (> 10 million rows).

### Synthesis

Method                            | "...WHERE date = '[DATE]';" | pg_IdFromDate() | Result                        
----------------------------------|-----------------------------|-----------------|--------------------------------
Querying a timestamp (no index)   | 2,264.76 ms                 | 6.53 ms         | pg_IdFromDate() is **346x faster**
Querying a date (no index)        | 3,252.57 ms                 | 6.82 ms         | pg_IdFromDate() is **477x faster**
Querying a timestamp (+ index)    | 3.58 ms                     | 5.84 ms         | pg_IdFromDate() is 2 ms slower
Querying a date (+ index)         | 3,303.71 ms                 | 5.91 ms         | pg_IdFromDate() is **559x faster**

### Querying a timestamp

```sql
-- Tests without an index
gab@benchmark # SELECT * FROM test WHERE date = '2012-05-09 08:37:02';
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8995608 | 2012-05-09 08:37:02 | 5b4d648e6bc878c006391972b1f50da3
(1 row)
Time: 2264.763 ms

gab@benchmark # SELECT * FROM test WHERE id = (SELECT pg_IdFromDate('test', 'date', '2012-05-09 08:37:02'));
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8995608 | 2012-05-09 08:37:02 | 5b4d648e6bc878c006391972b1f50da3
(1 row)
Time: 6.536 ms

-- Creating an index
gab@benchmark # CREATE INDEX ON test (date);
CREATE INDEX
Time: 24581.615 ms

-- Tests with an index
gab@benchmark # SELECT * FROM test WHERE date = '2012-05-09 08:37:02';
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8995608 | 2012-05-09 08:37:02 | 5b4d648e6bc878c006391972b1f50da3
(1 row)
Time: 3.582 ms

gab@benchmark # SELECT * FROM test WHERE id = (SELECT pg_IdFromDate('test', 'date', '2012-05-09 08:37:02'));
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8995608 | 2012-05-09 08:37:02 | 5b4d648e6bc878c006391972b1f50da3
(1 row)
Time: 5.847 ms
```

#### Querying a date

```sql
-- Tests without an index
gab@benchmark # SELECT * FROM test WHERE date::date = '2011-05-08' LIMIT 1;
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8466611 | 2011-05-08 00:00:02 | 75bf3e08491339a455f397d3923c51c1
(1 row)
Time: 3252.576 ms

gab@benchmark # SELECT * FROM test WHERE id = (SELECT pg_IdFromDate('test', 'date', '2011-05-08'::date));
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8466611 | 2011-05-08 00:00:02 | 75bf3e08491339a455f397d3923c51c1
(1 row)
Time: 6.823 ms

-- Creating an index
gab@benchmark # CREATE INDEX ON test (date);
CREATE INDEX
Time: 25555.699 ms

-- Tests with an index
gab@benchmark # SELECT * FROM test WHERE date::date = '2011-05-08' LIMIT 1;
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8466611 | 2011-05-08 00:00:02 | 75bf3e08491339a455f397d3923c51c1
(1 row)
Time: 3303.716 ms

gab@benchmark # SELECT * FROM test WHERE id = (SELECT pg_IdFromDate('test', 'date', '2011-05-08'::date));
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8466611 | 2011-05-08 00:00:02 | 75bf3e08491339a455f397d3923c51c1
(1 row)
Time: 5.911 ms
```

## Benchmark with a table > 100 million rows

### Synthesis

Method                            | "...WHERE date = '[DATE]';" | pg_IdFromDate() | Result                        
----------------------------------|-----------------------------|-----------------|--------------------------------
Querying a timestamp (no index)   | 25,225.50 ms                | 7.07 ms         | pg_IdFromDate() is **3,568x faster**
Querying a date (no index)        | 41,174.18 ms                | 6.56 ms         | pg_IdFromDate() is **6,276x faster**
Querying a timestamp (+ index)    | 12.44 ms                    | 7.11 ms         | pg_IdFromDate() is **2x faster**
Querying a date (+ index)         | 43,361.46 ms                | 7.27 ms         | pg_IdFromDate() is **5,964x faster**

### Querying a timestamp

```sql
-- Tests without an index
gab@benchmark # SELECT * FROM test2 WHERE date = '2014-03-09 02:46:44';
    id    |        date         |            some_data             
----------+---------------------+----------------------------------
 99454201 | 2014-03-09 02:46:44 | a996eecc7b6f3b693595a5ed22126360
(1 row)
Time: 25225.509 ms

gab@benchmark # SELECT * FROM test2 WHERE id = (SELECT pg_IdFromDate('test2', 'date', '2014-03-09 02:46:44'));
    id    |        date         |            some_data             
----------+---------------------+----------------------------------
 99454201 | 2014-03-09 02:46:44 | a996eecc7b6f3b693595a5ed22126360
(1 row)
Time: 7.072 ms

-- Creating an index
gab@benchmark # CREATE INDEX ON test2 (date);
CREATE INDEX
Time: 227698.756 ms

-- Tests with an index
gab@benchmark # SELECT * FROM test2 WHERE date = '2014-03-09 02:46:44';
    id    |        date         |            some_data             
----------+---------------------+----------------------------------
 99454201 | 2014-03-09 02:46:44 | a996eecc7b6f3b693595a5ed22126360
(1 row)
Time: 12.447 ms

gab@benchmark # SELECT * FROM test2 WHERE id = (SELECT pg_IdFromDate('test2', 'date', '2014-03-09 02:46:44'));
    id    |        date         |            some_data             
----------+---------------------+----------------------------------
 99454201 | 2014-03-09 02:46:44 | a996eecc7b6f3b693595a5ed22126360
(1 row)
Time: 7.110 ms
```

#### Querying a date

```sql
-- Tests without an index
gab@benchmark # SELECT * FROM test2 WHERE date::date = '2014-03-09' LIMIT 1;
    id    |        date         |            some_data             
----------+---------------------+----------------------------------
 99454035 | 2014-03-09 00:00:44 | 0481703a3ac9a1eb21d14b98afb35739
(1 row)
Time: 41174.185 ms

gab@benchmark # SELECT * FROM test2 WHERE id = (SELECT pg_IdFromDate('test2', 'date', '2014-03-09'::date));
    id    |        date         |            some_data             
----------+---------------------+----------------------------------
 99454034 | 2014-03-08 23:59:44 | e9ced33e5c31c96642f18937659c2b70
(1 row)
Time: 6.568 ms

-- Creating an index
gab@benchmark # CREATE INDEX ON test2 (date);
CREATE INDEX
Time: 231331.880 ms

-- Tests with an index
gab@benchmark # SELECT * FROM test2 WHERE date::date = '2014-03-09' LIMIT 1;
    id    |        date         |            some_data             
----------+---------------------+----------------------------------
 99454035 | 2014-03-09 00:00:44 | 0481703a3ac9a1eb21d14b98afb35739
(1 row)
Time: 43361.469 ms

gab@benchmark # SELECT * FROM test2 WHERE id = (SELECT pg_IdFromDate('test2', 'date', '2014-03-09'::date));
    id    |        date         |            some_data             
----------+---------------------+----------------------------------
 99454034 | 2014-03-08 23:59:44 | e9ced33e5c31c96642f18937659c2b70
(1 row)
Time: 7.272 ms
```
