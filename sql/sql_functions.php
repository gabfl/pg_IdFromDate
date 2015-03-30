-- Inputs: table name (text), date column name (text), Date needed (timestamp)
-- Output: ID in the table closets from "date needed"
CREATE OR REPLACE FUNCTION pg_IdFromDate(text, text, timestamp) RETURNS bigint as $$
DECLARE
    table_name ALIAS FOR $1;
    date_column_name ALIAS FOR $2;
    timestamp_needed ALIAS FOR $3;
    timestamp_needed_epoch bigint;
    r bigint;
    current_id bigint;
    current_id_date bigint;
    current_difference int;
    old_range int = 0;
    
BEGIN
      -- Fetch Max ID
      SELECT MAX_ID(table_name) INTO current_id;
      -- RAISE NOTICE 'Max. ID of table -%-: %', table_name, current_id;
      
      -- Convert requested date to epoch
      SELECT round(EXTRACT(EPOCH FROM timestamp_needed)) INTO timestamp_needed_epoch;
      -- RAISE NOTICE 'Date requested: %', timestamp_needed;
      -- RAISE NOTICE 'Date requested converted to epoch : %', timestamp_needed_epoch;

      FOR r in SELECT DATA_RANGES(table_name, 4)
      LOOP
            IF old_range != 0 THEN
                SELECT current_id + old_range INTO current_id;
            END IF;
            
            -- Reset working date
            -- 2147485547 is the maximum epoch!
	        SELECT 2147485547 INTO current_id_date;
            
            -- File we are still above the date researched and the current ID is still above 0
            WHILE current_id_date >= timestamp_needed_epoch AND current_id >= MIN_ID(table_name) AND current_id_date != timestamp_needed_epoch LOOP
                -- Search Date for current ID
                SELECT EPOCH_FROM_ID(table_name::text, date_column_name::text, current_id, '='::text) INTO current_id_date;
        
                -- If date found is null, we will search a date close from this ID
                IF current_id_date IS NULL THEN
                    -- RAISE NOTICE 'NULL Value found for ROW # %', r;
                    SELECT EPOCH_FROM_ID(table_name::text, date_column_name::text, current_id, '>='::text) INTO current_id_date;
                END IF;

                -- If We were not abaible to finc a valid ID close to this row
                IF current_id_date IS NULL THEN
                    -- RAISE NOTICE 'NULL Value also found for rows close to ROW # %', r;
                    RETURN NULL;
                 END IF;

                -- Calculate difference between current researched date and date needed for notice only
                SELECT current_id_date - timestamp_needed_epoch INTO current_difference;
                -- RAISE NOTICE 'Range -> % Current ID -> % date found -> % (diff %)', r, current_id, current_id_date, current_difference;

                -- If the ID date is still to high we will lower the IDs by one value of the range
                IF current_id_date > timestamp_needed_epoch THEN
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
-- SELECT pg_IdFromDate('test', 'date', (NOW() - INTERVAL '10 day')::timestamp);
-- SELECT pg_IdFromDate('test', 'date', '2014-07-12 16:53:00');

-- Input: Table name (text), cutter strength (int, default is 4)
-- Ouput: data ranges
CREATE OR REPLACE FUNCTION DATA_RANGES(text, int) RETURNS TABLE (serie int) AS $$
DECLARE
    table_name ALIAS FOR $1;
    cutter ALIAS FOR $2;
    current_id int;
BEGIN
    -- Fetch Max ID
    SELECT MAX_ID(table_name) INTO current_id;
    
    -- Create a new range for each Last Max Row / cutter
    WHILE current_id / cutter > 0 LOOP
        SELECT current_id / cutter INTO current_id;
        RETURN QUERY SELECT current_id;
    END LOOP;
    
    -- If the last value is > 1 we will add 1 as the last value
    IF current_id > 1 THEN
        SELECT 1 INTO current_id;
        RETURN QUERY SELECT current_id;
    END IF;
    
    RETURN;
END;
$$
LANGUAGE plpgsql;
-- Example:
-- SELECT DATA_RANGES('test', 4);

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
CREATE OR REPLACE FUNCTION EPOCH_FROM_ID(text, text, bigint, text) RETURNS bigint AS $$
DECLARE
    table_name ALIAS FOR $1;
    date_column_name ALIAS FOR $2;
    current_id ALIAS FOR $3;
    operator ALIAS FOR $4;
    timesp bigint;
BEGIN
   EXECUTE ' SELECT round(EXTRACT(EPOCH FROM  ' || quote_ident(date_column_name) || ')) FROM ' || quote_ident(table_name) || ' WHERE id ' || operator || ' ' || current_id || ' AND id IS NOT NULL LIMIT 1'
      INTO timesp;
   RETURN timesp;
END;
$$ LANGUAGE plpgsql;
-- Example:
-- SELECT EPOCH_FROM_ID('test', 'date', 114, '=');
