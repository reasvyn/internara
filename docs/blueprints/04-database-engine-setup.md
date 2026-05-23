# Blueprint 04: Database Engine Setup

## Default Configuration

Internara defaults to **SQLite** for development and testing. This provides
a zero-install database experience:

```
DB_CONNECTION=sqlite
```

The SQLite database file is `database/database.sqlite`. Create it with:

```bash
touch database/database.sqlite
php artisan migrate --seed
```

## Production Database

SQLite is not suitable for production. It locks the entire database file during
writes, so concurrent requests will encounter contention errors
("database is locked").

### MySQL 8+

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=internara
DB_PASSWORD=secure_password
```

Recommended configuration:

```ini
# /etc/mysql/my.cnf
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
innodb_flush_method = O_DIRECT
max_connections = 200
```

### MariaDB 10.6+

MariaDB is a drop-in alternative to MySQL using the same driver:

```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=internara
DB_PASSWORD=secure_password
```

MariaDB is configured in `config/database.php` under the `mariadb` connection.
It shares the same `pdo_mysql` extension as MySQL. Most MySQL tuning parameters
also apply to MariaDB.

### PostgreSQL 14+

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=internara
DB_USERNAME=internara
DB_PASSWORD=secure_password
```

Recommended configuration:

```ini
# /etc/postgresql/14/main/postgresql.conf
shared_buffers = 512MB
effective_cache_size = 1.5GB
work_mem = 16MB
maintenance_work_mem = 128MB
random_page_cost = 1.1
```

## Migration Strategy

After switching the database connection, run all migrations to create the schema:

```bash
php artisan migrate
```

**Note:** The application has not reached production, so no data migration is
needed yet. When migrating from SQLite to MySQL in the future, use Laravel's
built-in `dump` and schema sync tools.

## Known SQLite vs MySQL Differences

| Difference | Impact |
|---|---|
| SQLite does not enforce column length | A `varchar(255)` column accepts longer values in SQLite, but MySQL truncates. Ensure data fits constraints. |
| SQLite has no native `ENUM` type | Enum columns are stored as `text` with check constraints. MySQL uses native `ENUM`. Migrations abstract this difference. |
| SQLite locks on write | Under concurrent write load, "database is locked" errors occur. This is expected — switch to MySQL/PG in production. |
| SQLite `ALTER TABLE` is limited | Some schema changes require table recreation. Check `Schema::hasColumn()` before adding columns that may already exist. |

## Connection Pooling

For high-traffic deployments:

- **MySQL**: Use a connection pooler like ProxySQL or configure the database
  server's `max_connections` appropriately.
- **PostgreSQL**: Use PgBouncer for transaction-mode pooling.

Laravel's database configuration supports read/write separation for replicas:

```php
// config/database.php
'read' => [
    'host' => ['192.168.1.1'],
],
'write' => [
    'host' => ['192.168.1.2'],
],
```

## References

- `config/database.php` — all connection configurations
- `docs/known-issues.md` — SQLite vs MySQL differences catalog
- `docs/erd/00-erd-index.md` — complete ERD documentation (75 tables)
- `docs/adr/adr-007-sqlite-as-default-database.md` — rationale for SQLite default
