# Kenya EduHub - Comprehensive School Management System

A full-featured PHP-based school management system with integrated M-Pesa payment processing, multi-role access control, and comprehensive academic management tools.

## System Overview

Kenya EduHub is a complete educational management platform designed for Kenyan schools, featuring:
- Multi-user role system (Admin, School, Finance Manager, Teacher, Parent, Librarian)
- Integrated M-Pesa payment processing (STK Push, B2C withdrawals)
- Complete academic management (attendance, grades, exams, timetable)
- Library management with QR code support
- Financial tracking and reporting
- SMS notifications for all transactions
- Mobile app integration (Android)

## Prerequisites

- PHP 7.4+ (PHP 8 recommended)
- MySQL / MariaDB
- Apache (XAMPP recommended for local development)
- M-Pesa Daraja API credentials (for payment processing)
- SMS API credentials (Mobitech or TextSMS)

## Quick Setup

1. Place the project in your webroot (e.g. `C:\xampp\htdocs\kenyaeduhub`)
2. Create a MySQL database and import SQL files from the [database](database) folder
3. Update database credentials in [config.php](config.php)
4. Configure M-Pesa credentials in [config/mpesa_config.php](config/mpesa_config.php)
5. Configure SMS settings in admin panel
6. Ensure `uploads/` is writable by the web server
7. Visit `http://localhost/kenyaeduhub` to open the site

## User Roles & Access

### Super Admin
- Full system administration
- School account management
- User management across all roles
- System settings and configuration
- Access: `/admin`

### Schools
- Student management
- Teacher management
- Class and stream management
- Subject management
- Fee structure management
- Attendance tracking
- Exam and results management
- Disciplinary actions
- Withdrawal PIN management
- Access: `/schools`

### Finance Managers
- School balance management
- Withdrawal processing (with PIN verification)
- Financial reporting
- Invoice management
- Payment tracking
- Access: `/finance-managers`

### Teachers
- Attendance marking
- Assignment management
- Grade entry
- Performance tracking
- Student-subject assignment
- Duty management
- Access: `/teachers`

### Parents
- Fee payment via M-Pesa
- Library fine payments
- Children's performance viewing
- Attendance records
- Assignment tracking
- Access: `/parents`

### Librarians
- Book management
- Borrowing and returns
- Fine management
- QR code generation
- Reservation system
- Access: `/librarians`

## Core Features

### Payment System

#### M-Pesa Integration
- **STK Push**: Parents can pay fees via M-Pesa STK Push
- **B2C Withdrawals**: Schools can withdraw funds via M-Pesa B2C
- **Callback Handling**: Automatic transaction status updates
- **SMS Notifications**: Real-time SMS for all transactions
- **Multi-Environment**: Works on localhost (ngrok) and live hosting

#### Payment Types
- School fee payments
- Library fine payments
- Withdrawal processing
- Balance tracking

#### Security Features
- PIN verification for withdrawals (separate from login credentials)
- Transaction reference tracking
- Receipt number generation
- Balance deduction only after successful M-Pesa confirmation

### Academic Management

#### Student Management
- Student registration and profiles
- Parent-student linking
- Class and stream assignment
- Academic performance tracking
- Disciplinary records

#### Teacher Management
- Teacher profiles and assignments
- Subject allocation
- Class teacher assignments
- Duty assignments
- Performance tracking

#### Attendance System
- Daily attendance marking
- Attendance reports
- Parent notifications
- Attendance analytics

#### Grading & Exams
- Exam type management
- Grade entry and calculation
- Result generation
- Performance reports
- Parent access to results

#### Timetable Management
- Class scheduling
- Subject allocation
- Teacher assignment
- Calendar integration

### Library Management

#### Book Management
- Book catalog with categories
- QR code generation for books
- Book details and tracking
- Import/Export functionality

#### Circulation
- Book borrowing system
- Return processing
- Reservation management
- Overdue tracking

#### Fine Management
- Automatic fine calculation
- M-Pesa fine payment integration
- Fine payment SMS notifications
- Fine history tracking

### Financial Management

#### School Balance
- Real-time balance tracking
- Transaction history
- Withdrawal management
- Payment reconciliation

#### Fee Management
- Fee structure setup
- Term-based fee collection
- Payment tracking
- Invoice generation

#### Reporting
- Financial reports
- Payment summaries
- Withdrawal history
- Balance statements

### Communication

#### SMS Notifications
- Payment confirmations (M-Pesa style format)
- Withdrawal notifications
- Fee payment alerts
- Library fine payment confirmations
- Configurable SMS providers (Mobitech, TextSMS)

#### Email Integration
- PHPMailer integration
- User notifications
- System alerts
- Password recovery

## API Endpoints

### Root API (`/api`)
- User authentication
- Resource management
- File upload/download

### Schools API (`/schools/api`)
- Student management
- Teacher management
- Fee payment status checking
- B2C withdrawal status

### Parents API (`/parents/api`)
- M-Pesa fee payment initiation
- Payment status checking
- Fine payment status

### Finance Managers API (`/finance-managers/api`)
- B2C withdrawal processing
- Withdrawal status checking
- Balance management

### Librarians API (`/librarians/api`)
- M-Pesa fine payment initiation
- Fine payment status checking
- Book management

## Configuration Files

### Main Configuration
- [config.php](config.php) — Main application configuration
- [config/mpesa_config.php](config/mpesa_config.php) — M-Pesa API settings
- [config/database.php](config/database.php) — Database connection settings

### Directory Structure
```
kenyaeduhub/
├── admin/              # Super admin panel
├── schools/            # School management system
├── finance-managers/   # Financial management
├── teachers/           # Teacher portal
├── parents/            # Parent portal
├── librarians/         # Library management
├── api/                # Root API endpoints
├── config/             # Configuration files
├── database/          # SQL dumps and migrations
├── sms/                # SMS integration
├── PHPMailer/          # Email library
├── uploads/            # User uploads
└── anroid/             # Android app source
```

## Security Features

### Authentication
- Role-based access control
- Session management
- CSRF protection
- Secure password hashing

### Payment Security
- PIN verification for withdrawals
- Transaction reference tracking
- Callback validation
- Balance deduction after confirmation only

### Data Protection
- SQL injection prevention (prepared statements)
- XSS protection
- File upload validation
- Secure file serving

## SMS Notification Format

All SMS notifications follow M-Pesa format:
```
{ReceiptNumber} Confirmed. KES {Amount} {transaction details} on {date} at {time}. New balance is KES {balance}.
```

## M-Pesa Integration

### Supported Operations
- STK Push for fee payments
- B2C for withdrawals
- Automatic callback handling
- Sandbox and production environments

### Callback URLs
- Fee payments: `/parents/api/mpesa_callback.php`
- Fine payments: `/librarians/api/mpesa_fine_callback.php`
- Withdrawals: `/schools/payments/b2c/b2c_result.php` and `/finance-managers/b2c/b2c_result.php`

## Mobile App

Android app available in `/anroid/` directory with:
- React Native framework
- Parent portal access
- Fee payment functionality
- Performance tracking
- Real-time notifications

## Database

### Key Tables
- `users` — System users
- `schools` — School accounts
- `students` — Student records
- `teachers` — Teacher records
- `parents` — Parent accounts
- `library_fines` — Library fine tracking
- `fee_payments` — Fee payment records
- `school_withdrawals` — Withdrawal tracking
- `school_balances` — School balance management
- `transactions` — M-Pesa transaction logs

## Development Notes

### Testing Environment
- Use ngrok for localhost M-Pesa testing
- Safaricom sandbox credentials for testing
- Test phone: 254708374149 (sandbox)

### Production Deployment
- Update M-Pesa credentials to production
- Configure live callback URLs
- Enable HTTPS
- Set up proper file permissions
- Configure production SMS API keys
- Remove test files and debug logging

### Known Limitations
- Sandbox callbacks may be unreliable (Safaricom limitation)
- Email verification pending implementation
- Contact change cooldown period pending
- IP whitelisting for callbacks recommended

## Support & Maintenance

### Log Files
- PHP error logs: Check XAMPP PHP error log
- M-Pesa callbacks: Logged in callback files
- Transaction logs: Stored in database

### Common Issues
- M-Pesa callbacks not received: Check ngrok tunnel and callback URL
- SMS not sending: Verify SMS API credentials and admin settings
- Balance not updating: Check callback processing logs

## Current Status

**Completion: ~86%**

**Completed Features:**
- Multi-role user system
- M-Pesa payment integration (STK Push, B2C)
- SMS notifications for all transactions
- PIN verification for withdrawals
- Academic management (attendance, grades, exams)
- Library management with QR codes
- Financial tracking and reporting
- Mobile app integration

**Pending Features:**
- Email verification for withdrawal confirmation
- Contact change cooldown period
- IP whitelisting for callbacks
- Rate limiting on APIs

## License

Proprietary - Kenya EduHub

## Version

1.0.0 - Production Ready (Beta)

---
Last updated: July 28, 2026
