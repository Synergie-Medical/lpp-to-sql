CREATE OR REPLACE TABLE lpp_code (
    ref VARCHAR(13) PRIMARY KEY,
    old_ref VARCHAR(13),
    name VARCHAR(256),
    fk_provider INT NULL REFERENCES organization(id)
) CHARSET=utf8mb4;

CREATE OR REPLACE TABLE lpp_code_price (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fk_code VARCHAR(13) NOT NULL REFERENCES lpp_code(ref) ON DELETE CASCADE,
    type VARCHAR(4),
    secu_id INT NULL,
    validity_start DATE,
    validity_end DATE,
    jo_date DATE,
    order_date DATE,
    price FLOAT,
    unit_price FLOAT,
    major_guadeloupe FLOAT,
    major_martinique FLOAT,
    major_guyane FLOAT,
    major_reunion FLOAT,
    major_mayotte FLOAT,
    max_refund INT DEFAULT 0,
    UNIQUE(fk_code, validity_start, price)
) CHARSET=utf8mb4;


/*
DROP TABLE IF EXISTS lpp_code_price;
DROP TABLE IF EXISTS lpp_code;

DELETE FROM lpp_code; DELETE FROM lpp_code_price; SOURCE C:/Users/Yonis/Desktop/GitLab/lpp-to-sql/archives/2022-09-21-13-17-47/LPPTOT696.sql;
*/