# 🏨 Hostel Roommate Finder

The **Hostel Roommate Finder** is a web-based application designed specifically for **SRM University** students to discover and connect with their **roommates and block mates**. This platform helps students register their hostel details and explore room and block information within the campus hostel system.

---

## 📌 Overview

This tool allows students to:
- Register their **hostel block** and **room number**
- Automatically view **roommate information**
- Explore hostel block details and occupants
- Enjoy a modern, **mobile-responsive UI**

---

## ✨ Features

### 1. User Profile & Hostel Registration
- Register hostel block and room number
- View personal profile (Name, Register Number, Class, Email)
- Edit and update hostel information

### 2. Roommate Discovery
- Automatically list roommates from the same room
- Show profile photo or initials
- One-click access to full profiles

### 3. Hostel Block Explorer
- Browse all registered hostel blocks
- View available rooms and their occupancy
- Click on a room to view occupants

### 4. Interactive UI
- Clean and modern responsive layout
- Smooth animations and transitions
- Intuitive, mobile-friendly design

---

## 🧑‍💻 Technical Specifications

### Backend
- **Language**: PHP 7.4+
- **Database**: MySQL
- **Authentication**: Session-based
- **Security**: Prepared statements, Input validation

### Frontend
- **Framework**: Bootstrap 5
- **Icons**: Font Awesome
- **Interactivity**: jQuery
- **Design**: Custom CSS, responsive grid layouts

---

## 🗄️ Database Structure

### `students` Table
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Auto-increment Primary Key |
| register_number | VARCHAR(20) | Unique student ID |
| name | VARCHAR(100) | Student full name |
| email | VARCHAR(100) | SRM email |
| class | VARCHAR(50) | Class or department |
| profile_photo | VARCHAR(255) | Path to profile photo |

### `hostel_survey` Table
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Auto-increment Primary Key |
| register_number | VARCHAR(20) | Foreign key to `students` table |
| name | VARCHAR(100) | Student name |
| email | VARCHAR(100) | Email |
| class | VARCHAR(50) | Class |
| block | VARCHAR(50) | Hostel block name |
| room_number | VARCHAR(10) | Room number |

---

## ⚙️ Installation Guide

### Requirements
- Web server (Apache/Nginx)
- PHP 7.4+
- MySQL 5.7+
- Composer (optional)

### Setup Steps

#### 1. Database Setup

Run the following SQL commands:

```sql

CREATE DATABASE hostel_finder;

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    register_number VARCHAR(20) UNIQUE,
    name VARCHAR(100),
    email VARCHAR(100),
    class VARCHAR(50),
    profile_photo VARCHAR(255)
);

CREATE TABLE hostel_survey (
    id INT AUTO_INCREMENT PRIMARY KEY,
    register_number VARCHAR(20),
    name VARCHAR(100),
    email VARCHAR(100),
    class VARCHAR(50),
    block VARCHAR(50),
    room_number VARCHAR(10),
    FOREIGN KEY (register_number) REFERENCES students(register_number)
);


