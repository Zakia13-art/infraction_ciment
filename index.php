<?php session_start();
if (!isset($_SESSION['logged_in'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infraction CIMAT - Tableau de bord</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .stat-card h3 { font-size: 14px; color: #666; margin-bottom: 10px; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #667eea; }

        .actions { margin-bottom: 30px; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px; border: none; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #5568d3; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }

        .tabs { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab { padding: 12px 25px; background: white; border-radius: 5px; cursor: pointer; border: none; }
        .tab.active { background: #667eea; color: white; }

        .table-container { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-container table { width: 100%; border-collapse: collapse; }
        .table-container th { background: #667eea; color: white; padding: 15px; text-align: left; font-weight: 600; font-size: 13px; }
        .table-container td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 13px; }
        .table-container tr:hover { background: #f9f9f9; }
        .table-container tr:last-child td { border-bottom: none; }

        .filter { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .filter select, .filter input { padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚛 Infraction CIMAT</h1>
            <p>Suivi des trajets et éco-conduite</p>
        </div>

        <?php
        require_once "db.php";
        $db = Cnx();

        // Statistiques
        $stats_trajet = $db->query("SELECT COUNT(*) as total, SUM(kilometrage) as km FROM trajet")->fetch();
        $stats_eco = $db->query("SELECT COUNT(*) as total, SUM(penalites) as penalites FROM ecodriving")->fetch();
        ?>

        <div class="stats">
            <div class="stat-card">
                <h3>Trajets</h3>
                <div class="number"><?php echo number_format($stats_trajet['total']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Kilométrage total</h3>
                <div class="number"><?php echo number_format($stats_trajet['km'], 0); ?> km</div>
            </div>
            <div class="stat-card">
                <h3>Écarts éco-conduite</h3>
                <div class="number"><?php echo number_format($stats_eco['total']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Pénalités totales</h3>
                <div class="number"><?php echo number_format($stats_eco['penalites']); ?></div>
            </div>
        </div>

        <div class="actions">
            <button class="btn" onclick="window.location.href='WialonImport.php'">🔄 Import Wialon</button>
            <button class="btn" onclick="window.location.href='view_trajet.php'">📊 Voir les trajets</button>
            <button class="btn" onclick="window.location.href='view_ecodriving.php'">⚠️ Voir éco-conduite</button>
            <button class="btn btn-success" onclick="window.location.href='export_excel.php'">📥 Export Excel</button>
            <a href="login.php?logout=1" class="btn btn-danger">Déconnexion</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Derniers trajets</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent = $db->query("SELECT * FROM trajet ORDER BY debut DESC LIMIT 5")->fetchAll();
                    foreach ($recent as $row) {
                        echo "<tr>";
                        echo "<td><strong>{$row['conducteur']}</strong> - {$row['trajet_vers']} (" . number_format($row['kilometrage'], 1) . " km) - {$row['debut']}</td>";
                        echo "</tr>";
                    }
                    if (empty($recent)) {
                        echo "<tr><td colspan='1' style='text-align:center; padding: 30px; color: #999;'>Aucune donnée. <a href='WialonImport.php'>Importer les données</a></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
