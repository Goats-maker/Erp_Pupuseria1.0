-- 02_undo_seed_users.sql
-- Removes the three seeded system accounts.

DELETE FROM usuarios WHERE nombre_usuario IN ('mary_admin', 'chepe_cajero', 'ana_cajera');
