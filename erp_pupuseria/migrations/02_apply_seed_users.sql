-- 02_apply_seed_users.sql
-- Seeds the three official system accounts.
-- Login password for all three accounts: mary123
-- (bcrypt hashes below were generated with cost factor 10, compatible with
--  PHP's password_hash()/password_verify() using PASSWORD_BCRYPT)
--
-- Requires the "usuarios" table to already exist with a UNIQUE constraint
-- on nombre_usuario (columns: id_usuario, nombre_usuario, contrasena_hash, rol, fecha_registro).

INSERT INTO usuarios (nombre_usuario, contrasena_hash, rol)
VALUES
    ('mary_admin',   '$2b$10$v/Mb9/UaChl/mXEPP843H.64L7AuJP2KTT9flS4u/CXV5/HqCjW9G', 'Administrator'),
    ('chepe_cajero', '$2b$10$6DMQ297W35KxEyV21fXyJ.EO4fPoiMDAiLXLmVDVRHUN68bNYAqaO', 'Cashier'),
    ('ana_cajera',   '$2b$10$4yel9alt6FSqL2SiaZVc/.NJhe8gTLsGgtOhCh0lp13JRX5HskW7G', 'Cashier')
ON DUPLICATE KEY UPDATE
    contrasena_hash = VALUES(contrasena_hash),
    rol = VALUES(rol);
