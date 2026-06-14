USE `team14`;

-- Drop tables in reverse order of dependencies to avoid foreign key errors
DROP TABLE IF EXISTS `Rate_dish`;
DROP TABLE IF EXISTS `Rate_staff`;
DROP TABLE IF EXISTS `Feedback_details`;
DROP TABLE IF EXISTS `Feedback_forms`;
DROP TABLE IF EXISTS `Service`;
DROP TABLE IF EXISTS `Contain`;
DROP TABLE IF EXISTS `Has`;
DROP TABLE IF EXISTS `Consumption_records`;
DROP TABLE IF EXISTS `Customers`;
DROP TABLE IF EXISTS `Dishes`;
DROP TABLE IF EXISTS `Staffs`;

-- 1. Staffs (員工)
CREATE TABLE `Staffs` (
    `Staff_ID` VARCHAR(10) PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `position` VARCHAR(50) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Dishes (餐點)
CREATE TABLE `Dishes` (
    `Dish_ID` VARCHAR(10) PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `allergens` VARCHAR(255),
    `price` DECIMAL(10,2) NOT NULL CHECK (`price` >= 0)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Customers (顧客)
CREATE TABLE `Customers` (
    `Customer_ID` VARCHAR(10) PRIMARY KEY,
    `member_level` VARCHAR(20) NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `gender` CHAR(1) CHECK (`gender` IN ('M', 'F', 'O')),
    `age` INT CHECK (`age` >= 0),
    `phone_number` VARCHAR(20)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Consumption Records (消費紀錄)
CREATE TABLE `Consumption_records` (
    `Record_ID` VARCHAR(10) PRIMARY KEY,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `table_number` VARCHAR(10) NOT NULL,
    `number_of_customers` INT NOT NULL CHECK (`number_of_customers` > 0),
    `discount` DECIMAL(5,2) DEFAULT 1.00 CHECK (`discount` BETWEEN 0 AND 1),
    `amount` DECIMAL(10,2) NOT NULL CHECK (`amount` >= 0)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Has (顧客擁有消費紀錄 - 多對多)
CREATE TABLE `Has` (
    `Customer_ID` VARCHAR(10),
    `Record_ID` VARCHAR(10),
    PRIMARY KEY (`Customer_ID`, `Record_ID`),
    FOREIGN KEY (`Customer_ID`) REFERENCES `Customers` (`Customer_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`Record_ID`) REFERENCES `Consumption_records` (`Record_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Contain (消費紀錄包含餐點 - 多對多)
CREATE TABLE `Contain` (
    `Record_ID` VARCHAR(10),
    `Dish_ID` VARCHAR(10),
    `number` INT NOT NULL CHECK (`number` > 0),
    PRIMARY KEY (`Record_ID`, `Dish_ID`),
    FOREIGN KEY (`Record_ID`) REFERENCES `Consumption_records` (`Record_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`Dish_ID`) REFERENCES `Dishes` (`Dish_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Service (服務關係 - 三元關聯)
CREATE TABLE `Service` (
    `Record_ID` VARCHAR(10),
    `Staff_ID` VARCHAR(10),
    `Customer_ID` VARCHAR(10),
    PRIMARY KEY (`Record_ID`, `Staff_ID`, `Customer_ID`),
    FOREIGN KEY (`Record_ID`) REFERENCES `Consumption_records` (`Record_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`Staff_ID`) REFERENCES `Staffs` (`Staff_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`Customer_ID`) REFERENCES `Customers` (`Customer_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Feedback Forms (回饋表單)
CREATE TABLE `Feedback_forms` (
    `Feedback_ID` VARCHAR(10) PRIMARY KEY,
    `date` DATE NOT NULL,
    `time` TIME NOT NULL,
    `opinion` TEXT,
    `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `Customer_ID` VARCHAR(10) NOT NULL,
    `Record_ID` VARCHAR(10) NOT NULL,
    FOREIGN KEY (`Customer_ID`) REFERENCES `Customers` (`Customer_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`Record_ID`) REFERENCES `Consumption_records` (`Record_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Feedback Details (回饋細節 - 弱實體)
CREATE TABLE `Feedback_details` (
    `Feedback_ID` VARCHAR(10),
    `Detail_ID` INT,
    `opinion` TEXT,
    `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    PRIMARY KEY (`Feedback_ID`, `Detail_ID`),
    FOREIGN KEY (`Feedback_ID`) REFERENCES `Feedback_forms` (`Feedback_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Rate Staff (評分外場員工)
CREATE TABLE `Rate_staff` (
    `Feedback_ID` VARCHAR(10),
    `Detail_ID` INT,
    `Staff_ID` VARCHAR(10),
    PRIMARY KEY (`Feedback_ID`, `Detail_ID`, `Staff_ID`),
    FOREIGN KEY (`Feedback_ID`, `Detail_ID`) REFERENCES `Feedback_details` (`Feedback_ID`, `Detail_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`Staff_ID`) REFERENCES `Staffs` (`Staff_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Rate Dish (評分餐點)
CREATE TABLE `Rate_dish` (
    `Feedback_ID` VARCHAR(10),
    `Detail_ID` INT,
    `Dish_ID` VARCHAR(10),
    PRIMARY KEY (`Feedback_ID`, `Detail_ID`, `Dish_ID`),
    FOREIGN KEY (`Feedback_ID`, `Detail_ID`) REFERENCES `Feedback_details` (`Feedback_ID`, `Detail_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`Dish_ID`) REFERENCES `Dishes` (`Dish_ID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4;


-- ==========================================
-- Insert Mock Data
-- ==========================================

-- Staffs (S001, S002, S003)
INSERT INTO `Staffs` (`Staff_ID`, `name`, `position`) VALUES
('S001', '陳小明', '外場服務員'),
('S002', '林大華', '外場服務員'),
('S003', '王美麗', '外場領班');

-- Dishes (D001, D002, D003, D004)
INSERT INTO `Dishes` (`Dish_ID`, `name`, `type`, `allergens`, `price`) VALUES
('D001', '經典沙朗牛排', '主餐', '無', 680.00),
('D002', '凱薩沙拉', '沙拉', '牛奶, 蛋', 180.00),
('D003', '波士頓龍蝦義大利麵', '主餐', '甲殼類, 小麥', 580.00),
('D004', '熔岩巧克力蛋糕', '甜點', '蛋, 小麥', 150.00);

-- Customers (C001, C002, C003)
INSERT INTO `Customers` (`Customer_ID`, `member_level`, `name`, `gender`, `age`, `phone_number`) VALUES
('C001', '黃金會員', '張三', 'M', 28, '0911222333'),
('C002', '白銀會員', '李四', 'F', 34, '0922333444'),
('C003', '一般顧客', '王五', 'M', 45, '0933444555');

-- Consumption Records (R001, R002, R003, R004)
INSERT INTO `Consumption_records` (`Record_ID`, `date`, `time`, `table_number`, `number_of_customers`, `discount`, `amount`) VALUES
('R001', '2026-06-10', '12:30:00', 'T01', 2, 0.90, 860.00),
('R002', '2026-06-11', '18:30:00', 'T05', 4, 1.00, 2020.00),
('R003', '2026-06-12', '13:00:00', 'T03', 1, 1.00, 580.00),
('R004', '2026-06-13', '19:00:00', 'T02', 3, 0.85, 1450.00);

-- Has (顧客擁有的消費紀錄)
INSERT INTO `Has` (`Customer_ID`, `Record_ID`) VALUES
('C001', 'R001'),
('C002', 'R002'),
('C003', 'R003'),
('C001', 'R004');

-- Contain (訂單中的餐點數量)
INSERT INTO `Contain` (`Record_ID`, `Dish_ID`, `number`) VALUES
('R001', 'D001', 1),
('R001', 'D002', 1),
('R002', 'D001', 2),
('R002', 'D003', 1),
('R002', 'D004', 2),
('R003', 'D003', 1),
('R004', 'D001', 1),
('R004', 'D003', 1),
('R004', 'D004', 1);

-- Service (外場員工在消費中服務顧客)
INSERT INTO `Service` (`Record_ID`, `Staff_ID`, `Customer_ID`) VALUES
('R001', 'S001', 'C001'),
('R002', 'S002', 'C002'),
('R003', 'S001', 'C003'),
('R004', 'S003', 'C001');

-- Feedback Forms (回饋表單)
INSERT INTO `Feedback_forms` (`Feedback_ID`, `date`, `time`, `opinion`, `rating`, `Customer_ID`, `Record_ID`) VALUES
('F001', '2026-06-10', '13:45:00', '牛排熟度剛剛好，非常美味！服務生也很親切。', 5, 'C001', 'R001'),
('F002', '2026-06-11', '20:00:00', '餐點好吃，但龍蝦麵等得有點久，希望可以改進。', 4, 'C002', 'R002'),
('F003', '2026-06-12', '14:00:00', '上菜速度很快，服務非常好，下次還會再來！', 5, 'C003', 'R003');

-- Feedback Details (回饋細節)
INSERT INTO `Feedback_details` (`Feedback_ID`, `Detail_ID`, `opinion`, `rating`) VALUES
('F001', 1, '牛排非常嫩，多汁！', 5),
('F001', 2, '陳小明服務熱心，值得讚賞！', 5),
('F002', 1, '龍蝦麵稍微偏鹹，等候時間過長', 3),
('F002', 2, '林大華態度一般', 4),
('F003', 1, '龍蝦麵很好吃，新鮮！', 5),
('F003', 2, '陳小明工作認真！', 5);

-- Rate Staff (評分外場員工)
INSERT INTO `Rate_staff` (`Feedback_ID`, `Detail_ID`, `Staff_ID`) VALUES
('F001', 2, 'S001'),
('F002', 2, 'S002'),
('F003', 2, 'S001');

-- Rate Dish (評分餐點)
INSERT INTO `Rate_dish` (`Feedback_ID`, `Detail_ID`, `Dish_ID`) VALUES
('F001', 1, 'D001'),
('F002', 1, 'D003'),
('F003', 1, 'D003');