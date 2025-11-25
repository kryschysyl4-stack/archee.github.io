-- Migration: Add category column to books table
-- Run this SQL to add the category field to existing databases

ALTER TABLE books 
ADD COLUMN category VARCHAR(100) DEFAULT 'General' AFTER year;

-- Update existing books to have a default category if needed
UPDATE books SET category = 'General' WHERE category IS NULL;

