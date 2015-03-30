-- Create the following table:
CREATE TABLE test (
    id serial unique,
    date timestamp,
    some_data text
);

-- Create en index on the ID
CREATE INDEX ON test (id);

-- The following query will insert about 6,000 random rows in the table
INSERT INTO test (date, some_data)
SELECT generate_series, md5(random()::text)
FROM generate_series(NOW() - interval '9 month',  NOW(), '1 hours');
