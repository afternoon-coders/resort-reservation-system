-- Create database
CREATE DATABASE IF NOT EXISTS resort_reservation_db;
USE resort_reservation_db;

-- 1. Guests Table
-- Every person booking a cottage goes here. 
-- Note: Email is NOT unique here to allow guest checkouts to happen multiple times.
CREATE TABLE Guests (
    guest_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL, 
    phone_number VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optimize email lookups, especially for Guest checkouts
CREATE INDEX idx_guest_email ON Guests(email);

-- 2. Users Table (The Authentication Layer)
-- Links to Guests if they have an account. guest_id is NULL for Admins/Staff.
CREATE TABLE Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    guest_id INT UNIQUE DEFAULT NULL, -- Links to Guest profile (Optional)
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    account_email VARCHAR(100) UNIQUE NOT NULL, -- Used specifically for login/recovery
    role ENUM('guest', 'staff', 'admin') DEFAULT 'guest',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES Guests(guest_id) ON DELETE SET NULL
);

-- Optimize user lookups by username
CREATE INDEX idx_user_username ON Users(username);

-- 3. Cottage_Types
CREATE TABLE Cottage_Types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- 4. Cottages
CREATE TABLE Cottages (
    cottage_id INT PRIMARY KEY AUTO_INCREMENT,
    cottage_number VARCHAR(20) NOT NULL UNIQUE,
    type_id INT NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    max_occupancy INT NOT NULL,
    status ENUM('Available', 'Maintenance', 'Occupied', 'Out of Order') DEFAULT 'Available',
    FOREIGN KEY (type_id) REFERENCES Cottage_Types(type_id)
);

-- Optimize queries searching for available cottages
CREATE INDEX idx_cottage_status ON Cottages(status);

-- 5. Reservations (The "Folder" for a booking)
CREATE TABLE Reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    guest_id INT NOT NULL, -- Points to the person who booked (User or Guest Checkout)
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_in_date DATETIME NOT NULL,
    check_out_date DATETIME NOT NULL,
    checked_in_at DATETIME NULL, -- Actual timestamp when guest was marked Checked-In
    checked_out_at DATETIME NULL, -- Actual timestamp when guest was marked Checked-Out
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('Pending', 'Confirmed', 'Checked-In', 'Checked-Out', 'Cancelled') DEFAULT 'Pending',
    notes TEXT,
    confirmation_token VARCHAR(255),
    token_expires_at DATETIME,
    CONSTRAINT chk_dates CHECK (check_out_date > check_in_date),
    FOREIGN KEY (guest_id) REFERENCES Guests(guest_id)
);

-- Optimize date range queries and active reservation checks
CREATE INDEX idx_reservation_dates ON Reservations(check_in_date, check_out_date);
CREATE INDEX idx_reservation_status ON Reservations(status);

-- 6. Reservation_Items (Support for multi-cottage bookings)
CREATE TABLE Reservation_Items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    cottage_id INT NOT NULL,
    price_at_booking DECIMAL(10, 2) NOT NULL, -- Snapshot of price at the time of booking
    FOREIGN KEY (reservation_id) REFERENCES Reservations(reservation_id) ON DELETE CASCADE,
    FOREIGN KEY (cottage_id) REFERENCES Cottages(cottage_id)
);

-- 7. Payments
CREATE TABLE Payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method ENUM('Cash', 'Credit Card', 'PayPal', 'Bank Transfer') NOT NULL,
    payment_status ENUM('Pending', 'Completed', 'Refunded', 'Failed') DEFAULT 'Completed',
    transaction_ref VARCHAR(100), 
    FOREIGN KEY (reservation_id) REFERENCES Reservations(reservation_id) ON DELETE CASCADE
);