<?php
require __DIR__ . "/koneksi.php";

$produk = $koneksi->query("
    SELECT
        b.id_barang,
        b.nama_barang,
        b.harga_jual,
        j.nama_jenis,
        COALESCE(s.total_stok, 0) AS total_stok
    FROM barang AS b
    JOIN jenis_barang AS j ON j.id_jenis = b.id_jenis
    LEFT JOIN (
        SELECT id_barang, SUM(stok) AS total_stok
        FROM varian_barang
        GROUP BY id_barang
    ) AS s ON s.id_barang = b.id_barang
    WHERE b.aktif = 1
    ORDER BY b.id_barang
");

function aman($teks)
{
    return htmlspecialchars(
        (string) $teks,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CL Collection — Katalog</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #faf7f4;
            color: #302a27;
        }

        header {
            padding: 28px 6%;
            background: white;
            border-bottom: 1px solid #e9dfd7;
        }

        header h1 {
            margin: 0;
            font-size: 28px;
            letter-spacing: 2px;
        }

        header p {
            margin-bottom: 0;
            color: #756b64;
        }

        main {
            max-width: 1200px;
            margin: auto;
            padding: 32px 24px;
        }

        .judul {
            margin-bottom: 24px;
        }

        .judul p {
            color: #756b64;
        }

        .katalog {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 22px;
        }

        .produk {
            background: white;
            border: 1px solid #e9dfd7;
            border-radius: 12px;
            overflow: hidden;
        }

        .foto {
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eee6df;
            color: #77685d;
            font-size: 14px;
        }

        .isi {
            padding: 20px;
        }

        .jenis {
            color: #8a6350;
            font-size: 13px;
        }

        .produk h3 {
            margin: 10px 0;
            font-size: 17px;
            line-height: 1.5;
        }

        .harga {
            font-size: 19px;
            font-weight: bold;
        }

        .stok {
            color: #756b64;
            font-size: 14px;
        }

        footer {
            padding: 28px;
            text-align: center;
            color: #756b64;
            border-top: 1px solid #e9dfd7;
        }
    </style>
</head>
<body>
    <header>
        <h1>CL COLLECTION</h1>
        <p>Koleksi pakaian untuk melengkapi gayamu.</p>
    </header>

    <main>
        <div class="judul">
            <h2>Koleksi Kami</h2>
            <p><?= (int) $produk->num_rows ?> produk tersedia di katalog.</p>
        </div>

        <div class="katalog">
            <?php if ($produk->num_rows === 0): ?>
                <p>Belum ada produk yang ditampilkan.</p>
            <?php endif; ?>

            <?php while ($barang = $produk->fetch_assoc()): ?>
                <article class="produk">
                    <div class="foto">
                        Foto produk belum ditambahkan
                    </div>

                    <div class="isi">
                        <span class="jenis">
                            <?= aman($barang["nama_jenis"]) ?>
                        </span>

                        <h3><?= aman($barang["nama_barang"]) ?></h3>

                        <p class="harga">
                            Rp<?= number_format(
                                (float) $barang["harga_jual"],
                                0,
                                ",",
                                "."
                            ) ?>
                        </p>

                        <p class="stok">
                            <?php if ((int) $barang["total_stok"] > 0): ?>
                                Stok: <?= (int) $barang["total_stok"] ?> pcs
                            <?php else: ?>
                                Stok habis
                            <?php endif; ?>
                        </p>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    </main>

    <footer>
        &copy; <?= date("Y") ?> CL Collection
    </footer>
</body>
</html>