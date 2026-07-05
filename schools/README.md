# Kenya EduHub - Schools Management System

A comprehensive school management system for Kenya EduHub that allows schools to manage students, classes, streams, and parents with a professional dashboard interface.

## Features

### Core Functionality
- **School Registration & Authentication**: Schools can register and login with secure authentication
- **Student Management**: Add, edit, view, and delete students with admission numbers
- **Class Management**: Create and manage classes with capacity tracking
- **Stream Management**: Organize students into streams within classes
- **Parent Management**: Register and manage parent information
- **Dashboard**: Overview with statistics and quick actions

### Security Features
- Rate limiting on all endpoints
- CSRF protection
- Input sanitization
- Password hashing
- Session management
- SQL injection prevention (prepared statements)

### Upcoming Features
- Academic performance tracking
- Attendance management
- Fee management
- School settings
- Parent portal access

## Installation

### 1. Database Setup

Import the database schema:

```bash
mysql -u root -p users_db < database/schools_schema.sql
```

This will create the following tables:
- `schools` - School accounts
- `classes` - Class information
- `streams` - Stream divisions
- `students` - Student records
- `parents` - Parent information
- `student_parents` - Student-parent relationships
- `academic_performance` - Student grades and marks
- `attendance` - Attendance records
- `fee_structure` - Fee configuration
- `fee_payments` - Payment records
- `school_sessions` - School login sessions
- `parent_logins` - Parent authentication
- `parent_sessions` - Parent login sessions

### 2. Directory Structure

```
schools/
├── api/
│   ├── config.php          # API configuration
│   ├── security.php        # Security functions
│   ├── auth.php            # Authentication functions
│   ├── register.php        # School registration
│   ├── login.php           # School login
│   ├── logout.php          # School logout
│   ├── students.php        # Student management API
│   ├── classes.php         # Class management API
│   ├── streams.php         # Stream management API
│   └── parents.php          # Parent management API
├── index.php               # Login/Register page
├── dashboard.php           # Main dashboard
├── students.php            # Student management UI
├── classes.php             # Class management UI
├── streams.php             # Stream management UI
├── parents.php             # Parent management UI
├── performance.php         # Performance tracking (placeholder)
├── attendance.php          # Attendance management (placeholder)
├── fees.php                # Fee management (placeholder)
└── settings.php            # School settings (placeholder)
```

### 3. Configuration

Ensure the main `config.php` in the root directory is properly configured with your database credentials.

## Usage

### School Registration

1. Navigate to `http://localhost/kenyaeduhub/schools/`
2. Click on "Register School" tab
3. Fill in the required information:
   - School Name
   - Email Address
   - Phone Number
   - Password (minimum 8 characters)
   - County
   - School Type (Primary, Secondary, College, University)
   - Address
4. Submit the form
5. Wait for admin approval (account status will be 'pending')

### School Login

1. Navigate to `http://localhost/kenyaeduhub/schools/`
2. Enter your registered email and password
3. Click "Login"
4. You will be redirected to the dashboard

### Managing Students

1. Go to Dashboard → Students
2. Click "Add Student" to register a new student
3. Fill in student details:
   - First Name, Last Name
   - Gender
   - Date of Birth
   - Admission Date
   - Class (optional)
   - Stream (optional)
   - Parent (optional)
4. An admission number will be auto-generated
5. Edit or delete students using the action buttons

### Managing Classes

1. Go to Dashboard → Classes
2. Click "Add Class" to create a new class
3. Fill in class details:
   - Class Name (e.g., Grade 1)
   - Class Level (e.g., Primary)
   - Capacity
4. View student count per class

### Managing Streams

1. Go to Dashboard → Streams
2. Click "Add Stream" to create a stream
3. Select the class and enter:
   - Stream Name (e.g., East)
   - Capacity
4. Filter streams by class

### Managing Parents

1. Go to Dashboard → Parents
2. Click "Add Parent" to register a parent
3. Fill in parent details:
   - First Name, Last Name
   - Email
   - Phone
   - ID Number
   - Relationship (Father, Mother, Guardian)
   - Address (optional)
4. View number of children linked to each parent

## API Endpoints

### Authentication
- `POST /api/register.php` - Register a new school
- `POST /api/login.php` - School login
- `POST /api/logout.php` - School logout

### Students
- `GET /api/students.php` - Get all students
- `POST /api/students.php` - Add new student
- `PUT /api/students.php` - Update student
- `DELETE /api/students.php?id={id}` - Delete student

### Classes
- `GET /api/classes.php` - Get all classes
- `POST /api/classes.php` - Add new class
- `PUT /api/classes.php` - Update class
- `DELETE /api/classes.php?id={id}` - Delete class

### Streams
- `GET /api/streams.php` - Get all streams
- `GET /api/streams.php?class_id={id}` - Get streams by class
- `POST /api/streams.php` - Add new stream
- `PUT /api/streams.php` - Update stream
- `DELETE /api/streams.php?id={id}` - Delete stream

### Parents
- `GET /api/parents.php` - Get all parents
- `POST /api/parents.php` - Add new parent
- `PUT /api/parents.php` - Update parent
- `DELETE /api/parents.php?id={id}` - Delete parent

## Security

### Rate Limiting
- Registration: 3 attempts per 15 minutes
- Login: 10 attempts per 5 minutes
- Student operations: 20 requests per 5 minutes

### Authentication
- Session-based authentication for schools
- Session tokens stored in database
- 24-hour session expiration
- Automatic session cleanup

### Input Validation
- All inputs are sanitized
- Email validation
- Password strength requirements
- Enum validation for specific fields

### Logging
- Security events logged to `logs/security/`
- Rate limit data stored in `logs/rate_limits/`

## Database Schema

### Key Tables

**schools**
- Stores school account information
- Includes school code, name, contact details, status

**students**
- Student records with admission numbers
- Links to classes, streams, and parents
- Status tracking (active, inactive, transferred, graduated)

**classes**
- Class information with capacity
- Linked to schools
- Student count tracking

**streams**
- Stream divisions within classes
- Capacity management
- Student count tracking

**parents**
- Parent/guardian information
- Contact details and relationship
- Children count tracking

## Future Enhancements

- [ ] Complete performance tracking module
- [ ] Complete attendance management module
- [ ] Complete fee management module
- [ ] Complete school settings module
- [ ] Parent portal with student viewing access
- [ ] SMS/Email notifications
- [ ] Report generation (PDF/Excel)
- [ ] Mobile app integration
- [ ] Multi-school admin panel
- [ ] Data export functionality

## Support

For issues or questions, please contact the development team.

## License

This is part of the Kenya EduHub project.
