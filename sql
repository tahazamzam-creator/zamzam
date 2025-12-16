CREATE TABLE IF NOT EXISTS stude (
    id INT AUTO_INCREMENT PRIMARY KEY,
    f_name VARCHAR(100) NOT NULL,
    l_name VARCHAR(100) NOT NULL,
    fa_name VARCHAR(100) DEFAULT NULL,
    n_code CHAR(10) UNIQUE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    pas VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS studen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name_dars VARCHAR(100) NOT NULL,
    score TINYINT NOT NULL CHECK(score >= 0 AND score <= 20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES stude(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO studen (user_id, name_dars, score) VALUES
(1, 'فارسی', 0),
(1, 'ریاضی', 0),
(1, 'قرآن', 0),
(1, 'دینی', 0),
(1, 'تاریخ', 0),
(1, 'هنر', 0),
(1, 'ورزش', 0)
ON DUPLICATE KEY UPDATE score=score;
