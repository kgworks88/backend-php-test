ALTER TABLE todos 
ADD completed CHAR(1) NOT NULL DEFAULT 'N' COMMENT 'Y/N' AFTER `description`;

INSERT INTO todos (user_id, description, completed) VALUES 
('1', 'buy groceries', 'Y'), 
('1', 'cook dinner', 'N'), 
('1', 'do dishes', 'N'), 
('1', 'read a book', 'N'),
('1', 'meditate', 'N'), 
('1', 'call grandma', 'N'), 
('1', 'make plan for tomorrow', 'N'), 
('1', 'watch TV', 'N');