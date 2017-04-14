-- Inputs: table name (text), date column name (text), Date needed (timestamp)
-- Output: ID in the table closets from "date needed"
CREATE OR REPLACE FUNCTION pg_IdFromDate(text, text, timestamp) RETURNS bigint as $$
DECLARE
    table_name ALIAS FOR $1;
    date_column_name ALIAS FOR $2;
    timestamp_needed ALIAS FOR $3;
    timestamp_needed_epoch bigint;
    id_low bigint;
    id_high bigint;
    id_middle bigint;
    id_middle_date bigint;
    
BEGIN
    -- Fetch Min ID
    SELECT pg_IFD_MIN_ID(table_name) INTO id_low;

    -- Fetch Max ID
    SELECT pg_IFD_MAX_ID(table_name) INTO id_high;
      
    -- Convert requested date to epoch
    SELECT round(EXTRACT(EPOCH FROM timestamp_needed)) INTO timestamp_needed_epoch;
    -- RAISE NOTICE 'Date requested: %', timestamp_needed;
    -- RAISE NOTICE 'Date requested converted to epoch : %', timestamp_needed_epoch;

    LOOP
        -- Get middle ID between low ID and high ID
        SELECT pg_IFD_MIDDLE_ID(id_low, id_high) INTO id_middle;
        -- RAISE NOTICE 'Middle ID: %', id_middle;

        -- Search Date for current ID
        SELECT pg_IFD_EPOCH_FROM_ID(table_name, date_column_name, id_middle, '='::text) INTO id_middle_date;

        -- If date found is null, we will search a date close from this ID
        IF id_middle_date IS NULL THEN
            -- RAISE NOTICE 'NULL Value found for ROW # %', id_middle;
            SELECT pg_IFD_EPOCH_FROM_ID(table_name, date_column_name, id_middle, '>='::text) INTO id_middle_date;
        END IF;

        -- If We were not able to find a valid ID close to this row
        IF id_middle_date IS NULL THEN
            -- RAISE NOTICE 'NULL Value also found for rows close to ROW # %', id_middle;
            RETURN NULL;
        END IF;
        
        -- Shift ID high or ID low to eliminate all rows not needed anymore
        IF id_middle_date = timestamp_needed_epoch THEN -- Perfect match
            RETURN id_middle;
        ELSEIF id_middle_date > timestamp_needed_epoch THEN -- Date of middle ID is greater than date needed
            SELECT id_middle INTO id_high;
        ELSEIF id_middle_date < timestamp_needed_epoch THEN -- Date of middle ID is lesser than date needed
            SELECT id_middle INTO id_low;
        ELSE
            RETURN NULL;
        END IF;
        
        -- Notice
        -- RAISE NOTICE 'Shift search to: id_low=% & id_high=%', id_low, id_high;

        -- If there is less than 50 IDs between id_low and id_high
        -- We will make one query to search between these IDs and find the row with the closest date from the date needed
        IF id_high - id_low <= 50 THEN
            SELECT pg_IFD_FETCH_ROW_FROM_SMALL_RANGE(table_name, date_column_name, id_low, id_high, timestamp_needed) INTO id_middle;
            RETURN id_middle;
        END IF;
    END LOOP;

    RETURN 1;
END;
$$ LANGUAGE 'plpgsql';
-- Example:
-- SELECT pg_IdFromDate('test', 'date', '2015-09-03 16:53:00');

-- Input: table name (text)
-- Output: Minimal ID in a table
CREATE OR REPLACE FUNCTION pg_IFD_MIN_ID(text) RETURNS bigint AS $$
DECLARE
    table_name ALIAS FOR $1;
    minid bigint;
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
CREATE OR REPLACE FUNCTION pg_IFD_MAX_ID(text) RETURNS bigint AS $$
DECLARE
    table_name ALIAS FOR $1;
    maxid bigint;
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

-- Inputs: lowest ID from range (bigint), highest ID from range (bigint)
-- Output: Middle ID
CREATE OR REPLACE FUNCTION pg_IFD_MIDDLE_ID(bigint, bigint) RETURNS bigint AS $$
DECLARE
    id_low ALIAS FOR $1;
    id_high ALIAS FOR $2;
    id_middle bigint;
BEGIN
   SELECT round((id_high - id_low) / 2 + id_low) INTO id_middle;
   RETURN id_middle;
END;
$$ LANGUAGE plpgsql;
-- Example:
-- SELECT pg_IFD_MIDDLE_ID(10000, 20000);

-- Inputs: table name (text), date column name (text), lowest ID from range (bigint), highest ID from range (bigint), Date needed (timestamp)
-- Output: ID with a date closest from a timestamp given with a max. tolerance of 12 hours
CREATE OR REPLACE FUNCTION pg_IFD_FETCH_ROW_FROM_SMALL_RANGE(text, text, bigint, bigint, timestamp) RETURNS bigint AS $$
DECLARE
    table_name ALIAS FOR $1;
    date_column_name ALIAS FOR $2;
    id_low ALIAS FOR $3;
    id_high ALIAS FOR $4;
    timestamp_needed ALIAS FOR $5;
    selected_id bigint;
BEGIN
   EXECUTE 'SELECT id
            FROM ' || quote_ident(table_name) || '
            WHERE id >= ' || id_low || ' AND id <= ' || id_high || '
                  AND ABS(
                               EXTRACT(EPOCH FROM ' || quote_ident(date_column_name) || ')
                                - 
                               EXTRACT(EPOCH FROM ' || quote_literal(timestamp_needed) || '::timestamp)
                           ) < 43200 -- The maximum tolerated difference between epoch is 12 hours
            ORDER BY ABS(
                         EXTRACT(EPOCH FROM ' || quote_ident(date_column_name) || ')
                          - 
                         EXTRACT(EPOCH FROM ' || quote_literal(timestamp_needed) || '::timestamp)
                     )
            LIMIT 1'
   INTO selected_id;
   RETURN selected_id;
END;
$$ LANGUAGE plpgsql;
-- Example:
-- SELECT pg_IFD_FETCH_ROW_FROM_SMALL_RANGE('test', 'date', 1000, 1020, '2009-07-05 18:02:02'::timestamp);
