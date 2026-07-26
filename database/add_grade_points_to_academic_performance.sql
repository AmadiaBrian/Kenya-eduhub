-- Add grade_points column to academic_performance table
ALTER TABLE `academic_performance` ADD COLUMN `grade_points` int(11) DEFAULT NULL AFTER `grade`;
