CREATE database organization_registration_db;
Use organization_registration_db;

CREATE TABLE Organizations (
    organization_id INT AUTO_INCREMENT PRIMARY KEY,
    organization_name VARCHAR(100) NOT NULL,
    organization_phone INT NOT NULL,
    organization_address VARCHAR(100) NOT NULL,
    organization_email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

