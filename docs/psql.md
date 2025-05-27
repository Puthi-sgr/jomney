## PostgreSQL Docker Cheat Sheet

### Docker Container Management
```bash
# List running containers
docker ps

# Access container bash shell
docker exec -it <container_name> bash

# Direct PostgreSQL access
docker exec -it <container_name> psql -U <username> -d <database>
```

### PostgreSQL Connection
```bash
psql -U <username> -d <database>
```

My current database:
psql -U food_user -d food_delivery

### Database Navigation
```sql
\l          -- List all databases
\c <db>     -- Connect to database
\dt         -- List all tables
\d <table>  -- Describe table
\du         -- List users and roles
\dn         -- List schemas
```

### Table Operations
```sql
-- Query data
SELECT * FROM <table_name> LIMIT 10;
SELECT column1, column2 FROM <table_name> WHERE condition;

-- Modify data
INSERT INTO <table_name> (column1, column2) VALUES ('value1', 'value2');
UPDATE <table_name> SET column1 = 'new_value' WHERE condition;
DELETE FROM <table_name> WHERE condition;
```

### Database Management
```sql
-- Database operations
CREATE DATABASE <database_name>;
DROP DATABASE <database_name>;

-- Create table
CREATE TABLE <table_name> (
  id SERIAL PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Modify table
ALTER TABLE <table_name> ADD COLUMN <column_name> <data_type>;
```

### User Management
```sql
CREATE USER <username> WITH PASSWORD 'password';
GRANT ALL PRIVILEGES ON DATABASE <database_name> TO <username>;
```

### Backup and Restore
```bash
# Export database
docker exec -it <container_name> pg_dump -U <username> -d <database> > backup.sql

# Import database
docker exec -i <container_name> psql -U <username> -d <database> < backup.sql
```

### Useful Queries
```sql
-- Database size
SELECT pg_size_pretty(pg_database_size('<database_name>'));

-- Table size
SELECT pg_size_pretty(pg_total_relation_size('<table_name>'));

-- Active connections
SELECT * FROM pg_stat_activity;
```

### Exit Commands
```sql
\q    -- Exit psql
exit  -- Exit container shell
```