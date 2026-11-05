-- Cap quyen quan tri cho tai khoan da dang ky.
-- Sua email ben duoi thanh email tai khoan cua ban truoc khi chay.
SET @admin_email = 'admin@electrostore.local';
UPDATE users SET role = 'admin' WHERE email = @admin_email;
SELECT id, name, email, role FROM users WHERE email = @admin_email;
