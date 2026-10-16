<?php

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli(
        "localhost",
        "root",
        "",
        "toko_pakaian",
        3306
    );

    $koneksi->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    exit("Koneksi database gagal. Periksa pengaturan database.");
}