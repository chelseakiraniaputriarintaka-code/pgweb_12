<?php
// ==========================================
// KONEKSI KE DATABASE MYSQL (PHPMYADMIN)
// ==========================================
$servername = "127.0.0.1";
$username   = "root";
$password   = "";
$dbname     = "pgweb12";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$sql    = "SELECT id, kecamatan, longitude, latitude, luas, jumlah_penduduk FROM kec_penduduk ORDER BY kecamatan ASC";
$result = $conn->query($sql);

$data_kecamatan = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data_kecamatan[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web GIS Gunungkidul - GeoServer + LeafletJS + MySQL</title>

    <!-- 1. CSS LeafletJS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        html, body {
            height: 100%;
            width: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .map-header {
            background-color: #0d3b66;
            color: #ffffff;
            padding: 12px 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .map-header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .map-header p {
            margin: 2px 0 0 0;
            font-size: 12px;
            color: #dce6f1;
        }

        .btn-toggle-table {
            background-color: #ffffff;
            color: #0d3b66;
            border: none;
            padding: 8px 14px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: background-color 0.2s;
        }

        .btn-toggle-table:hover {
            background-color: #dce6f1;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
        }

        #map {
            flex: 1;
            width: 100%;
        }

        /* ==========================================
           PANEL TABEL DATA KECAMATAN
           ========================================== */
        .table-panel {
            height: 250px;
            background-color: #ffffff;
            border-top: 3px solid #0d3b66;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            z-index: 999;
        }

        .table-panel.hidden {
            display: none;
        }

        .table-toolbar {
            background-color: #f1f5f9;
            padding: 8px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #cbd5e1;
            gap: 10px;
        }

        .table-toolbar h3 {
            margin: 0;
            font-size: 13px;
            color: #0d3b66;
        }

        .table-search {
            padding: 5px 10px;
            font-size: 12px;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            width: 200px;
            outline: none;
        }

        .table-search:focus {
            border-color: #0d3b66;
        }

        .table-wrapper {
            flex: 1;
            overflow-y: auto;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        table.data-table thead th {
            position: sticky;
            top: 0;
            background-color: #0d3b66;
            color: #ffffff;
            padding: 8px 12px;
            text-align: left;
            font-weight: bold;
            z-index: 2;
        }

        table.data-table thead th.text-center,
        table.data-table tbody td.text-center {
            text-align: center;
        }

        table.data-table thead th.text-right,
        table.data-table tbody td.text-right {
            text-align: right;
        }

        table.data-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            transition: background-color 0.15s;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        table.data-table tbody tr:hover {
            background-color: #e0f2fe;
        }

        table.data-table tbody td {
            padding: 7px 12px;
            color: #1e293b;
        }

        .btn-zoom {
            background-color: #0d3b66;
            color: #ffffff;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
        }

        .btn-zoom:hover {
            background-color: #1d5d9b;
        }

        /* ==========================================
           LEGENDA PETA
           ========================================== */
        .info.legend {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 10px 12px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
            max-height: 240px;
            overflow-y: auto;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }

        .info.legend h4 {
            margin: 0 0 8px 0;
            font-size: 13px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
        }

        .symbol-point {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #e74c3c;
            border: 1.5px solid #ffffff;
            box-shadow: 0 0 2px #000;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .symbol-point-kec {
            display: inline-block;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background-color: #ff0000;
            border: 2px solid #ffffff;
            box-shadow: 0 0 3px #000;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .symbol-line-jalan {
            display: inline-block;
            width: 18px;
            height: 3px;
            background-color: #e67e22;
            margin-right: 8px;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

    <header class="map-header">
        <div>
            <h1>Peta Administrasi, Jaringan Jalan, dan Kependudukan Kabupaten Gunungkidul</h1>
            <p>Praktikum Pemrograman Geospasial Web &mdash; GeoServer, LeafletJS &amp; MySQL</p>
        </div>
        <button id="toggleTableBtn" class="btn-toggle-table" onclick="toggleTabel()">
            Sembunyikan Tabel Data
        </button>
    </header>

    <div class="main-content">
        <div id="map"></div>

        <!-- PANEL TABEL DATA LANGSUNG DARI DATABASE MYSQL (pgweb12) -->
        <div id="tablePanel" class="table-panel">
            <div class="table-toolbar">
                <h3>Data Kecamatan, Luas Wilayah, dan Jumlah Penduduk Kabupaten Gunungkidul (<?= count($data_kecamatan) ?> Kecamatan)</h3>
                <input type="text" id="searchKecamatan" class="table-search" placeholder="Cari nama kecamatan...">
            </div>
            <div class="table-wrapper">
                <table class="data-table" id="tabelPenduduk">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 45px;">No</th>
                            <th>Kecamatan (Kapanewon)</th>
                            <th class="text-center">Longitude</th>
                            <th class="text-center">Latitude</th>
                            <th class="text-right">Luas Area (km&sup2;)</th>
                            <th class="text-right">Jumlah Penduduk (Jiwa)</th>
                            <th class="text-center" style="width: 90px;">Lokasi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyPenduduk">
                        <?php if (count($data_kecamatan) > 0): ?>
                            <?php $no = 1; foreach ($data_kecamatan as $row): ?>
                                <?php
                                    $luasFmt     = number_format((float)$row['luas'], 2, ',', '.');
                                    $pendudukFmt = number_format((int)$row['jumlah_penduduk'], 0, ',', '.');
                                ?>
                                <tr onclick="zoomKeKecamatan(<?= (int)$row['id'] ?>)">
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['kecamatan']) ?></strong></td>
                                    <td class="text-center"><?= number_format((float)$row['longitude'], 4) ?></td>
                                    <td class="text-center"><?= number_format((float)$row['latitude'], 4) ?></td>
                                    <td class="text-right"><?= $luasFmt ?></td>
                                    <td class="text-right"><strong><?= $pendudukFmt ?></strong></td>
                                    <td class="text-center">
                                        <button class="btn-zoom" type="button">Lihat</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 16px; color: #64748b;">
                                    Data tidak ditemukan pada tabel kec_penduduk.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 2. Library JavaScript Leaflet & jQuery -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <!-- 3. Plugin Leaflet-Geoserver -->
    <script src="lib/L.Geoserver.js"></script>

    <script>
        var map = L.map("map").setView([-7.997, 110.602], 11);

        // ==========================================
        // PENGATURAN PANE PERMANEN (CONTAINER TERPISAH)
        // ==========================================
        map.createPane("panePolygon");
        map.getPane("panePolygon").style.zIndex = 410; // Paling bawah

        map.createPane("paneGaris");
        map.getPane("paneGaris").style.zIndex = 450;   // Tengah

        map.createPane("paneTitik");
        map.getPane("paneTitik").style.zIndex = 490;   // Paling atas

        // ==========================================
        // 1. BASEMAP
        // ==========================================
        var osm = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        });
        osm.addTo(map);

        // ==========================================
        // 2. OVERLAY LAYERS (GEOSERVER + MYSQL POPUP)
        // ==========================================
        var geoserverWmsUrl = "http://localhost:8080/geoserver/wms";

        // Polygon Batas Administrasi di panePolygon (zIndex 410)
        var layerPolygon = L.Geoserver.wms(geoserverWmsUrl, {
            layers: "pgweb11:ADMINISTRASIDESA_AR_25K",
            transparent: true,
            pane: "panePolygon",
            opacity: 0.85
        });
        layerPolygon.addTo(map);

        // Garis Jaringan Jalan di paneGaris (zIndex 450)
        var layerJalan = L.Geoserver.wms(geoserverWmsUrl, {
            layers: "pgweb11:JALAN_LN_25K",
            transparent: true,
            pane: "paneGaris"
        });
        layerJalan.addTo(map);

        // Titik Toponimi di paneTitik (zIndex 490)
        var layerTitik = L.Geoserver.wms(geoserverWmsUrl, {
            layers: "pgweb11:TOPONIMI_PT_25K",
            transparent: true,
            pane: "paneTitik"
        });
        layerTitik.addTo(map);

        // Titik Kecamatan WMS GeoServer (MySQL SQL View)
        var layerKecWms = L.Geoserver.wms(geoserverWmsUrl, {
            layers: "pgweb11:kec_penduduk",
            transparent: true,
            pane: "paneTitik"
        });
        layerKecWms.addTo(map);

        // ==========================================
        // 3. TITIK INTERAKTIF DARI PHPMYADMIN (POP-UP)
        // ==========================================
        var dataKecamatan = <?= json_encode($data_kecamatan, JSON_NUMERIC_CHECK) ?>;
        var layerGroupPopup = L.layerGroup().addTo(map);
        var markerById = {};

        dataKecamatan.forEach(function (item) {
            var luasFmt = Number(item.luas).toLocaleString("id-ID", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            var pendudukFmt = Number(item.jumlah_penduduk).toLocaleString("id-ID");

            var popupContent = `
                <div style="font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6; min-width: 195px;">
                    <div style="font-weight: bold; font-size: 14px; color: #0d3b66; border-bottom: 1.5px solid #0d3b66; padding-bottom: 4px; margin-bottom: 6px;">
                        Kapanewon ${item.kecamatan}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <tr>
                            <td style="color: #475569; padding: 2px 0;"><strong>Luas Area</strong></td>
                            <td style="text-align: right; font-weight: bold;">${luasFmt} km&sup2;</td>
                        </tr>
                        <tr>
                            <td style="color: #475569; padding: 2px 0;"><strong>Penduduk</strong></td>
                            <td style="text-align: right; font-weight: bold;">${pendudukFmt} jiwa</td>
                        </tr>
                        <tr>
                            <td style="color: #475569; padding: 2px 0;"><strong>Longitude</strong></td>
                            <td style="text-align: right;">${item.longitude}</td>
                        </tr>
                        <tr>
                            <td style="color: #475569; padding: 2px 0;"><strong>Latitude</strong></td>
                            <td style="text-align: right;">${item.latitude}</td>
                        </tr>
                    </table>
                </div>
            `;

            // CircleMarker ditempatkan di paneTitik agar bisa diklik memunculkan pop-up
            var marker = L.circleMarker([item.latitude, item.longitude], {
                pane: "paneTitik",
                radius: 7,
                fillColor: "#ff0000",
                color: "#ffffff",
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9
            })
            .bindPopup(popupContent)
            .bindTooltip("Kapanewon " + item.kecamatan, { direction: "top" });

            marker.addTo(layerGroupPopup);
            markerById[item.id] = marker;
        });

        // ==========================================
        // 4. LAYER CONTROL (COLLAPSIBLE / CLEAN)
        // ==========================================
        var baseMaps = {
            "Basemap - OpenStreetMap": osm
        };

        var overlayMaps = {
            "Titik - Kecamatan & Pop-up (MySQL)": layerGroupPopup,
            "Titik - Kecamatan WMS (GeoServer)": layerKecWms,
            "Titik - Toponimi": layerTitik,
            "Garis - Jaringan Jalan": layerJalan,
            "Polygon - Batas Administrasi Desa": layerPolygon
        };

        L.control.layers(baseMaps, overlayMaps, {
            collapsed: true
        }).addTo(map);

        // ==========================================
        // 5. LEGENDA SCROLLABLE
        // ==========================================
        var customLegend = L.control({ position: "bottomleft" });

        customLegend.onAdd = function (map) {
            var div = L.DomUtil.create("div", "info legend");
            
            L.DomEvent.disableScrollPropagation(div);
            L.DomEvent.disableClickPropagation(div);

            var legendDesaImg = geoserverWmsUrl + "?REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&LAYER=pgweb11:ADMINISTRASIDESA_AR_25K";

            div.innerHTML = `
                <h4>Legenda</h4>
                <div class="legend-item">
                    <span class="symbol-point-kec"></span>
                    <span>Titik - Kecamatan (MySQL)</span>
                </div>
                <div class="legend-item">
                    <span class="symbol-point"></span>
                    <span>Titik - Toponimi</span>
                </div>
                <div class="legend-item">
                    <span class="symbol-line-jalan"></span>
                    <span>Garis - Jaringan Jalan</span>
                </div>
                <hr style="border: 0; border-top: 1px solid #ddd; margin: 8px 0;">
                <div style="font-weight: bold; margin-bottom: 4px;">Polygon - Batas Administrasi Desa</div>
                <img src="${legendDesaImg}" alt="Legenda Batas Desa">
            `;
            return div;
        };
        customLegend.addTo(map);

        // ==========================================
        // 6. INTERAKSI TABEL KE MARKER PETA
        // ==========================================
        function zoomKeKecamatan(id) {
            var marker = markerById[id];
            if (marker) {
                var latLng = marker.getLatLng();
                map.flyTo(latLng, 13, { duration: 0.8 });
                marker.openPopup();
            }
        }

        // Pencarian cepat di tabel
        $("#searchKecamatan").on("keyup", function () {
            var keyword = $(this).val().toLowerCase();
            $("#tbodyPenduduk tr").filter(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(keyword) > -1);
            });
        });

        // Tombol Sembunyikan / Tampilkan Tabel
        function toggleTabel() {
            var panel = document.getElementById("tablePanel");
            var btn = document.getElementById("toggleTableBtn");
            panel.classList.toggle("hidden");

            if (panel.classList.contains("hidden")) {
                btn.textContent = "Tampilkan Tabel Data";
            } else {
                btn.textContent = "Sembunyikan Tabel Data";
            }

            setTimeout(function () {
                map.invalidateSize();
            }, 150);
        }
    </script>

</body>
</html>