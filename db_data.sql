USE `your_db_name`; -- TODO: change to your database name

CREATE TABLE IF NOT EXISTS `your_student_id` ( -- TODO: change table name to your student ID
	`id` VARCHAR(10) PRIMARY KEY,
	`title` VARCHAR(100) NOT NULL,
	`director` VARCHAR(50),
	`release_date` DATE
) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


INSERT INTO `your_student_id` (`id`, `title`, `director`, `release_date`) VALUES -- TODO: change table name to your student ID
('MV001', 'The Shawshank Redemption', 'Frank Darabont', '1994-09-23'),
('MV002', 'The Godfather', 'Francis Ford Coppola', '1972-03-24'),
('MV003', 'Inception', 'Christopher Nolan', '2010-07-16'),
('MV004', 'Parasite', 'Bong Joon-ho', '2019-05-30'),
('MV005', 'Spirited Away', 'Hayao Miyazaki', '2001-07-20'),
('MV006', 'Pulp Fiction', 'Quentin Tarantino', '1994-10-14'),
('MV007', 'Interstellar', 'Christopher Nolan', '2014-11-07'),
('MV008', 'Schindler''s List', 'Steven Spielberg', '1993-12-15'),
('MV009', 'Whiplash', 'Damien Chazelle', '2014-10-10'),
('MV010', 'Your Name', 'Makoto Shinkai', '2016-08-26');