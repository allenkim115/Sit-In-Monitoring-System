-- Update notifications table structure
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS recipient_type enum('user','admin') NOT NULL DEFAULT 'user' AFTER user_id,
MODIFY COLUMN user_id varchar(20) DEFAULT NULL,
MODIFY COLUMN type enum('reservation_request','reservation_approved','reservation_rejected','reward_received') NOT NULL;

-- Update reservation request notifications
UPDATE notifications 
SET type = 'reservation_request' 
WHERE type = '' 
AND message LIKE '%has been submitted and is pending approval%';

-- Update approved notifications
UPDATE notifications 
SET type = 'reservation_approved' 
WHERE type = '' 
AND message LIKE '%approved%';

-- Update rejected notifications
UPDATE notifications 
SET type = 'reservation_rejected' 
WHERE type = '' 
AND message LIKE '%rejected%'; 