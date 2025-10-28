-- ---------------------------------------------------------------------
-- 1. DATA TRUNCATION AND FOREIGN KEY MANAGEMENT (FIXED ORDER)
-- ---------------------------------------------------------------------
-- Temporarily disable foreign key checks to allow truncation of tables
-- with dependencies, which is faster than DELETE FROM.
SET FOREIGN_KEY_CHECKS = 0;
-- Truncate all tables to completely clear all existing data
-- Tables are truncated in order of dependency (Children first, Parents last)
TRUNCATE TABLE `activity_logs`;
-- Depends on devices, users
TRUNCATE TABLE `schedules`;
-- Depends on devices, users
TRUNCATE TABLE `updates`;
-- Depends on devices, providers
TRUNCATE TABLE `device_assignments`;
-- Depends on devices, users
TRUNCATE TABLE `devices`;
-- Depends on providers
TRUNCATE TABLE `providers`;
-- Depends on users
TRUNCATE TABLE `users`;
-- Independent (Root table)
-- Reset AUTO_INCREMENT counters for all tables (optional, but good practice)
ALTER TABLE `users` AUTO_INCREMENT = 1;
ALTER TABLE `providers` AUTO_INCREMENT = 1;
ALTER TABLE `devices` AUTO_INCREMENT = 1;
ALTER TABLE `device_assignments` AUTO_INCREMENT = 1;
ALTER TABLE `schedules` AUTO_INCREMENT = 1;
ALTER TABLE `activity_logs` AUTO_INCREMENT = 1;
ALTER TABLE `updates` AUTO_INCREMENT = 1;
-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
-- ---------------------------------------------------------------------
-- 2. INSERT 10 TEST USERS
-- (user_id 1-5 for Admin/Provider roles, 6-10 for User roles)
-- NOTE: Passwords are in plain text as requested for easy testing.
-- You must hash these passwords if your application expects hashed values.
-- ---------------------------------------------------------------------
INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`)
VALUES (
        1,
        'Admin User 1',
        'admin1@test.com',
        'admin123',
        'admin'
    ),
    (
        2,
        'Admin User 2',
        'admin2@test.com',
        'admin123',
        'admin'
    ),
    (
        3,
        'Provider User 1',
        'provider1@test.com',
        'provider123',
        'provider'
    ),
    (
        4,
        'Provider User 2',
        'provider2@test.com',
        'provider123',
        'provider'
    ),
    (
        5,
        'Special Admin',
        'admin_special@test.com',
        'admin123',
        'admin'
    ),
    (
        6,
        'Standard User 1',
        'user1@test.com',
        'user123',
        'user'
    ),
    (
        7,
        'Standard User 2',
        'user2@test.com',
        'user123',
        'user'
    ),
    (
        8,
        'Standard User 3',
        'user3@test.com',
        'user123',
        'user'
    ),
    (
        9,
        'Standard User 4',
        'user4@test.com',
        'user123',
        'user'
    ),
    (
        10,
        'Guest User',
        'user5@test.com',
        'user123',
        'user'
    );
-- ---------------------------------------------------------------------
-- 3. INSERT TEST PROVIDERS (Linked to Provider Users)
-- ---------------------------------------------------------------------
INSERT INTO `providers` (
        `provider_id`,
        `company_name`,
        `support_contact`,
        `user_id`
    )
VALUES (101, 'Globex Corp IoT', 'support@globex.com', 3),
    -- Linked to Provider User 1 (ID 3)
    (102, 'SmartDevice Co.', 'help@smartdev.co', 4);
-- Linked to Provider User 2 (ID 4)
-- ---------------------------------------------------------------------
-- 4. INSERT TEST DEVICES (Linked to Providers)
-- ---------------------------------------------------------------------
INSERT INTO `devices` (
        `device_id`,
        `device_name`,
        `status`,
        `provider_id`,
        `compatibility_version`
    )
VALUES (1001, 'Living Room Light', 'ON', 101, 'v2.0'),
    (1002, 'Front Door Lock', 'OFF', 101, 'v3.1'),
    (1003, 'Garage Camera', 'ON', 102, 'v1.5'),
    (1004, 'Thermostat', 'OFF', 102, 'v4.0');
-- ---------------------------------------------------------------------
-- 5. INSERT DEVICE ASSIGNMENTS (Users controlling Devices)
-- ---------------------------------------------------------------------
INSERT INTO `device_assignments` (`user_id`, `device_id`)
VALUES (6, 1001),
    -- User 1 assigned to Light
    (6, 1002),
    -- User 1 assigned to Lock
    (7, 1003),
    -- User 2 assigned to Camera
    (8, 1004),
    -- User 3 assigned to Thermostat
    (1, 1001),
    -- Admin 1 assigned to Light
    (1, 1002),
    -- Admin 1 assigned to Lock
    (1, 1003),
    -- Admin 1 assigned to Camera
    (1, 1004);
-- Admin 1 assigned to Thermostat
-- ---------------------------------------------------------------------
-- 6. INSERT SCHEDULES
-- ---------------------------------------------------------------------
INSERT INTO `schedules` (
        `user_id`,
        `device_id`,
        `action`,
        `scheduled_time`,
        `status`,
        `executed_by`
    )
VALUES (
        6,
        1001,
        'OFF',
        NOW() + INTERVAL 1 HOUR,
        'pending',
        'user'
    ),
    (
        7,
        1003,
        'ON',
        NOW() + INTERVAL 2 HOUR,
        'pending',
        'scheduler'
    );
-- ---------------------------------------------------------------------
-- 7. INSERT ACTIVITY LOGS
-- ---------------------------------------------------------------------
INSERT INTO `activity_logs` (
        `user_id`,
        `device_id`,
        `action`,
        `executed_by`,
        `timestamp`
    )
VALUES (6, 1001, 'ON', 'user', NOW()),
    (1, 1002, 'UPDATED', 'admin', NOW()),
    (
        3,
        1004,
        'ADDED',
        'provider',
        NOW() - INTERVAL 1 DAY
    );