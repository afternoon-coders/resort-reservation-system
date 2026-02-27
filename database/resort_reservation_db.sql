-- Create database
CREATE DATABASE IF NOT EXISTS resort_reservation_db;
USE resort_reservation_db;


-- 1. Create the Users table (Parent)
-- Handles optional account logins
CREATE TABLE Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    account_email VARCHAR(100) UNIQUE NOT NULL,
    role VARCHAR(20) DEFAULT 'guest',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Create the Cottage_Types table (Parent)
-- Master list of all possible cottage types
CREATE TABLE Cottage_Types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255)
);

-- 3. Create the Cottages table (Parent)
-- The physical cottages
CREATE TABLE Cottages (
    cottage_id INT PRIMARY KEY AUTO_INCREMENT,
    cottage_number VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    max_occupancy INT NOT NULL,
    is_available BOOLEAN DEFAULT TRUE
);

-- 4. Create the Cottage_Type_Mapping table (Child of Cottages & Cottage_Types)
-- Links cottages to their multiple types
CREATE TABLE Cottage_Type_Mapping (
    cottage_id INT,
    type_id INT,
    PRIMARY KEY (cottage_id, type_id),
    FOREIGN KEY (cottage_id) REFERENCES Cottages(cottage_id) ON DELETE CASCADE,
    FOREIGN KEY (type_id) REFERENCES Cottage_Types(type_id) ON DELETE CASCADE
);

-- 5. Create the Guests table (Child of Users)
-- Holds guest details for the booking, optionally linked to a user account
CREATE TABLE Guests (
    guest_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT, -- NULL means they checked out as a guest without an account
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    contact_email VARCHAR(100) NOT NULL, 
    phone_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL 
);

-- 6. Create the Reservations table (Child of Guests & Cottages)
-- Connects a guest to a cottage for a specific date range
CREATE TABLE Reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    guest_id INT NOT NULL,
    cottage_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending', -- Pending, Confirmed, Checked-In, Checked-Out, Cancelled
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (guest_id) REFERENCES Guests(guest_id) ON DELETE CASCADE,
    FOREIGN KEY (cottage_id) REFERENCES Cottages(cottage_id) ON DELETE RESTRICT
);

-- 7. Create the Payments table (Child of Reservations)
-- Tracks financial transactions for a reservation
CREATE TABLE Payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50) NOT NULL, -- Credit Card, Cash, PayPal, etc.
    payment_status VARCHAR(20) DEFAULT 'Completed', -- Completed, Refunded, Failed
    FOREIGN KEY (reservation_id) REFERENCES Reservations(reservation_id) ON DELETE CASCADE
);