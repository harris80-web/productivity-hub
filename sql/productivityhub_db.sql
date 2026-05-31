CREATE TABLE `tasks` (
    `id` INT(11) PRIMARY KEY AUTO_INCREMENT,
    `task_name` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(100) NOT NULL,
    `task_type` VARCHAR(50) NOT NULL COMMENT 'Study, General, or Urgent',
    `due_date` DATE NOT NULL,
    `estimate_min` INT(11) NOT NULL,
    `is_completed` BOOLEAN DEFAULT 0,
    `date_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);