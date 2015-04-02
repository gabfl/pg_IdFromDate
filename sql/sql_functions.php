-- Inputs: table name (text), date column name (text), Date needed (timestamp)
-- Output: ID in the table closets from "date needed"
CREATE OR REPLACE FUNCTION pg_IdFromDate(text, text, timestamp) RETURNS bigint as $$
DECLARE
    table_name ALIAS FOR $1;
    date_column_name ALIAS FOR $2;
    timestamp_needed ALIAS FOR $3;
    timestamp_needed_epoch bigint;
    r bigint;
    min_id bigint;
    current_id bigint;
    current_id_date bigint;
    current_difference int;
    old_range int = 0;
    
BEGIN
      -- Fetch Max ID
      SELECT pg_IFD_MAX_ID(table_name) INTO current_id;
      -- RAISE NOTICE 'Max. ID of table -%-: %', table_name, current_id;
      
      -- Fetch Min ID
      SELECT pg_IFD_MIN_ID(table_name) INTO min_id;
      -- RAISE NOTICE 'Min. ID of table -%-: %', table_name, min_id;
      
      -- Convert requested date to epoch
      SELECT round(EXTRACT(EPOCH FROM timestamp_needed)) INTO timestamp_needed_epoch;
      -- RAISE NOTICE 'Date requested: %', timestamp_needed;
      -- RAISE NOTICE 'Date requested converted to epoch : %', timestamp_needed_epoch;

      FOR r in SELECT pg_IFD_DATA_RANGES(table_name, 4)
      LOOP
            IF old_range != 0 THEN
                SELECT current_id + old_range INTO current_id;
            END IF;
            
            -- Reset working date (in order to start the while)
            -- 2147485547 is the maximum epoch!
            SELECT 2147485547 INTO current_id_date;
            
            -- File we are still above the date researched and the current ID is still above 0
            WHILE current_id_date >= timestamp_needed_epoch AND current_id >= min_id AND current_id_date != timestamp_needed_epoch LOOP
                -- Search Date for current ID
                SELECT pg_IFD_EPOCH_FROM_ID(table_name::text, date_column_name::text, current_id, '='::text) INTO current_id_date;
        
                -- If date found is null, we will search a date close from this ID
                IF current_id_date IS NULL THEN
                    -- RAISE NOTICE 'NULL Value found for ROW # %', r;
                    SELECT pg_IFD_EPOCH_FROM_ID(table_name::text, date_column_name::text, current_id, '>='::text) INTO current_id_date;
                END IF;

                -- If We were not abaible to finc a valid ID close to this row
                IF current_id_date IS NULL THEN
                    -- RAISE NOTICE 'NULL Value also found for rows close to ROW # %', r;
                    RETURN NULL;
                 END IF;

                -- Debug notice
                -- RAISE NOTICE 'Range -> % Current ID -> % date found -> % (diff %)', r, current_id, current_id_date, current_difference;

                -- Calculate difference between current researched date and date needed
                SELECT current_id_date - timestamp_needed_epoch INTO current_difference;
                
                -- If the current ID perfectly matches the timestamp we will return it
                IF current_difference = 0 THEN
                    RETURN current_id;
                END IF;

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
-- SELECT pg_IdFromDate('test', 'date', '2015-07-12 16:53:00');

-- Input: Table name (text), cutter strength (int, default is 4)
-- Ouput: data ranges
CREATE OR REPLACE FUNCTION pg_IFD_DATA_RANGES(text, int) RETURNS TABLE (serie int) AS $$
DECLARE
    table_name ALIAS FOR $1;
    cutter ALIAS FOR $2;
    current_id int;
BEGIN
    -- Fetch Max ID
    SELECT pg_IFD_MAX_ID(table_name) INTO current_id;
    
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
-- SELECT pg_IFD_DATA_RANGES('test', 4);

-- Input: table name (text)
-- Output: Minimal ID in a table
CREATE OR REPLACE FUNCTION pg_IFD_MIN_ID(text) RETURNS int AS $$
DECLARE
    table_name ALIAS FOR $1;
    minid int;
BEGIN
   EXECUTE ' SELECT MIN(id) FROM ' || quote_ident(table_name)
   INTO minid;
   RETURN minid;
END;
$$ LANGUAGE plpgsql;
-- Example:
-- SELECT pg_IFD_MIN_ID('test');

-- Input: table name (text)
-- Output: Maximal ID in a table
CREATE OR REPLACE FUNCTION pg_IFD_MAX_ID(text) RETURNS int AS $$
DECLARE
    table_name ALIAS FOR $1;
    maxid int;
BEGIN
   EXECUTE ' SELECT MAX(id) FROM ' || quote_ident(table_name)
   INTO maxid;
   RETURN maxid;
END;
$$ LANGUAGE plpgsql;
-- Example:
-- SELECT pg_IFD_MAX_ID('test');

-- Inputs: table name (text), date column name (text), id (bigint), operator (=; >=)
-- Output: date correspondant do the ID researched
CREATE OR REPLACE FUNCTION pg_IFD_EPOCH_FROM_ID(text, text, bigint, text) RETURNS bigint AS $$
DECLARE
    table_name ALIAS FOR $1;
    date_column_name ALIAS FOR $2;
    current_id ALIAS FOR $3;
    operator ALIAS FOR $4;
    timesp bigint;
BEGIN
   EXECUTE ' SELECT round(EXTRACT(EPOCH FROM ' || quote_ident(date_column_name) || ')) FROM ' || quote_ident(table_name) || ' WHERE id ' || operator || ' ' || current_id || ' AND ' || quote_ident(date_column_name) || ' IS NOT NULL LIMIT 1'
      INTO timesp;
   RETURN timesp;
END;
$$ LANGUAGE plpgsql;
-- Example:
-- SELECT pg_IFD_EPOCH_FROM_ID('test', 'date', 114, '=');
