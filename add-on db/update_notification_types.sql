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