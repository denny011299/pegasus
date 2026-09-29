-- =============================================================================
-- 00_setup_session.sql
-- Siapkan session: charset + matikan cek FK/unique sementara.
-- TIDAK mengubah data bisnis.
-- Tabel: —
-- =============================================================================

SET NAMES utf8mb4;
SET @OLD_UNIQUE_CHECKS := @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS := @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
