# School Management System (Kenya EduHub) Requirements

## Overview

A comprehensive school management system comparable to Zeraki.

## Core Modules

### 1. Student Management

-   Student admission and registration
-   Student profiles (photo, contacts, guardians, medical info)
-   Class and stream assignment
-   Student transfers
-   Student ID generation
-   Attendance
-   Discipline records
-   Alumni/archive

### 2. Academic Management

-   Academic years and terms
-   Classes and streams
-   Subjects
-   Teacher-subject allocation
-   Timetable
-   Lesson plans
-   Syllabus coverage
-   Assignments

### 3. Examination & Results

-   Exam creation (CAT, Midterm, Endterm, Mock)
-   Marks entry
-   Automatic grading
-   Ranking
-   Report cards
-   Performance analytics
-   Historical performance
-   CBC / 8-4-4 grading support

### 4. Fees & Finance

-   Fee structures
-   Invoicing
-   Payment recording
-   M-Pesa integration
-   Receipt generation
-   Arrears
-   Discounts & scholarships
-   Financial reports

### 5. Parent Portal

-   Results
-   Fee balances
-   Attendance
-   Announcements
-   Messaging

### 6. Teacher Portal

-   Attendance
-   Marks entry
-   Timetable
-   Assignments
-   Communication

### 7. Student Portal

-   Results
-   Assignments
-   Timetable
-   Attendance
-   Announcements

### 8. Communication

-   SMS
-   Email
-   Push notifications
-   Circulars
-   Emergency alerts

### 9. Library

-   Book catalog
-   Borrow/return
-   Fines
-   Reports

### 10. Inventory

-   Assets
-   Store items
-   Stock movement
-   Reports

### 11. Staff Management

-   Staff profiles
-   Attendance
-   Leave
-   Payroll (optional)
-   Performance

### 12. Transport (Optional)

-   Routes
-   Vehicles
-   Drivers
-   Student allocation

### 13. Hostel (Optional)

-   Room allocation
-   Bed management
-   Occupancy

### 14. Reports

-   Enrollment
-   Attendance
-   Fees
-   Exams
-   Staff
-   Inventory
-   Custom PDF/Excel exports

### 15. Security

-   Role-based permissions
-   Audit logs
-   Password reset
-   Two-factor authentication (optional)
-   Backups

## Dashboard

-   Total students
-   Total teachers
-   Fees collected today
-   Outstanding balances
-   Attendance summary
-   Upcoming exams
-   Recent announcements

## Integrations

-   M-Pesa Daraja API
-   SMS gateway
-   Email
-   PDF generation
-   Excel import/export

## User Roles

-   Super Admin
-   School Admin
-   Principal
-   Deputy Principal
-   Accountant
-   Teacher
-   Parent
-   Student
-   Librarian
-   Store Keeper

## Nice-to-Have Features

-   AI performance insights
-   Multi-school support
-   Mobile-friendly UI
-   Offline sync
-   Cloud backup
-   Dark mode

## Suggested Development Order

1.  Authentication
2.  Student Management
3.  Academic Setup
4.  Exams
5.  Fees & M-Pesa
6.  Parent Portal
7.  Teacher Portal
8.  Reports
9.  Communication
10. Optional modules

## Suggested Database Tables

-   users
-   roles
-   permissions
-   students
-   guardians
-   staff
-   teachers
-   classes
-   streams
-   subjects
-   academic_years
-   terms
-   timetables
-   attendance
-   exams
-   exam_results
-   grades
-   fee_structures
-   invoices
-   payments
-   receipts
-   announcements
-   messages
-   books
-   book_loans
-   inventory_items
-   stock_transactions
-   audit_logs
