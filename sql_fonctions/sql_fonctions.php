-- Inputs: table name (text), date column name (text), Date needed (timestamp)
CREATE OR REPLACE FUNCTION pg_IdFromDate(text, text, timestamp) RETURNS int as $$
DECLARE
    table_name ALIAS FOR $1;
    date_column_name ALIAS FOR $2;
    timestamp_needed ALIAS FOR $3;
    working_date timestamp;
    r int;
    current_id int;
    old_range int = 0;
    
BEGIN
      -- Select current date  
      SELECT NOW() INTO working_date;
      -- RAISE NOTICE 'Current time: %', working_date;
      
      -- Fetch Max ID
      SELECT MAX_ID(table_name) INTO current_id;
      -- NOTICE
      -- RAISE NOTICE 'Max. ID of table -%-: %', table_name, current_id;

      FOR r in SELECT DATA_RANGES(table_name)
      LOOP
            IF old_range != 0 THEN
                current_id = old_range;
                -- Select current date  
                SELECT NOW() INTO working_date;
            END IF;
            
            -- File we are still above the date researched and the current ID is still above 0
            WHILE working_date > timestamp_needed AND current_id > MIN_ID(table_name) LOOP
                -- Search Date for current ID          
                SELECT EPOCH_FROM_ID(table_name::text, date_column_name::text, r::int , '='::text) INTO working_date;
        
                -- If date found is null, we will search a date close from this ID
                IF working_date IS NULL THEN
                    -- NOTICE
                    -- RAISE NOTICE 'NULL Value found for ROW # %', r;
                    SELECT EPOCH_FROM_ID(table_name::text, date_column_name::text, r::int , '>='::text) INTO working_date;
                END IF;

                -- If We were not abaible to finc a valid ID close to this row
                IF working_date IS NULL THEN
                    -- NOTICE
                    -- RAISE NOTICE 'NULL Value also found for rows close to ROW # %', r;
                    RETURN NULL;
                 END IF;

                -- NOTICE
                -- RAISE NOTICE 'Date % found for ROW # %',working_date, r;
                RAISE NOTICE 'Range -> % Current ID -> % date -> % (diff %)', r, current_id, working_date, 22;

                -- If the ID date is still to high we will lower the IDs by one value of the range
                IF working_date > timestamp_needed THEN
                    SELECT current_id - r INTO current_id;
                END IF;
            END LOOP;
           
            -- Save previous range
            SELECT r INTO old_range;

      END LOOP;
   RETURN current_id + old_range;
END;
$$ LANGUAGE 'plpgsql';
-- Example:
-- SELECT GET_ID_FROM_DATE('test', 'date', '2014-04-03 15:32:19');

-- Input: Table name
-- Ouput: data ranges
CREATE OR REPLACE FUNCTION DATA_RANGES(text) RETURNS TABLE (serie int)  AS $$
DECLARE
    table_name ALIAS FOR $1;
BEGIN
   RETURN QUERY WITH series as (
                    -- Generate a series of ranges of IDs from the minimal du the maximal
                    SELECT generate_series
                    FROM generate_series(MIN_ID('test'), MAX_ID('test'), MAX_ID('test') / 20)
                )
                SELECT CASE WHEN generate_series = (SELECT MAX(generate_series) FROM series) AND generate_series < MAX_ID('test') THEN MAX_ID('test')
                  ELSE generate_series
                  END
                FROM series;
END;
$$ LANGUAGE plpgsql;
-- Example:
-- SELECT DATA_RANGES('test');

-- Input: table name (text)
-- Output: Minimal ID in a table
CREATE OR REPLACE FUNCTION MIN_ID(text) RETURNS int AS $$
DECLARE
    table_name ALIAS FOR $1;
    minid int;
BEGIN
   EXECUTE ' SELECT MIN(id) FROM ' || quote_ident(table_name) || ''
   INTO minid;
   RETURN minid;
END;
$$ LANGUAGE plpgsql;
-- Example:
-- SELECT MIN_ID('test');

-- Input: table name (text)
-- Output: Maximal ID in a table
CREATE OR REPLACE FUNCTION MAX_ID(text) RETURNS int AS $$
DECLARE
    table_name ALIAS FOR $1;
    maxid int;
BEGIN
   EXECUTE ' SELECT MAX(id) FROM ' || quote_ident(table_name) || ''
   INTO maxid;
   RETURN maxid;
END;
$$ LANGUAGE plpgsql;
-- Example:
-- SELECT MAX_ID('test');

-- Inputs: table name (text), date column name (text), id (bigint), operator (=; >=)
-- Output: date correspondant do the ID researched
CREATE OR REPLACE FUNCTION EPOCH_FROM_ID(text, text, int, text) RETURNS int AS $$
DECLARE
    table_name ALIAS FOR $1;
    date_column_name ALIAS FOR $2;
    current_id ALIAS FOR $3;
    operator ALIAS FOR $4;
    timesp int;
BEGIN
   EXECUTE ' SELECT round(EXTRACT(EPOCH FROM  ' || quote_ident(date_column_name) || ')) FROM ' || quote_ident(table_name) || ' WHERE id ' || operator || ' ' || current_id || ' AND id IS NOT NULL LIMIT 1'
      INTO timesp;
   RETURN timesp;
END;
$$ LANGUAGE plpgsql;
-- Example:
-- SELECT EPOCH_FROM_ID('test', 'date', 114, '=');

-- Inputs: table name (text)
-- Ouput: range of valies
CREATE OR REPLACE FUNCTION DATA_RANGES(text) RETURNS TABLE (serie int)  AS $$
DECLARE
    table_name ALIAS FOR $1;
BEGIN
   RETURN QUERY WITH series as (
                    -- Generate a series of ranges of IDs from the minimal du the maximal
                    SELECT generate_series
                    FROM generate_series(MIN_ID(table_name), MAX_ID(table_name), MAX_ID(table_name) / 20)
                )
                SELECT CASE WHEN generate_series = (SELECT MAX(generate_series) FROM series) AND generate_series < MAX_ID(table_name) THEN MAX_ID(table_name)
                  ELSE generate_series
                  END
                FROM series
                ORDER BY generate_series DESC;
END;
$$ LANGUAGE plpgsql;
-- Example:
-- SELECT DATA_RANGES('test');

-- Inputs: table name (text), date column name (text), Date needed (timestamp)
-- Output: ID in the table closets from "date needed"
CREATE OR REPLACE FUNCTION pg_IdFromDate(text, text, timestamp) RETURNS int as $$
DECLARE
    table_name ALIAS FOR $1;
    date_column_name ALIAS FOR $2;
    timestamp_needed ALIAS FOR $3;
    timestamp_needed_epoch int;
    working_date int;
    r int;
    current_id int;
    old_range int = 0;
    
BEGIN
      -- Select current date  
      SELECT ROUND(EXTRACT(EPOCH FROM NOW())) INTO working_date;
      -- RAISE NOTICE 'Current time: %', working_date;
      
      -- Fetch Max ID
      SELECT MAX_ID(table_name) INTO current_id;
      -- NOTICE
      -- RAISE NOTICE 'Max. ID of table -%-: %', table_name, current_id;
      
      -- Convert requested date to epoch
      SELECT round(EXTRACT(EPOCH FROM timestamp_needed)) INTO timestamp_needed_epoch;
      -- NOTICE
      -- RAISE NOTICE 'Date requested: %', timestamp_needed;
      -- RAISE NOTICE 'Date requested converted to epoch : %', timestamp_needed_epoch;

      FOR r in SELECT DATA_RANGES(table_name)
      LOOP
            IF old_range != 0 THEN
                current_id = old_range;
                -- Select current date  
                SELECT timestamp_needed_epoch INTO working_date;
            END IF;
            
            -- File we are still above the date researched and the current ID is still above 0
            WHILE working_date > timestamp_needed_epoch AND current_id > MIN_ID(table_name) LOOP
                -- Search Date for current ID          
                SELECT DATE_FROM_ID(table_name::text, date_column_name::text, r::int , '='::text) INTO working_date;
        
                -- If date found is null, we will search a date close from this ID
                IF working_date IS NULL THEN
                    -- NOTICE
                    -- RAISE NOTICE 'NULL Value found for ROW # %', r;
                    SELECT DATE_FROM_ID(table_name::text, date_column_name::text, r::int , '>='::text) INTO working_date;
                END IF;

                -- If We were not abaible to finc a valid ID close to this row
                IF working_date IS NULL THEN
                    -- NOTICE
                    -- RAISE NOTICE 'NULL Value also found for rows close to ROW # %', r;
                    RETURN NULL;
                 END IF;

                -- NOTICE
                -- RAISE NOTICE 'Date % found for ROW # %',working_date, r;
                RAISE NOTICE 'Range -> % Current ID -> % date -> % (diff %)', r, current_id, working_date, 22;

                -- If the ID date is still to high we will lower the IDs by one value of the range
                IF working_date > timestamp_needed_epoch THEN
                    SELECT current_id - r INTO current_id;
                END IF;
            END LOOP;
           
            -- Save previous range
            SELECT r INTO old_range;

      END LOOP;
   RETURN current_id + old_range;
END;
$$ LANGUAGE 'plpgsql';
-- Example:
-- SELECT GET_ID_FROM_DATE('test', 'date', '2014-07-02 11:53:00.786386');
