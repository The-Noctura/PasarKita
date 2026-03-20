-- Adds product weight column (grams)
-- Safe to run once; if your MySQL version doesn't support IF NOT EXISTS for ADD COLUMN,
-- check existence first or handle the error.

ALTER TABLE `products`
  ADD COLUMN `weight_grams` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `price`;
