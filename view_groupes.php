<?php session_start(); if (!isset($_SESSION['logged_in'])) { header('Location: login.php'); exit; } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groupes - Infraction CIMAT</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }

        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; }
        .back { margin-top: 15px; }
        .back a { color: white; text-decoration: none; opacity: 0.9; }

        .filter-box { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .filter-box label { display: inline-block; margin-right: 20px; margin-bottom: 10px; cursor: pointer; }
        .filter-box input[type="checkbox"] { margin-right: 8px; transform: scale(1.2); }
        .filter-box .select-all { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .filter-box button { padding: 10px 25px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px; }

        .tables-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .table-box { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .table-box .box-header { padding: 20px; color: white; font-weight: bold; }
        .trajet-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .eco-header { background: linear-gradient(135deg, #f59e0b 0%, #dc2626 100%); }

        table { width: 100%; border-collapse: collapse; }
        th { padding: 12px 15px; text-align: left; font-size: 12px; color: #666; border-bottom: 2px solid #eee; }
        td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 13px; }
        tr:hover { background: #f9f9f9; }
        tr:last-child td { border-bottom: none; }

        .number { font-weight: bold; color: #667eea; }
        .eco-number { font-weight: bold; color: #dc2626; }
        .group-name { font-weight: 600; color: #333; }
        .zero-data { color: #999; font-style: italic; }
        .priority-group { background: #fff9e6; border-left: 4px solid #f59e0b; }
        .priority-badge { background: #f59e0b; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px; margin-left: 8px; }

        @media (max-width: 900px) {
            .tables-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 Groupes</h1>
            <div class="back"><a href="index.php">← Retour à l'accueil</a></div>
        </div>

        <!-- FILTER WITH CHECKBOXES -->
        <form method="GET" class="filter-box">
            <div class="select-all">
                <label><input type="checkbox" id="selectAll" onchange='toggleAll(this)'> <strong>Tout sélectionner / Désélectionner</strong></label>
            </div>
            <?php
            require_once "db.php";
            require_once "api.php";

            $db = Cnx();

            // Get selected groups from URL
            $selected = isset($_GET['groups']) ? $_GET['groups'] : array();

            // Use ALL groups from api.php configuration - priority groups first
            $priority_list = array();
            $regular_list = array();
            foreach ($tab_group as $group_name => $group_info) {
                if (in_array($group_name, $priority_groups)) {
                    $priority_list[] = $group_name;
                } else {
                    $regular_list[] = $group_name;
                }
            }
            $ordered_groups = array_merge($priority_list, $regular_list);

            foreach ($ordered_groups as $group_name) {
                $checked = in_array($group_name, $selected) ? 'checked' : '';
                $is_priority = in_array($group_name, $priority_groups);
                $style = $is_priority ? 'font-weight:bold; color:#f59e0b;' : '';
                echo "<label style='$style'><input type='checkbox' name='groups[]' value='$group_name' $checked onchange='updateSelectAll()'> $group_name</label>";
            }
            ?>
            <br>
            <button type="submit">Filtrer</button>
            <a href="view_groupes.php" style="margin-left: 15px; color: #666;">Réinitialiser</a>
        </form>

        <div class="tables-container">
            <!-- TABLE TRAJET -->
            <div class="table-box">
                <div class="box-header trajet-header">
                    📊 Groupes - Trajets
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Groupe</th>
                            <th>Trajets</th>
                            <th>Kilométrage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT regroupement, COUNT(*) as nb, SUM(kilometrage) as km FROM trajet WHERE 1=1";
                        $params = array();

                        if (!empty($selected)) {
                            $placeholders = implode(',', array_fill(0, count($selected), '?'));
                            $sql .= " AND regroupement IN ($placeholders)";
                            $params = $selected;
                        }

                        $sql .= " GROUP BY regroupement ORDER BY nb DESC";

                        $stmt = $db->prepare($sql);
                        $stmt->execute($params);
                        $trajet_data = array();
                        foreach ($stmt->fetchAll() as $row) {
                            $trajet_data[$row['regroupement']] = $row;
                        }

                        // Separate priority and regular groups
                        $priority_list = array();
                        $regular_list = array();
                        foreach ($tab_group as $group_name => $group_info) {
                            if (in_array($group_name, $priority_groups)) {
                                $priority_list[] = $group_name;
                            } else {
                                $regular_list[] = $group_name;
                            }
                        }
                        $ordered_groups = array_merge($priority_list, $regular_list);

                        // Display ALL groups from config, showing 0 for those without data
                        foreach ($ordered_groups as $group_name) {
                            if (!empty($selected) && !in_array($group_name, $selected)) continue;

                            $is_priority = in_array($group_name, $priority_groups);
                            $row_class = $is_priority ? 'priority-group' : '';

                            if (isset($trajet_data[$group_name])) {
                                $row = $trajet_data[$group_name];
                                $badge = $is_priority ? "<span class='priority-badge'>⭐ Priorité</span>" : "";
                                echo "<tr class='$row_class'>";
                                echo "<td class='group-name'>{$row['regroupement']}$badge</td>";
                                echo "<td class='number'>{$row['nb']}</td>";
                                echo "<td>" . number_format($row['km'], 1) . " km</td>";
                                echo "</tr>";
                            } else {
                                $badge = $is_priority ? "<span class='priority-badge'>⭐ Priorité</span>" : "";
                                echo "<tr class='$row_class'>";
                                echo "<td class='group-name zero-data'>$group_name$badge</td>";
                                echo "<td class='zero-data'>0</td>";
                                echo "<td class='zero-data'>0.0 km</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- TABLE ECODRIVING -->
            <div class="table-box">
                <div class="box-header eco-header">
                    ⚠️ Groupes - Éco-conduite
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Groupe</th>
                            <th>Écarts</th>
                            <th>Pénalités</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT regroupement, COUNT(*) as nb, SUM(penalites) as penalites FROM ecodriving WHERE 1=1";
                        $params = array();

                        if (!empty($selected)) {
                            $placeholders = implode(',', array_fill(0, count($selected), '?'));
                            $sql .= " AND regroupement IN ($placeholders)";
                            $params = $selected;
                        }

                        $sql .= " GROUP BY regroupement ORDER BY nb DESC";

                        $stmt = $db->prepare($sql);
                        $stmt->execute($params);
                        $eco_data = array();
                        foreach ($stmt->fetchAll() as $row) {
                            $eco_data[$row['regroupement']] = $row;
                        }

                        // Separate priority and regular groups
                        $priority_list = array();
                        $regular_list = array();
                        foreach ($tab_group as $group_name => $group_info) {
                            if (in_array($group_name, $priority_groups)) {
                                $priority_list[] = $group_name;
                            } else {
                                $regular_list[] = $group_name;
                            }
                        }
                        $ordered_groups = array_merge($priority_list, $regular_list);

                        // Display ALL groups from config, showing 0 for those without data
                        foreach ($ordered_groups as $group_name) {
                            if (!empty($selected) && !in_array($group_name, $selected)) continue;

                            $is_priority = in_array($group_name, $priority_groups);
                            $row_class = $is_priority ? 'priority-group' : '';

                            if (isset($eco_data[$group_name])) {
                                $row = $eco_data[$group_name];
                                $badge = $is_priority ? "<span class='priority-badge'>⭐ Priorité</span>" : "";
                                echo "<tr class='$row_class'>";
                                echo "<td class='group-name'>{$row['regroupement']}$badge</td>";
                                echo "<td class='eco-number'>{$row['nb']}</td>";
                                echo "<td>" . number_format($row['penalites']) . "</td>";
                                echo "</tr>";
                            } else {
                                $badge = $is_priority ? "<span class='priority-badge'>⭐ Priorité</span>" : "";
                                echo "<tr class='$row_class'>";
                                echo "<td class='group-name zero-data'>$group_name$badge</td>";
                                echo "<td class='zero-data'>0</td>";
                                echo "<td class='zero-data'>0</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TOTALS -->
        <div style="margin-top: 20px; background: white; padding: 20px; border-radius: 10px; text-align: center;">
            <?php
            $sql_trajet = "SELECT COUNT(*) FROM trajet WHERE 1=1";
            $sql_eco = "SELECT COUNT(*) FROM ecodriving WHERE 1=1";

            if (!empty($selected)) {
                $placeholders = implode(',', array_fill(0, count($selected), '?'));
                $sql_trajet .= " AND regroupement IN ($placeholders)";
                $sql_eco .= " AND regroupement IN ($placeholders)";
                $stmt_t = $db->prepare($sql_trajet);
                $stmt_t->execute($selected);
                $total_trajet = $stmt_t->fetchColumn();
                $stmt_e = $db->prepare($sql_eco);
                $stmt_e->execute($selected);
                $total_eco = $stmt_e->fetchColumn();
            } else {
                $total_trajet = $db->query("SELECT COUNT(*) FROM trajet")->fetchColumn();
                $total_eco = $db->query("SELECT COUNT(*) FROM ecodriving")->fetchColumn();
            }
            ?>
            <strong>Total affiché:</strong> <?php echo $total_trajet; ?> trajets | <?php echo $total_eco; ?> écarts éco-conduite
        </div>
    </div>

    <script>
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('input[name="groups[]"]');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }

        function updateSelectAll() {
            const checkboxes = document.querySelectorAll('input[name="groups[]"]');
            const selectAll = document.getElementById('selectAll');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            selectAll.checked = allChecked && checkboxes.length > 0;
        }

        // Initialize selectAll state on page load
        window.onload = function() {
            updateSelectAll();
        };
    </script>
</body>
</html>
