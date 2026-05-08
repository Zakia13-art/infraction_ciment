<?php
session_start();
if (!isset($_SESSION['logged_in'])) { header('Location: login.php'); exit; }

require_once "db.php";
require_once "vendor/autoload.php";
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$db = Cnx();

$groups = $db->query("SELECT DISTINCT regroupement FROM (SELECT regroupement FROM trajet UNION SELECT regroupement FROM ecodriving) x ORDER BY regroupement")->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = $_POST['date_debut'] ?? '';
    $to = $_POST['date_fin'] ?? '';
    $grp = $_POST['regroupement'] ?? '';
    $type = $_POST['type'] ?? 'all';

    $spreadsheet = new Spreadsheet();
    $cols = range('A', 'Z');

    if ($type === 'all' || $type === 'trajet') {
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Trajets');

        $headers1 = ['Regroupement', 'Conducteur', 'Parcours', 'Depart de', 'Trajet vers', 'Debut', 'Fin', 'Compte', 'Kilometrage (km)', 'Duree trajet', 'Duree stationnement', 'Penalites'];
        for ($i = 0; $i < count($headers1); $i++) {
            $sheet1->setCellValue($cols[$i] . '1', $headers1[$i]);
        }
        $sheet1->getStyle('A1:L1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('667EEA');
        $sheet1->getStyle('A1:L1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');

        $sql = "SELECT * FROM trajet WHERE 1=1";
        $params = [];
        if ($from) { $sql .= " AND DATE(debut) >= ?"; $params[] = $from; }
        if ($to) { $sql .= " AND DATE(debut) <= ?"; $params[] = $to; }
        if ($grp) { $sql .= " AND regroupement = ?"; $params[] = $grp; }
        $sql .= " ORDER BY debut DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = 2;
        foreach ($stmt->fetchAll() as $r) {
            $sheet1->setCellValue('A' . $row, $r['regroupement']);
            $sheet1->setCellValue('B' . $row, $r['conducteur']);
            $sheet1->setCellValue('C' . $row, $r['parcours']);
            $sheet1->setCellValue('D' . $row, $r['depart_de']);
            $sheet1->setCellValue('E' . $row, $r['trajet_vers']);
            $sheet1->setCellValue('F' . $row, $r['debut']);
            $sheet1->setCellValue('G' . $row, $r['fin']);
            $sheet1->setCellValue('H' . $row, $r['compte']);
            $sheet1->setCellValue('I' . $row, $r['kilometrage']);
            $sheet1->setCellValue('J' . $row, $r['duree_trajet']);
            $sheet1->setCellValue('K' . $row, $r['duree_stationnement']);
            $sheet1->setCellValue('L' . $row, $r['penalites']);
            $row++;
        }
        foreach (range('A', 'L') as $c) { $sheet1->getColumnDimension($c)->setAutoSize(true); }
        
        $lastRow = $row - 1;
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet1->getStyle('A1:L' . $lastRow)->applyFromArray($styleArray);
    }

    if ($type === 'all' || $type === 'ecodriving') {
        if ($type === 'all') {
            $sheet2 = $spreadsheet->createSheet();
        } else {
            $sheet2 = $spreadsheet->getActiveSheet();
        }
        $sheet2->setTitle('Eco-conduite');

        $headers2 = ['Regroupement', 'Conducteur', 'Emplacement initial', 'Infraction', 'Debut', 'Fin', 'Lieu arrivee', 'Kilometrage (km)', 'Duree', 'Valeur', 'Vitesse moyenne', 'Vitesse finale', 'Compte', 'Penalites'];
        for ($i = 0; $i < count($headers2); $i++) {
            $sheet2->setCellValue($cols[$i] . '1', $headers2[$i]);
        }
        $sheet2->getStyle('A1:N1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DC2626');
        $sheet2->getStyle('A1:N1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');

        $sql = "SELECT * FROM ecodriving WHERE 1=1";
        $params = [];
        if ($from) { $sql .= " AND DATE(debut) >= ?"; $params[] = $from; }
        if ($to) { $sql .= " AND DATE(debut) <= ?"; $params[] = $to; }
        if ($grp) { $sql .= " AND regroupement = ?"; $params[] = $grp; }
        $sql .= " ORDER BY debut DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $row = 2;
        foreach ($stmt->fetchAll() as $r) {
            $sheet2->setCellValue('A' . $row, $r['regroupement']);
            $sheet2->setCellValue('B' . $row, $r['conducteur']);
            $sheet2->setCellValue('C' . $row, $r['emplacement_initial']);
            $sheet2->setCellValue('D' . $row, $r['infraction']);
            $sheet2->setCellValue('E' . $row, $r['debut']);
            $sheet2->setCellValue('F' . $row, $r['fin']);
            $sheet2->setCellValue('G' . $row, $r['lieu_arrivee']);
            $sheet2->setCellValue('H' . $row, $r['kilometrage']);
            $sheet2->setCellValue('I' . $row, $r['duree']);
            $sheet2->setCellValue('J' . $row, $r['valeur']);
            $sheet2->setCellValue('K' . $row, $r['vitesse_moyenne']);
            $sheet2->setCellValue('L' . $row, $r['vitesse_finale']);
            $sheet2->setCellValue('M' . $row, $r['compte']);
            $sheet2->setCellValue('N' . $row, $r['penalites']);
            $row++;
        }
        foreach (range('A', 'N') as $c) { $sheet2->getColumnDimension($c)->setAutoSize(true); }
        
        $lastRow = $row - 1;
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet2->getStyle('A1:N' . $lastRow)->applyFromArray($styleArray);
    }

    // بناء اسم الملف
    if ($grp) {
        $groupName = strtoupper($grp); // تحويل إلى أحرف كبيرة
        if ($type === 'trajet') {
            $filename = $groupName . "_TRAJET_" . ($to ?: 'all') . ".xlsx";
        } elseif ($type === 'ecodriving') {
            $filename = $groupName . "_ECODRIVING_" . ($to ?: 'all') . ".xlsx";
        } else {
            $filename = $groupName . "_TRAJET_ECODRIVING_" . ($to ?: 'all') . ".xlsx";
        }
    } else {
        $names = ['all' => 'TOUS_TRAJETS_ECO', 'trajet' => 'TOUS_TRAJETS', 'ecodriving' => 'TOUS_ECODRIVING'];
        $filename = $names[$type] . "_" . ($to ?: 'all') . ".xlsx";
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Excel - Infraction CIMAT</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 50px auto; background: white; border-radius: 10px; padding: 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 30px; color: #333; }
        label { display: block; font-weight: 600; margin-bottom: 5px; color: #555; font-size: 14px; }
        select, input[type="date"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; margin-bottom: 20px; }
        .btn { display: block; padding: 12px 30px; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: 600; width: 100%; margin-bottom: 10px; }
        .btn-trajet { background: #667eea; }
        .btn-trajet:hover { background: #5568d3; }
        .btn-eco { background: #dc2626; }
        .btn-eco:hover { background: #b91c1c; }
        .btn-all { background: #10b981; }
        .btn-all:hover { background: #059669; }
        .back { text-align: center; margin-top: 20px; }
        .back a { color: #666; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📥 Export Excel</h1>
        <form method="POST">
            <label for="regroupement">Regroupement</label>
            <select name="regroupement" id="regroupement">
                <option value="">Tous les regroupements</option>
                <?php foreach ($groups as $g): ?>
                    <option value="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars($g) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="date_debut">Date début</label>
            <input type="date" name="date_debut" id="date_debut">

            <label for="date_fin">Date fin</label>
            <input type="date" name="date_fin" id="date_fin">

            <button type="submit" name="type" value="trajet" class="btn btn-trajet">📥 Télécharger Trajets</button>
            <button type="submit" name="type" value="ecodriving" class="btn btn-eco">📥 Télécharger Eco-conduite</button>
            <button type="button" class="btn btn-all" onclick="downloadBoth()">📥 Télécharger les deux</button>
        </form>

        <script>
        function downloadBoth() {
            var form = document.querySelector('form');
            // Trajet
            var f1 = new FormData(form);
            f1.set('type', 'trajet');
            fetch('', {method:'POST', body:f1}).then(r => r.blob()).then(b => {
                var a = document.createElement('a');
                a.href = URL.createObjectURL(b);
                a.download = 'trajets.xlsx';
                a.click();
            });
            // Eco
            var f2 = new FormData(form);
            f2.set('type', 'ecodriving');
            fetch('', {method:'POST', body:f2}).then(r => r.blob()).then(b => {
                var a = document.createElement('a');
                a.href = URL.createObjectURL(b);
                a.download = 'eco_conduite.xlsx';
                a.click();
            });
        }
        </script>
        <div class="back"><a href="index.php">← Retour à l'accueil</a></div>
    </div>
</body>
</html>