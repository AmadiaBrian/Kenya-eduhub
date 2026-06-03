-- Add user_id column to resources table
ALTER TABLE resources ADD COLUMN user_id INT NULL;

-- Add foreign key constraint (optional, if you want to enforce referential integrity)
-- ALTER TABLE resources ADD CONSTRAINT fk_resources_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
