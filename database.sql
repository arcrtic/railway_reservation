-- ============================================
-- RAILWAY RESERVATION SYSTEM - DATABASE SETUP
-- ============================================

CREATE DATABASE IF NOT EXISTS railway_reservation_db;
USE railway_reservation_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trains table
CREATE TABLE IF NOT EXISTS trains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    train_number VARCHAR(10) NOT NULL UNIQUE,
    train_name VARCHAR(100) NOT NULL,
    from_city VARCHAR(50) NOT NULL,
    to_city VARCHAR(50) NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    duration VARCHAR(20),
    total_seats INT DEFAULT 100,
    available_seats INT DEFAULT 100,
    fare_ac1 DECIMAL(10,2) DEFAULT 2500.00,
    fare_ac3 DECIMAL(10,2) DEFAULT 1200.00,
    fare_sleeper DECIMAL(10,2) DEFAULT 500.00,
    status ENUM('active','cancelled','delayed') DEFAULT 'active',
    days_of_run VARCHAR(100) DEFAULT 'Mon,Tue,Wed,Thu,Fri,Sat,Sun'
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pnr VARCHAR(10) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    train_id INT NOT NULL,
    passenger_name VARCHAR(100) NOT NULL,
    passenger_age INT,
    passenger_gender ENUM('male','female','other'),
    contact_number VARCHAR(15),
    from_city VARCHAR(50),
    to_city VARCHAR(50),
    journey_date DATE NOT NULL,
    ticket_class ENUM('AC First Class','AC 3 Tier','Sleeper') NOT NULL,
    seat_number VARCHAR(10),
    fare DECIMAL(10,2),
    payment_status ENUM('pending','paid','refunded') DEFAULT 'paid',
    booking_status ENUM('confirmed','cancelled','waitlisted') DEFAULT 'confirmed',
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (train_id) REFERENCES trains(id)
);

-- Contacts table
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    subject VARCHAR(200),
    message TEXT,
    status ENUM('new','read','replied') DEFAULT 'new',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- SEED DATA
-- ============================================

-- Admin user (password: admin123)
INSERT INTO users (full_name, username, email, password, phone, role) VALUES
('Admin User', 'admin', 'admin@railway.in', 'admin123', '9999999999', 'admin');

-- Sample trains
INSERT INTO trains (train_number, train_name, from_city, to_city, departure_time, arrival_time, duration, total_seats, available_seats, fare_ac1, fare_ac3, fare_sleeper) VALUES
('12301', 'Rajdhani Express', 'New Delhi', 'Mumbai', '16:00:00', '08:35:00', '16h 35m', 120, 45, 3200.00, 1800.00, 750.00),
('12951', 'Mumbai Rajdhani', 'Mumbai', 'New Delhi', '17:00:00', '08:00:00', '15h 00m', 120, 32, 3200.00, 1800.00, 750.00),
('12259', 'Shatabdi Express', 'New Delhi', 'Kolkata', '06:00:00', '14:25:00', '8h 25m', 100, 78, 2800.00, 1500.00, 0.00),
('12621', 'Tamil Nadu Express', 'New Delhi', 'Chennai', '22:30:00', '07:00:00', '32h 30m', 150, 12, 3500.00, 2000.00, 900.00),
('12431', 'Trivandrum Rajdhani', 'New Delhi', 'Pune', '11:30:00', '20:15:00', '8h 45m', 120, 65, 2900.00, 1600.00, 680.00),
('22691', 'Rajdhani Express', 'Bangalore', 'New Delhi', '20:00:00', '05:25:00', '33h 25m', 130, 0, 4200.00, 2400.00, 1100.00),
('12285', 'Duronto Express', 'Mumbai', 'Kolkata', '14:05:00', '22:45:00', '32h 40m', 110, 55, 3100.00, 1700.00, 800.00),
('12009', 'Shatabdi Express', 'Mumbai', 'Ahmedabad', '06:25:00', '13:00:00', '6h 35m', 90, 40, 1800.00, 900.00, 0.00),
('12723', 'Telangana Express', 'Hyderabad', 'New Delhi', '06:20:00', '10:30:00', '28h 10m', 140, 88, 3000.00, 1650.00, 720.00),
('12875', 'Neelachal Express', 'Kolkata', 'Mumbai', '23:55:00', '07:30:00', '31h 35m', 125, 23, 2900.00, 1550.00, 680.00);

-- ============================================
-- NEW TABLES FOR ADDITIONAL FEATURES
-- ============================================

-- Passengers table (for multi-passenger booking)
CREATE TABLE IF NOT EXISTS booking_passengers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    passenger_name VARCHAR(100) NOT NULL,
    passenger_age INT,
    passenger_gender ENUM('male','female','other'),
    seat_number VARCHAR(10),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Add profile photo column to users if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS date_of_birth DATE DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS total_trips INT DEFAULT 0;
