# pg_IdFromDate(): Benchmark

We searched the same timestamp / dates on the same medium sized table (> 10 million rows).

### Synthesis

Method                            | "...WHERE date = '[DATE]';" | pg_IdFromDate() | Result                        
----------------------------------|-----------------------------|-----------------|--------------------------------
Querying a timestamp (no index)   | 2264.763 ms                 | 16.351 ms       | pg_IdFromDate() is *141x faster*
Querying a date (no index)        | 3252.576 ms                 | 17.084 ms       | pg_IdFromDate() is *191x faster*
Querying a timestamp (+ index)    | 3.582 ms                    | 23.507 ms       | pg_IdFromDate() is 6x slower
Querying a date (+ index)         | 3303.716                    | 24.398          | pg_IdFromDate() is *137x faster*

### Querying a timestamp

```sql
-- Tests without an index
gab@benchmark # SELECT * FROM test WHERE date = '2012-05-09 08:37:21';
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 9000008 | 2012-05-09 08:37:21 | 80e27e440ff8d570b9a6bbaa72fe6733
(1 row)
Time: 2264.763 ms

gab@benchmark # SELECT * FROM test WHERE id = (SELECT pg_IdFromDate('test', 'date', '2012-05-09 08:37:21'));
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 9000009 | 2012-05-09 08:38:21 | 8ff70dd3db443b810d6793792e15bea3
(1 row)
Time: 16.351 ms

-- Creating an index
gab@benchmark # CREATE INDEX ON test (date);
CREATE INDEX
Time: 24581.615 ms

-- Tests with an index
gab@benchmark # SELECT * FROM test WHERE date = '2012-05-09 08:37:21';
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 9000008 | 2012-05-09 08:37:21 | 80e27e440ff8d570b9a6bbaa72fe6733
(1 row)
Time: 3.582 ms

gab@benchmark # SELECT * FROM test WHERE id = (SELECT pg_IdFromDate('test', 'date', '2012-05-09 08:37:21'));
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 9000009 | 2012-05-09 08:38:21 | 8ff70dd3db443b810d6793792e15bea3
(1 row)
Time: 23.507 ms
```

pg_IdFromDate() was about 141 faster on a table without an index than a regular "date" based query.
With an index, the index was more performant than pg_IdFromDate() while both queries returned a very quick result.

For these queries, pg_IdFromDate() returned a row with 1 minute difference versus the timestamp searched. Small approximations are due to fast and efficient calculation versus slow and perfect. It will be improved within the next versions.

#### Querying a date

```sql
-- Tests without an index
gab@benchmark # SELECT * FROM test WHERE date::date = '2011-05-08' LIMIT 1;
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8471011 | 2011-05-08 00:00:21 | 1954adfd4f5067b11d2ef096d8e98212
(1 row)
Time: 3252.576 ms

gab@benchmark # SELECT * FROM test WHERE id = (SELECT pg_IdFromDate('test', 'date', '2011-05-08'::date));
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8471011 | 2011-05-08 00:00:21 | 1954adfd4f5067b11d2ef096d8e98212
(1 row)
Time: 17.084 ms

-- Creating an index
gab@benchmark # CREATE INDEX ON test (date);
CREATE INDEX
Time: 25555.699 ms

-- Tests with an index
gab@benchmark # SELECT * FROM test WHERE date::date = '2011-05-08' LIMIT 1;
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8471011 | 2011-05-08 00:00:21 | 1954adfd4f5067b11d2ef096d8e98212
(1 row)
Time: 3303.716 ms

gab@benchmark # SELECT * FROM test WHERE id = (SELECT pg_IdFromDate('test', 'date', '2011-05-08'::date));
   id    |        date         |            some_data             
---------+---------------------+----------------------------------
 8471011 | 2011-05-08 00:00:21 | 1954adfd4f5067b11d2ef096d8e98212
(1 row)
Time: 24.398 ms
```

On a test bases on a date instead of a timestamp (while the column itself is a timestamp), pg_IdFromDate() was 191 times faster than a regular query.
Even after adding an index on the column, pg_IdFromDate() stayed 137 faster than the query.

