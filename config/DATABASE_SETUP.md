# Database Setup Guide for ServiceHub Multivendor Marketplace

## Prerequisites
- XAMPP, WAMP, or similar local web server with PHP and MySQL
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.2 or higher

## Database Import Instructions

### Method 1: Using phpMyAdmin (Recommended)

1. **Start your web server** (XAMPP/WAMP)
2. **Open phpMyAdmin** in your browser: `http://localhost/phpmyadmin`
3. **Import the database:**
   - Click on "Import" in the top menu
   - Click "Choose File" and select `database_import.sql`
   - Click "Go" to import
4. **Verify the import:**
   - You should see a new database called `multivendor`
   - Check that all tables are created successfully

### Method 2: Using MySQL Command Line

1. **Open MySQL command line** or terminal
2. **Run the import command:**
   ```bash
   mysql -u root -p < database_import.sql
   ```
3. **Enter your MySQL root password** when prompted

### Method 3: Using MySQL Workbench

1. **Open MySQL Workbench**
2. **Connect to your MySQL server**
3. **Open the SQL file:**
   - File → Open SQL Script
   - Select `database_import.sql`
4. **Execute the script:**
   - Click the lightning bolt icon or press Ctrl+Shift+Enter

## Database Configuration

The database configuration is already set up in `config/database.php`:

```php
$conn = mysqli_connect("localhost", "root", "", "multivendor");
```

**If you need to change the database settings:**
- **Host:** Change "localhost" if your MySQL is on a different server
- **Username:** Change "root" to your MySQL username
- **Password:** Add your MySQL password after the empty string
- **Database:** The database name is "multivendor"

## Default Login Credentials

After importing the database, you can log in with these default accounts:

### Admin Account
- **Email:** admin@servicehub.com
- **Password:** password
- **Role:** Administrator
- **Access:** Full platform management

### Sample Customer Accounts
- **Email:** john@example.com
- **Password:** password
- **Role:** Customer

- **Email:** jane@example.com
- **Password:** password
- **Role:** Customer

### Sample Provider Accounts
- **Email:** mike@electric.com
- **Password:** password
- **Role:** Provider (Electrician)

- **Email:** sarah@plumber.com
- **Password:** password
- **Role:** Provider (Plumber)

- **Email:** david@cleaner.com
- **Password:** password
- **Role:** Provider (Cleaner)

## Database Structure

The database includes the following tables:

### Core Tables
- **users** - User accounts and authentication
- **providers** - Service provider profiles
- **services** - Individual services offered by providers
- **bookings** - Service bookings and appointments
- **reviews** - Customer reviews and ratings
- **notifications** - System notifications
- **reports** - User reports and disputes

### Sample Data Included
- 1 Admin user
- 2 Customer users
- 3 Provider users (Electrician, Plumber, Cleaner)
- 9 Sample services
- 5 Sample bookings
- 2 Sample reviews
- 4 Sample notifications
- 2 Sample reports

## File Permissions

Make sure the following directories have proper write permissions:

```bash
# For Linux/Mac users
chmod 755 uploads/
chmod 755 uploads/profiles/
chmod 755 uploads/kyc/
chmod 755 uploads/reviews/

# For Windows users
# Right-click on folders → Properties → Security → Edit → Add your user with Full Control
```

## Testing the Installation

1. **Access the application:** `http://localhost/project2/`
2. **Test admin login:** Use admin@servicehub.com / password
3. **Test customer login:** Use john@example.com / password
4. **Test provider login:** Use mike@electric.com / password

## Troubleshooting

### Common Issues

1. **"Access denied" error:**
   - Check your MySQL username and password in `config/database.php`
   - Ensure MySQL service is running

2. **"Table doesn't exist" error:**
   - Make sure the database import was successful
   - Check that all tables were created

3. **"Connection failed" error:**
   - Verify MySQL is running
   - Check the host, username, and password in database.php

4. **Upload errors:**
   - Check file permissions on upload directories
   - Ensure PHP has write access to the upload folders

### Reset Database

If you need to reset the database:

1. **Drop the existing database:**
   ```sql
   DROP DATABASE IF EXISTS multivendor;
   ```

2. **Re-import the database file**

### Backup Database

To create a backup of your database:

```bash
mysqldump -u root -p multivendor > backup_$(date +%Y%m%d_%H%M%S).sql
```

## Security Notes

- **Change default passwords** after first login
- **Update database credentials** in production
- **Set up proper file permissions** for upload directories
- **Configure SSL** for production use
- **Regular database backups** are recommended

## Support

If you encounter any issues:
1. Check the error logs in your web server
2. Verify all prerequisites are met
3. Ensure proper file permissions
4. Test with the default credentials first

---

**Note:** This is a development setup. For production deployment, additional security measures and configurations are required. 