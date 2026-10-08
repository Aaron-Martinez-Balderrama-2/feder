<?php
require __DIR__ . '/config/config.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

$sql = "
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS user_achievements;
DROP TABLE IF EXISTS achievements;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS review_cards;
DROP TABLE IF EXISTS challenge_attempts;
DROP TABLE IF EXISTS lesson_progress;
DROP TABLE IF EXISTS question_options;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS lessons;
DROP TABLE IF EXISTS course_attendance;

CREATE TABLE IF NOT EXISTS course_classes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  module_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  is_completed TINYINT(1) NOT NULL DEFAULT 0,
  completed_at DATETIME NULL,
  CONSTRAINT fk_cclass_module FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS course_attendance (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  class_id INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_catt (user_id, class_id),
  CONSTRAINT fk_catt_usr FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_catt_cls FOREIGN KEY (class_id) REFERENCES course_classes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE course_materials ADD COLUMN class_id INT UNSIGNED NULL AFTER course_id;
ALTER TABLE course_materials ADD CONSTRAINT fk_cmat_cls FOREIGN KEY (class_id) REFERENCES course_classes(id) ON DELETE SET NULL;

ALTER TABLE enrollments DROP COLUMN progress_pct;
ALTER TABLE courses ADD COLUMN progress_pct TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER end_date;

SET FOREIGN_KEY_CHECKS = 1;
";

$db->multi_query($sql);
do {
    if ($res = $db->store_result()) {
        $res->free();
    }
} while ($db->more_results() && $db->next_result());

if ($db->error) {
    echo "Error: " . $db->error . "\n";
} else {
    echo "Restructure DB updated successfully.\n";
}
