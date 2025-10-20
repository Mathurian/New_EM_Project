-- Database initialization script for Docker
-- This script creates the database and user if they don't exist

-- Create database if it doesn't exist
SELECT 'CREATE DATABASE event_manager'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'event_manager')\gexec

-- Connect to the event_manager database
\c event_manager;

-- Create user if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = 'event_manager') THEN
        CREATE ROLE event_manager WITH LOGIN PASSWORD 'password';
    END IF;
END
$$;

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE event_manager TO event_manager;
GRANT ALL PRIVILEGES ON SCHEMA public TO event_manager;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO event_manager;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO event_manager;

-- Set default privileges for future objects
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO event_manager;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO event_manager;
