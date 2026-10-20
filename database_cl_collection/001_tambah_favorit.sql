CREATE TABLE IF NOT EXISTS favorit (
    id_favorit INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_user INT UNSIGNED NOT NULL,
    id_barang INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unik_favorit_user_barang (id_user, id_barang),
    KEY idx_favorit_barang (id_barang),

    CONSTRAINT fk_favorit_user
        FOREIGN KEY (id_user)
        REFERENCES `user` (id_user)
        ON DELETE CASCADE,

    CONSTRAINT fk_favorit_barang
        FOREIGN KEY (id_barang)
        REFERENCES barang (id_barang)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;