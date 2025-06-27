# ServiceHub - Multivendor Service Marketplace Platform

A comprehensive web application where multiple service providers can register and list their services. Customers can browse, filter, and request services, while admins can monitor and manage the platform.

## 🚀 Features

### For Service Providers
- **Registration & KYC**: Complete registration with KYC document upload (PDF/IMG)
- **Service Profile**: Set up business profile with service categories and location tagging
- **Service Management**: Add, edit, and manage service listings
- **Booking Management**: Receive and manage booking requests
- **Job Completion**: Mark jobs as completed and track earnings
- **Reviews & Ratings**: View customer feedback and ratings

### For Customers
- **Advanced Search**: Filter services by category, price range, location, and rating
- **Service Browsing**: Browse providers and their services
- **Booking System**: Schedule services with date/time selection and requirements
- **Booking Management**: Track booking status and history
- **Feedback System**: Leave ratings and reviews after service completion

### For Administrators
- **Provider Approval**: Review and approve/reject service provider applications
- **User Management**: Monitor and manage user accounts
- **Dispute Resolution**: Handle reports and flagged content
- **Analytics Dashboard**: View platform statistics and reports
- **Content Moderation**: Manage reviews, reports, and platform content

## 🛠️ Technical Requirements

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ or MariaDB 10.2+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Web Server**: Apache/Nginx
- **File Upload**: Support for PDF, JPG, PNG files (max 5MB)

## 📁 Project Structure

```
ServiceHub/
├── admin/                 # Admin panel files
│   ├── dashboard.php     # Admin dashboard
│   ├── providers.php     # Provider management
│   ├── users.php         # User management
│   └── reports.php       # Report management
├── auth/                 # Authentication files
│   ├── login.php         # User login
│   ├── register.php      # User registration
│   ├── provider-setup.php # Provider profile setup
│   └── logout.php        # Logout functionality
├── booking/              # Booking system
│   ├── create.php        # Create booking
│   ├── view.php          # View booking details
│   └── manage.php        # Manage bookings
├── provider/             # Provider panel
│   ├── dashboard.php     # Provider dashboard
│   ├── profile.php       # Provider profile
│   └── services.php      # Service management
├── user/                 # Customer panel
│   ├── dashboard.php     # Customer dashboard
│   └── bookings.php      # Booking history
├── assets/               # Static assets
│   ├── css/             # Stylesheets
│   ├── js/              # JavaScript files
│   └── images/          # Images and icons
├── config/              # Configuration files
│   └── database.php     # Database configuration
├── includes/            # PHP includes
│   └── functions.php    # Utility functions
├── uploads/             # File uploads
│   ├── profiles/        # Profile images
│   ├── kyc/            # KYC documents
│   └── reviews/        # Review images
├── index.php           # Home page
├── services.php        # Services listing
├── providers.php       # Providers listing
└── README.md          # This file
```

## 🚀 Installation & Setup

### 1. Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.2 or higher
- PHP extensions: mysqli, fileinfo, gd

### 2. Database Setup
1. Create a new MySQL database:
```sql
CREATE DATABASE servicehub;
```

2. Update database configuration in `config/database.php`:
```php
$host = 'localhost';
$username = 'your_username';
$password = 'your_password';
$database = 'servicehub';
```

### 3. File Permissions
Set proper permissions for upload directories:
```bash
chmod 755 uploads/
chmod 755 uploads/profiles/
chmod 755 uploads/kyc/
chmod 755 uploads/reviews/
```

### 4. Web Server Configuration
Configure your web server to point to the project directory and ensure PHP is enabled.

### 5. Initial Setup
1. Access the application in your browser
2. The database tables will be created automatically
3. Default admin account will be created:
   - Username: `admin`
   - Email: `admin@servicehub.com`
   - Password: `admin123`

## 👥 User Roles & Access

### Admin (Default Account)
- **Login**: admin@servicehub.com / admin123
- **Access**: Full platform management
- **Features**: User management, provider approval, reports, analytics

### Service Provider
- **Registration**: Through registration form
- **Features**: Profile setup, service management, booking handling

### Customer
- **Registration**: Through registration form
- **Features**: Service browsing, booking, reviews

## 🔧 Configuration

### Database Configuration
Edit `config/database.php` to match your database settings.

### File Upload Settings
- Maximum file size: 5MB
- Allowed formats: PDF, JPG, PNG
- Upload directories: `uploads/profiles/`, `uploads/kyc/`, `uploads/reviews/`

### Email Configuration
To enable email notifications, configure SMTP settings in the functions file.

## 📊 Database Schema

### Core Tables
- **users**: User accounts and authentication
- **providers**: Service provider profiles
- **services**: Service listings
- **bookings**: Service bookings
- **reviews**: Customer reviews and ratings
- **reports**: User reports and disputes
- **notifications**: System notifications

### Key Relationships
- Users can be customers or providers
- Providers have services and receive bookings
- Customers make bookings and leave reviews
- Admins manage all entities

## 🔒 Security Features

- **Password Hashing**: Secure password storage using PHP password_hash()
- **SQL Injection Prevention**: Prepared statements for all database queries
- **XSS Protection**: Input sanitization and output escaping
- **File Upload Security**: File type and size validation
- **Session Management**: Secure session handling
- **Access Control**: Role-based access control

## 🎨 UI/UX Features

- **Responsive Design**: Mobile-friendly interface
- **Modern UI**: Clean and professional design
- **Interactive Elements**: Dynamic forms and real-time validation
- **User Feedback**: Success/error messages and notifications
- **Loading States**: Visual feedback for async operations

## 📱 Mobile Responsiveness

The application is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- All modern browsers

## 🔄 Booking System Logic

### Availability Checking
- Prevents double-booking for providers
- Validates booking times against existing bookings
- Considers service duration for overlap detection

### Booking Flow
1. Customer selects provider and service
2. Chooses date and time
3. System checks availability
4. Booking is created with pending status
5. Provider receives notification
6. Provider can confirm or reject booking
7. Customer receives status updates

## 📈 Analytics & Reporting

### Admin Dashboard Metrics
- Total users and providers
- Pending provider approvals
- Total bookings and revenue
- Recent activity and reports

### Provider Analytics
- Booking history and earnings
- Customer ratings and reviews
- Service performance metrics

## 🛡️ Error Handling

- Comprehensive error logging
- User-friendly error messages
- Graceful degradation
- Input validation and sanitization

## 🔧 Customization

### Styling
- Modify `assets/css/style.css` for custom styling
- Update color scheme and branding
- Customize layout and components

### Functionality
- Extend functions in `includes/functions.php`
- Add new features to respective modules
- Modify database schema as needed

## 📞 Support

For support and questions:
1. Check the documentation
2. Review error logs
3. Verify configuration settings
4. Test with default admin account

## 🔄 Updates & Maintenance

### Regular Maintenance
- Monitor error logs
- Update PHP and MySQL versions
- Backup database regularly
- Review and update security measures

### Feature Updates
- Add new service categories
- Implement payment integration
- Add advanced analytics
- Enhance mobile experience

## 📄 License

This project is created for educational and demonstration purposes.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

---

**ServiceHub** - Connecting customers with trusted service providers since 2024. #   m u l t i v e n d o r 
 
 #   p r o j e c t 1 _ s e r v i c e P r o v i d e r s 
 
 #   p r o j e c t 1 _ s e r v i c e P r o v i d e r s 
 
 