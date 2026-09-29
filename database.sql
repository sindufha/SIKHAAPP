-- SIKHA - skema database untuk instalasi baru
-- Kompatibel dengan MySQL 5.7+ / 8.x dan MariaDB yang mendukung JSON.
-- Impor file ini setelah memilih database tujuan.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL,
    data MEDIUMBLOB NOT NULL,
    expires_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sessions_expires_at (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) NOT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(150) NOT NULL,
    role ENUM('ADMIN', 'GURU') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    KEY idx_users_role_active (role, is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tahun_ajaran (
    id CHAR(36) NOT NULL,
    tahun VARCHAR(20) NOT NULL,
    semester ENUM('GANJIL', 'GENAP') NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tahun_ajaran (tahun, semester),
    KEY idx_tahun_ajaran_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kelas (
    id CHAR(36) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    wali_kelas_id CHAR(36) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kelas_nama (nama),
    UNIQUE KEY uq_kelas_wali (wali_kelas_id),
    CONSTRAINT fk_kelas_wali
        FOREIGN KEY (wali_kelas_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS siswa (
    id CHAR(36) NOT NULL,
    nis VARCHAR(50) NOT NULL,
    nama VARCHAR(150) NOT NULL,
    kelas_id CHAR(36) NOT NULL,
    qr_code CHAR(40) NOT NULL,
    jenis_kelamin ENUM('LAKI_LAKI', 'PEREMPUAN') NOT NULL,
    tempat_lahir VARCHAR(100) NULL,
    tanggal_lahir DATE NULL,
    alamat TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_siswa_nis (nis),
    UNIQUE KEY uq_siswa_qr (qr_code),
    KEY idx_siswa_kelas_active (kelas_id, is_active),
    CONSTRAINT fk_siswa_kelas
        FOREIGN KEY (kelas_id) REFERENCES kelas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jam_presensi (
    id CHAR(36) NOT NULL,
    jam_masuk TIME NOT NULL,
    toleransi_menit SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    jam_pulang TIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_jam_presensi_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS presensi (
    id CHAR(36) NOT NULL,
    siswa_id CHAR(36) NOT NULL,
    kelas_id CHAR(36) NOT NULL,
    tahun_ajaran_id CHAR(36) NULL,
    tanggal DATE NOT NULL,
    status ENUM('HADIR', 'TERLAMBAT', 'IZIN', 'SAKIT', 'ALFA') NOT NULL,
    metode VARCHAR(20) NOT NULL,
    jam_datang TIME NULL,
    jam_pulang TIME NULL,
    keterangan VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_presensi_siswa_tanggal (siswa_id, tanggal),
    KEY idx_presensi_tanggal_status (tanggal, status),
    KEY idx_presensi_kelas_tanggal (kelas_id, tanggal),
    KEY idx_presensi_tahun_ajaran (tahun_ajaran_id),
    CONSTRAINT fk_presensi_siswa
        FOREIGN KEY (siswa_id) REFERENCES siswa(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_presensi_kelas
        FOREIGN KEY (kelas_id) REFERENCES kelas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_presensi_tahun_ajaran
        FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_log (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) NULL,
    aksi VARCHAR(100) NOT NULL,
    deskripsi TEXT NULL,
    detail JSON NULL,
    ip VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_log_created (created_at),
    KEY idx_audit_log_user (user_id),
    CONSTRAINT fk_audit_log_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- Akun awal untuk login pertama.
-- Username: admin
-- Password: password
-- Segera ganti password melalui menu Profil setelah login.
INSERT IGNORE INTO users (id, username, password, nama, role, is_active)
VALUES (
    '00000000-0000-4000-8000-000000000001',
    'admin',
    '$2y$10$1qM.OFuMEG5m7RbhMmBxBuI3nCxWE8gP2WhLrsM1LiKaYUEI.u./2',
    'Administrator',
    'ADMIN',
    1
);

-- Tahun ajaran dan jam presensi awal dapat diedit dari panel admin.
INSERT IGNORE INTO tahun_ajaran
    (id, tahun, semester, tanggal_mulai, tanggal_selesai, is_active)
VALUES
    ('00000000-0000-4000-8000-000000000002', '2026/2027', 'GANJIL', '2026-07-01', '2026-12-31', 1);

INSERT IGNORE INTO jam_presensi
    (id, jam_masuk, toleransi_menit, jam_pulang, is_active)
VALUES
    ('00000000-0000-4000-8000-000000000003', '07:00:00', 15, '13:00:00', 1);
