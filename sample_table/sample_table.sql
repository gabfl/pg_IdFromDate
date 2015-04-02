-- Create the following table:
CREATE TABLE test (
    id serial unique,
    date timestamp,
    some_data text
);

-- Create en index on the ID
CREATE INDEX ON test (id);

-- The following query will insert about 8,700 random rows in the table
INSERT INTO test (date, some_data)
SELECT generate_series, md5(random()::text)
FROM generate_series('2015-01-01 00:00:00'::timestamp,  '2015-12-31 23:59:59'::timestamp, '1 hour');
