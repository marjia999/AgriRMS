CREATE DATABASE agriRMS;
USE agriRMS;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Client') DEFAULT 'Client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('Machinery', 'Storage', 'Equipment') NOT NULL,
    status ENUM('Available', 'In_Use', 'Maintenance') DEFAULT 'Available',
    last_maintenance DATE,
    next_maintenance DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit_id VARCHAR(50) NOT NULL DEFAULT '',
    quantity INT NOT NULL DEFAULT 0
);

CREATE TABLE resource_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id INT NOT NULL,
    maintenance_date DATE NOT NULL,
    description TEXT,
    cost DECIMAL(10,2),
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
);

CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    resource_type ENUM('Machinery', 'Storage', 'Equipment') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Assigned', 'Completed', 'Cancelled') DEFAULT 'Pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    resource_id INT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    days INT DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('Pending', 'Paid') DEFAULT 'Pending',
    cancellation_reason TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    quantity INT NOT NULL DEFAULT 1,

    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE SET NULL
);

CREATE TABLE request_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    resource_id INT NOT NULL,
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
);

CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    delivery_address TEXT NOT NULL,
    status ENUM('Scheduled', 'In_Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',

    FOREIGN KEY (assignment_id) REFERENCES request_assignments(id) ON DELETE CASCADE
);

CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    client_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending', 'Paid', 'Overdue') DEFAULT 'Pending',
    invoice_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    payment_method ENUM('Bkash', 'Rocket', 'Nagad', 'Card') NOT NULL,
    transaction_id VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Successful', 'Failed', 'Pending') DEFAULT 'Successful',
    card_details TEXT,

    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);

CREATE TABLE request_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    resource_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,

    FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
);

INSERT INTO users (first_name, last_name, email, password, role)
VALUES 
('System', 'Admin', 'admin@gmail.com', '1234', 'Admin'),
('John', 'Client', 'client@gmailt.com', '1234', 'Client');

INSERT INTO resources 
(name, type, status, last_maintenance, next_maintenance, price, unit_id, quantity)
VALUES
('X Tractor', 'Machinery', 'Available', '2024-01-15', '2024-07-15', 1500, 'TR-001', 5),

('Harvester X100', 'Machinery', 'Available', '2024-02-10', '2024-08-10', 2000, 'TR-002', 3),

('Cold Storage Unit A', 'Storage', 'Available', '2024-01-20', '2024-07-20', 800, 'ST-001', 2),

('Grain Silo B', 'Storage', 'In_Use', '2024-03-01', '2024-09-01', 1000, 'ST-002', 1),

('Irrigation Pump', 'Equipment', 'Maintenance', '2024-02-28', '2024-05-28', 500, 'EQ-001', 10),

('Sprayer Drone', 'Equipment', 'Available', '2024-01-05', '2024-06-05', 1200, 'EQ-002', 8);
