<?php session_start(); if (!isset($_SESSION['logged_in'])) { header('Location: login.php'); exit; } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trajets - Infraction CIMAT</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }

        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; }
        .back { margin-top: 15px; }
        .back a { color: white; text-decoration: none; opacity: 0.9; }

        .filter { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .filter select, .filter input { padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-right: 10px; }
        .filter button { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; }

        .summary-box { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .summary-title { font-size: 16px; font-weight: bold; margin-bottom: 15px; color: #333; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; }
        .summary-item { background: #f9f9f9; padding: 12px; border-radius: 5px; border-left: 4px solid #667eea; }
        .summary-item.zero { border-left-color: #ccc; opacity: 0.7; }
        .summary-item .name { font-weight: 600; font-size: 13px; }
        .summary-item .count { font-size: 18px; color: #667eea; margin-top: 5px; }
        .summary-item.zero .count { color: #999; }
        .summary-item.priority { border-left-color: #f59e0b; background: #fff9e6; }

        .table-container { background: white; border-radius: 10px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #667eea; color: white; padding: 12px; text-align: left; font-size: 12px; white-space: nowrap; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 12px; }
        tr:hover { background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Trajets</h1>
            <div class="back"><a href="index.php">← Retour à l'accueil</a></div>
        </div>

        <form method="GET" class="filter">
            <select name="regroupement">
                <option value="">Tous les regroupements</option>
                <?php
                require_once "db.php";
                $db = Cnx();
                $groups = $db->query("SELECT DISTINCT regroupement FROM trajet ORDER BY regroupement")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($groups as $group_name) {
                    $selected = (isset($_GET['regroupement']) && $_GET['regroupement'] == $group_name) ? 'selected' : '';
                    echo "<option value='$group_name' $selected>$group_name</option>";
                }
                ?>
            </select>
            <input type="date" name="date_debut" value="<?php echo $_GET['date_debut'] ?? ''; ?>" placeholder="Date début">
            <input type="date" name="date_fin" value="<?php echo $_GET['date_fin'] ?? ''; ?>" placeholder="Date fin">
            <button type="submit">Filtrer</button>
            <a href="view_trajet.php" style="margin-left: 10px; color: #666;">Réinitialiser</a>
        </form>

        <!-- SUMMARY BY GROUP -->
        <div class="summary-box">
            <div class="summary-title">📁 Résumé par groupe</div>
            <div class="summary-grid">
                <?php
                $sql = "SELECT regroupement, COUNT(*) as nb FROM trajet WHERE 1=1";
                $params = array();
                if (!empty($_GET['regroupement'])) {
                    $sql .= " AND regroupement = ?";
                    $params[] = $_GET['regroupement'];
                }
                $sql .= " GROUP BY regroupement";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $data = array();
                foreach ($stmt->fetchAll() as $row) {
                    $data[$row['regroupement']] = $row['nb'];
                }

                // Sort groups by count descending
                arsort($data);
                foreach ($data as $group_name => $count) {
                    if (!empty($_GET['regroupement']) && $_GET['regroupement'] != $group_name) continue;
                    $class = $count > 0 ? '' : 'zero';
                    echo "<div class='summary-item $class'>";
                    echo "<div class='name'>$group_name</div>";
                    echo "<div class='count'>$count trajets</div>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- DETAILED TABLE -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Regroupement</th>
                        <th>Conducteur</th>
                        <th>Départ de</th>
                        <th>Trajet vers</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Kilométrage</th>
                        <th>Durée trajet</th>
                        <th>Pénalités</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM trajet WHERE 1=1";
                    $params = array();

                    if (!empty($_GET['regroupement'])) {
                        $sql .= " AND regroupement = ?";
                        $params[] = $_GET['regroupement'];
                    }
                    if (!empty($_GET['date_debut'])) {
                        $sql .= " AND DATE(debut) >= ?";
                        $params[] = $_GET['date_debut'];
                    }
                    if (!empty($_GET['date_fin'])) {
                        $sql .= " AND DATE(debut) <= ?";
                        $params[] = $_GET['date_fin'];
                    }

                    $sql .= " ORDER BY debut DESC LIMIT 500";

                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    $trajets = $stmt->fetchAll();

                    foreach ($trajets as $row) {
                        echo "<tr>";
                        echo "<td><strong>{$row['regroupement']}</strong></td>";
                        echo "<td>{$row['conducteur']}</td>";
                        echo "<td>{$row['depart_de']}</td>";
                        echo "<td>{$row['trajet_vers']}</td>";
                        echo "<td>{$row['debut']}</td>";
                        echo "<td>{$row['fin']}</td>";
                        echo "<td>" . number_format($row['kilometrage'], 1) . " km</td>";
                        echo "<td>{$row['duree_trajet']}</td>";
                        echo "<td>{$row['penalites']}</td>";
                        echo "</tr>";
                    }

                    if (empty($trajets)) {
                        echo "<tr><td colspan='9' style='text-align:center; padding: 30px; color: #999;'>Aucun résultat</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
