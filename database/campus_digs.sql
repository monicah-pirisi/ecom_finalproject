
-- CampusDigs Kenya Database Schema
-- Complete database structure for student housing platform

-- Drop existing database if it exists (CAREFUL: This deletes all data!)
DROP DATABASE IF EXISTS ecommerce_2025A_monicah_lekupe;

-- Database: `ecommerce_2025A_monicah_lekupe`
--
CREATE DATABASE IF NOT EXISTS `ecommerce_2025A_monicah_lekupe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `ecommerce_2025A_monicah_lekupe`;



-- TABLE: users
-- Stores all user accounts (students, landlords, admins)


CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_type ENUM('student', 'landlord', 'admin') NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  phone VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  
  -- Additional fields
  university VARCHAR(150) DEFAULT NULL,                -- For students
  student_id VARCHAR(50) DEFAULT NULL,                 -- For students
  student_id_document VARCHAR(255) DEFAULT NULL,       -- Path to uploaded student ID
  landlord_id_document VARCHAR(255) DEFAULT NULL,      -- Path to landlord ID/title deed
  
  -- Verification status
  email_verified TINYINT(1) DEFAULT 0,
  phone_verified TINYINT(1) DEFAULT 0,
  account_verified TINYINT(1) DEFAULT 0,               -- Admin verification
  
  -- Security fields
  login_attempts INT DEFAULT 0,
  lockout_until DATETIME DEFAULT NULL,
  last_login DATETIME DEFAULT NULL,
  
  -- Status
  account_status ENUM('active', 'suspended', 'deleted') DEFAULT 'active',
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX idx_email (email),
  INDEX idx_user_type (user_type),
  INDEX idx_account_status (account_status),
  INDEX idx_email_verified (email_verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: properties
-- Stores property listings

CREATE TABLE IF NOT EXISTS properties (
  id INT PRIMARY KEY AUTO_INCREMENT,
  landlord_id INT NOT NULL,
  
  -- Basic information
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(150) NOT NULL,
  address TEXT DEFAULT NULL,
  
  -- Pricing
  price_monthly DECIMAL(10,2) NOT NULL,
  security_deposit DECIMAL(10,2) NOT NULL,
  
  -- Property details
  room_type ENUM('shared', 'private', 'studio', 'one_bedroom', 'two_bedroom') NOT NULL,
  capacity INT DEFAULT 1,                              -- Number of occupants
  
  -- Location details
  distance_from_campus DECIMAL(5,2) DEFAULT NULL,     -- In kilometers
  university_nearby VARCHAR(150) DEFAULT NULL,
  
  -- Safety information
  safety_score DECIMAL(2,1) DEFAULT 0.0,              -- 0.0 to 5.0 stars
  has_cctv TINYINT(1) DEFAULT 0,
  has_security_guard TINYINT(1) DEFAULT 0,
  has_secure_entry TINYINT(1) DEFAULT 0,
  
  -- Amenities (stored as JSON or separate table)
  amenities TEXT DEFAULT NULL,                         -- JSON array of amenities
  
  -- Keywords for search optimization
  keywords VARCHAR(500) DEFAULT NULL,
  
  -- Images
  main_image VARCHAR(255) DEFAULT NULL,
  
  -- Verification and status
  is_verified TINYINT(1) DEFAULT 0,
  is_premium TINYINT(1) DEFAULT 0,
  status ENUM('active', 'inactive', 'pending', 'rejected', 'expired') DEFAULT 'pending',
  rejection_reason TEXT DEFAULT NULL,
  
  -- Booking information
  available_from DATE DEFAULT NULL,
  min_lease_months INT DEFAULT 4,
  max_lease_months INT DEFAULT 12,
  
  -- Statistics
  view_count INT DEFAULT 0,
  booking_count INT DEFAULT 0,
  wishlist_count INT DEFAULT 0,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULL DEFAULT NULL,
  
  -- Foreign keys
  FOREIGN KEY (landlord_id) REFERENCES users(id) ON DELETE CASCADE,
  
  -- Indexes for performance
  INDEX idx_landlord (landlord_id),
  INDEX idx_location (location),
  INDEX idx_price (price_monthly),
  INDEX idx_status (status),
  INDEX idx_verified (is_verified),
  INDEX idx_room_type (room_type),
  FULLTEXT INDEX idx_search (title, description, location, keywords)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: property_images
-- Stores multiple images for each property


CREATE TABLE IF NOT EXISTS property_images (
  id INT PRIMARY KEY AUTO_INCREMENT,
  property_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  is_main TINYINT(1) DEFAULT 0,
  display_order INT DEFAULT 0,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  INDEX idx_property (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: wishlist
-- Stores student favorite properties

CREATE TABLE IF NOT EXISTS wishlist (
  id INT PRIMARY KEY AUTO_INCREMENT,
  student_id INT NOT NULL,
  property_id INT NOT NULL,
  added_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  
  -- Prevent duplicate wishlist entries
  UNIQUE INDEX idx_wishlist_unique (student_id, property_id),
  INDEX idx_student (student_id),
  INDEX idx_property (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: bookings
-- Stores property booking requests

CREATE TABLE IF NOT EXISTS bookings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  booking_reference VARCHAR(20) UNIQUE NOT NULL,      -- e.g., CD202501001
  
  -- Parties involved
  student_id INT NOT NULL,
  property_id INT NOT NULL,
  landlord_id INT NOT NULL,
  
  -- Booking details
  move_in_date DATE NOT NULL,
  move_out_date DATE DEFAULT NULL,
  lease_duration_months INT NOT NULL,
  
  -- Pricing
  monthly_rent DECIMAL(10,2) NOT NULL,
  security_deposit DECIMAL(10,2) NOT NULL,
  total_amount DECIMAL(10,2) NOT NULL,
  commission_amount DECIMAL(10,2) NOT NULL,
  landlord_payout DECIMAL(10,2) NOT NULL,
  
  -- Student message
  message TEXT DEFAULT NULL,
  
  -- Status tracking
  status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending',
  payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
  rejection_reason TEXT DEFAULT NULL,
  cancellation_reason TEXT DEFAULT NULL,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  approved_at DATETIME DEFAULT NULL,
  rejected_at DATETIME DEFAULT NULL,
  cancelled_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  
  -- Foreign keys
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  FOREIGN KEY (landlord_id) REFERENCES users(id) ON DELETE CASCADE,
  
  -- Indexes
  INDEX idx_student (student_id),
  INDEX idx_landlord (landlord_id),
  INDEX idx_property (property_id),
  INDEX idx_status (status),
  INDEX idx_booking_ref (booking_reference)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: payments
-- Stores payment transactions


CREATE TABLE IF NOT EXISTS payments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  booking_id INT DEFAULT NULL,
  student_id INT NOT NULL,
  property_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(10) DEFAULT 'KES',
  payment_method VARCHAR(50) NOT NULL,
  payment_reference VARCHAR(255) NOT NULL UNIQUE,
  authorization_code VARCHAR(255) DEFAULT NULL,
  payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  paid_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,

  INDEX idx_student (student_id),
  INDEX idx_property (property_id),
  INDEX idx_booking (booking_id),
  INDEX idx_reference (payment_reference),
  INDEX idx_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: payment_installments
-- Tracks installment payment schedule

CREATE TABLE IF NOT EXISTS payment_installments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  booking_id INT NOT NULL,
  installment_number INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  due_date DATE NOT NULL,
  status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
  payment_id INT DEFAULT NULL,
  paid_at DATETIME DEFAULT NULL,
  
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
  INDEX idx_booking (booking_id),
  INDEX idx_status (status),
  INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: reviews
-- Stores property reviews from students

CREATE TABLE IF NOT EXISTS reviews (
  id INT PRIMARY KEY AUTO_INCREMENT,
  property_id INT NOT NULL,
  student_id INT NOT NULL,
  booking_id INT NOT NULL,
  
  -- Review content
  rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
  title VARCHAR(200) DEFAULT NULL,
  comment TEXT DEFAULT NULL,
  
  -- Review aspects
  cleanliness_rating INT DEFAULT NULL,
  safety_rating INT DEFAULT NULL,
  value_rating INT DEFAULT NULL,
  location_rating INT DEFAULT NULL,
  landlord_rating INT DEFAULT NULL,
  
  -- Moderation
  is_approved TINYINT(1) DEFAULT 1,
  is_flagged TINYINT(1) DEFAULT 0,
  flag_reason TEXT DEFAULT NULL,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  
  -- Prevent duplicate reviews per booking
  UNIQUE INDEX idx_review_unique (booking_id, student_id),
  INDEX idx_property (property_id),
  INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: notifications
-- Stores user notifications

CREATE TABLE IF NOT EXISTS notifications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  
  -- Notification details
  type VARCHAR(50) NOT NULL,                           -- e.g., 'booking_approved', 'payment_due'
  title VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  link VARCHAR(255) DEFAULT NULL,
  
  -- Status
  is_read TINYINT(1) DEFAULT 0,
  read_at DATETIME DEFAULT NULL,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user (user_id),
  INDEX idx_read (is_read),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: messages
-- Stores messages between students and landlords

CREATE TABLE IF NOT EXISTS messages (
  id INT PRIMARY KEY AUTO_INCREMENT,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  property_id INT DEFAULT NULL,
  booking_id INT DEFAULT NULL,
  
  -- Message content
  subject VARCHAR(200) DEFAULT NULL,
  message TEXT NOT NULL,
  
  -- Status
  is_read TINYINT(1) DEFAULT 0,
  read_at DATETIME DEFAULT NULL,
  
  -- Timestamps
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
  
  INDEX idx_sender (sender_id),
  INDEX idx_receiver (receiver_id),
  INDEX idx_property (property_id),
  INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- TABLE: activity_logs
-- Logs important user actions for security and auditing

CREATE TABLE IF NOT EXISTS activity_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  description TEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user (user_id),
  INDEX idx_action (action),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INSERT DEFAULT ADMIN ACCOUNT
-- Username: admin@campusdigs.co.ke
-- Password: Admin@2025 (change this immediately!)


INSERT INTO users (user_type, full_name, email, phone, password, email_verified, account_verified, account_status) 
VALUES (
  'admin', 
  'System Administrator', 
  'admin@campusdigs.co.ke', 
  '+254700000000', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Password: Admin@2025
  1, 
  1, 
  'active'
);


-- TRIGGERS

-- Auto-generate booking reference
DELIMITER //
CREATE TRIGGER before_booking_insert
BEFORE INSERT ON bookings
FOR EACH ROW
BEGIN
  IF NEW.booking_reference IS NULL OR NEW.booking_reference = '' THEN
    SET NEW.booking_reference = CONCAT('CD', DATE_FORMAT(NOW(), '%Y%m'), LPAD((SELECT COALESCE(MAX(id), 0) + 1 FROM bookings), 4, '0'));
  END IF;
END//
DELIMITER ;

-- Update property safety score based on reviews
DELIMITER //
CREATE TRIGGER after_review_insert
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
  UPDATE properties 
  SET safety_score = (
    SELECT AVG(safety_rating) 
    FROM reviews 
    WHERE property_id = NEW.property_id AND is_approved = 1
  )
  WHERE id = NEW.property_id;
END//
DELIMITER ;

-- VIEWS FOR REPORTING

-- View: Active properties with landlord info
CREATE OR REPLACE VIEW v_active_properties AS
SELECT 
  p.*,
  u.full_name AS landlord_name,
  u.email AS landlord_email,
  u.phone AS landlord_phone,
  u.account_verified AS landlord_verified,
  (SELECT COUNT(*) FROM bookings WHERE property_id = p.id) AS total_bookings,
  (SELECT AVG(rating) FROM reviews WHERE property_id = p.id AND is_approved = 1) AS avg_rating
FROM properties p
JOIN users u ON p.landlord_id = u.id
WHERE p.status = 'active' AND u.account_status = 'active';

-- View: Booking summary
CREATE OR REPLACE VIEW v_booking_summary AS
SELECT 
  b.*,
  s.full_name AS student_name,
  s.email AS student_email,
  l.full_name AS landlord_name,
  p.title AS property_title,
  p.location AS property_location
FROM bookings b
JOIN users s ON b.student_id = s.id
JOIN users l ON b.landlord_id = l.id
JOIN properties p ON b.property_id = p.id;

