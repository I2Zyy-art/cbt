CREATE DATABASE IF NOT EXISTS quizverse;
USE quizverse;

-- Tabel user
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    level INT DEFAULT 1,
    exp INT DEFAULT 0
);

-- Tabel soal
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    option_a VARCHAR(255),
    option_b VARCHAR(255),
    option_c VARCHAR(255),
    option_d VARCHAR(255),
    correct_option CHAR(1)
);

-- Contoh soal
INSERT INTO questions 
(question, option_a, option_b, option_c, option_d, correct_option)
VALUES
('Ibukota Indonesia adalah?', 'Bandung', 'Surabaya', 'Jakarta', 'Medan', 'C');

CREATE TABLE detail_jawaban (
 id INT AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(50),
 soal TEXT,
 jawaban_siswa CHAR(1),
 jawaban_benar CHAR(1)
);
