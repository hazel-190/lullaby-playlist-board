-- Step 1: run this line first while connected to the default "postgres" database
-- (in psql:  psql -U postgres -f schema.sql   or paste into pgAdmin's Query Tool)
CREATE DATABASE classwall;

-- Then connect to classwall (psql: \c classwall) and run the rest:
CREATE TABLE IF NOT EXISTS messages (
    id         SERIAL PRIMARY KEY,
    author     VARCHAR(50)  NOT NULL,
    body       TEXT         NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);
