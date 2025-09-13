# HopeLink - Charity & Volunteer Management System

A web-based platform that connects volunteers with charitable organizations, manages donations, and coordinates volunteer trips.

## Features

### For Users
- User authentication (login/register)
- Profile management
- Donation system with VNPay integration
- Trip registration and management 
- Volunteer hour tracking
- Donation history viewing

### For Administrators
- User management
- Event/Trip creation and management
- Participant management
- Statistics and reporting
- Staff account management

## Tech Stack

- **Backend**: PHP 8.2+
- **Database**: MySQL
- **Payment Integration**: VNPay
- **Frontend**: HTML5, CSS3, JavaScript
- **Dependencies**: Managed via Composer

## Project Structure

```
├── admin/              # Admin panel files
├── Database/           # Database related files
├── images/            # Image assets
├── staff/             # Staff panel files
├── styles/            # CSS stylesheets
├── vendor/            # Composer dependencies
├── config.php         # Configuration file
├── index.php          # Main entry point
└── various .php files # Core functionality
```

## Installation

1. Clone the repository
2. Install dependencies:
```bash
composer install
```

3. Configure your database in `config.php`:
```php
$host = 'localhost';
$dbname = 'tuthien';
$user = 'root';
$pass = '123';
```

4. Configure VNPay settings in `config.php`:
```php
$vnpay_config = [
    'vnp_TmnCode' => 'YOUR_TMN_CODE',
    'vnp_HashSecret' => 'YOUR_HASH_SECRET',
    'vnp_Url' => 'VNPAY_API_URL',
    'vnp_ReturnUrl' => 'YOUR_RETURN_URL'
];
```

## Key Features

1. **User Authentication**
   - Secure login/registration
   - Password recovery
   - Session management

2. **Donation System**
   - VNPay integration
   - Transaction history
   - Receipt generation

3. **Volunteer Management**
   - Trip registration
   - Hour tracking
   - Activity history

4. **Admin Dashboard**
   - User management
   - Event creation
   - Statistics viewing
   - Staff management

## Security Features

- Password hashing
- CSRF protection
- Session security
- SQL injection prevention
- XSS protection

## Contributing

Please read the contributing guidelines before submitting pull requests.

## License

This project is licensed under the MIT License.