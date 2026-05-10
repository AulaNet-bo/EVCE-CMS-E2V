INSERT INTO wallets (user_id, balance, currency, created_at, updated_at) 
VALUES (1, 250.00, 'BOB', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE balance = 250.00, updated_at = NOW();

INSERT INTO wallets (user_id, balance, currency, created_at, updated_at) 
VALUES (12, 250.00, 'BOB', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE balance = 250.00, updated_at = NOW();
