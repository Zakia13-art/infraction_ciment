<?php

set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "db.php";

define('WIALON_TOKEN',       'b6db68331b4b6ed14b61dbfeeaad9a0605EA995CF621CE53D5C01A0A29C9FCFB6B2902A8');
define('WIALON_BASE_URL',    'https://hst-api.wialon.com/wialon/ajax.html');
define('WIALON_RESOURCE_ID', 22861605);
define('RAPPORT_ECODRIVING', 6);
define('RAPPORT_TRAJETS',    36);

$SID = '';
$stats = ['groupes'=>0,'vehicules'=>0,'eco'=>0,'traj'=>0,'eco_ig'=>0,'traj_ig'=>0,'err'=>0];
$periodes_traitees = 0;

echo "<html><head><meta charset='UTF-8'><title>Wialon Import</title>
<style>
body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;padding:20px}
.container{max-width:900px;margin:0 auto;background:white;border-radius:10px;padding:30px;box-shadow:0 2px 10px rgba(0,0,0,.1)}
pre{background:#1e1e1e;color:#d4d4d4;padding:15px;border-radius:8px;overflow-x:auto;font-size:13px;line-height:1.5}
.ok{color:#4ec9b0}.er{color:#f48771}.wn{color:#dcdcaa}.in{color:#569cd6}
</style></head><body><div class='container'>";
echo "<h1>Wialon Import</h1><pre>";

// Mois à importer (par défaut: 2026-04)
$moisAnnee = $_GET['mois'] ?? '2026-04';
$moisDebutTS = strtotime("first day of $moisAnnee 00:00:00");
$moisFinTS   = strtotime("last day of $moisAnnee 23:59:59");
logMsg("Mois: $moisAnnee (" . date('d/m/Y', $moisDebutTS) . ' → ' . date('d/m/Y', $moisFinTS) . ')', 'in');

// Construire les blocs de 5 jours (du plus récent au plus ancien)
$blocs = [];
$finBloc = $moisFinTS;
while ($finBloc >= $moisDebutTS) {
    $debutBloc = max($finBloc - 4 * 86400, $moisDebutTS);
    $debutBloc = strtotime(date('Y-m-d', $debutBloc) . ' 00:00:00');
    $finBloc   = strtotime(date('Y-m-d', $finBloc) . ' 23:59:59');
    $blocs[] = ['from' => $debutBloc, 'to' => $finBloc];
    $finBloc = $debutBloc - 86400;
}
logMsg(count($blocs) . " bloc(s) de 5 jours", 'in');

// Auth
$auth = wialonCall('token/login', ['token' => WIALON_TOKEN, 'fl' => 1]);
if (empty($auth['eid'])) { logMsg("Auth échouée", 'er'); exit; }
$SID = $auth['eid'];
logMsg("Connecté OK", 'ok');

// Charger groupes (sans toutes les unités - juste les groupes)
$groups = loadGroups();
$units = [];

// 54 groupes ACL
$ACL = [
    'TRAVAUX API','AFAB DE CONSTRUCTION','AKRITE USB','ATLAS ESSAGHIR','BAIDDAH',
    'BASSO','BENMI CIMENT','BOUAMAMA','BOUMARA TRANS','CATRAD',
    'COGEMIL API','CSL API','DAR ALLATI','ENTRAVAIL','FADEL MAC USB',
    'IFLILT','KAWTARI USB','LAKHOUILI','LARMOUS API','LBIDA',
    'LTG TRANS','MADATRANS','MATERCA','MIDASS API','MIXTRA',
    'MKH SERVICE','MR CONSTRUCTION','NAJACOT API','OULAINE','OULFA CIMENT',
    'POLIMA TRANS','QATRANS','RACHKAM','SAADA PLATRE','SAHATEC',
    'SNTL','SOCILOG','SODRIWCHAT','SOLOGAT','SOTAMACOD',
    'SOTRAINCIMENT','TIOUGHZA','TRAJET SUD','TRANSBULK','TRANS MOUNTAHA USB',
    'TRANS NEJ','TRANSPONA USB','ULTRA MAC','VIG TRANS','WIFAK USB','WITRANS',
];

// Filtrer groupes + collecter unités autorisées
$aclGroups = [];
$aclUnitIds = [];
foreach ($groups as $nom => $info) {
    $match = false;
    foreach ($ACL as $a) {
        if ($nom === $a || str_contains($nom, $a) || str_contains($a, $nom)) { $match = true; break; }
    }
    if ($match) {
        $aclGroups[$nom] = $info;
        foreach ($info['unites'] as $uid) $aclUnitIds[] = (int)$uid;
    }
}
$aclUnitIds = array_unique($aclUnitIds);
logMsg(count($aclGroups) . " groupes autorisés | " . count($aclUnitIds) . " unités", 'ok');

// Boucle sur chaque bloc de 4 jours
foreach ($blocs as $bIdx => $bloc) {
    $d1 = date('d/m/Y', $bloc['from']);
    $d2 = date('d/m/Y', $bloc['to']);
    logMsg("\n" . str_repeat('═', 50), 'in');
    logMsg("BLOC " . ($bIdx + 1) . "/" . count($blocs) . ": $d1 → $d2", 'in');
    logMsg(str_repeat('═', 50), 'in');
    $periodes_traitees++;

    foreach ($aclGroups as $nom => $info) {
        $groupId = $info['id'];
        $uIds = $info['unites'];
        $grp = mb_strtoupper($nom);
        if (empty($uIds)) continue;

        // TRAJETS: par GROUPE
        importTrajByGroup($groupId, $grp, $bloc['from'], $bloc['to']);

        // ECO: par GROUPE (au lieu de par unité)
        importEcoByGroup($groupId, $grp, $bloc['from'], $bloc['to']);
    }
}

wialonCall('core/logout', []);
logMsg("\n" . str_repeat('═', 50), 'in');
logMsg("IMPORT TERMINÉ - {$periodes_traitees} périodes", 'ok');
logMsg("Eco: {$stats['eco']} | Trajets: {$stats['traj']} | Erreurs: {$stats['err']}", 'ok');
logMsg("Doublons - Eco: {$stats['eco_ig']} | Trajets: {$stats['traj_ig']}", 'wn');
echo "</pre></div></body></html>";


// =====================================================================
function importEcoByGroup($groupId, $grp, $from, $to) {
    global $SID, $stats;

    $res = execRapport(RAPPORT_ECODRIVING, $groupId, $from, $to);
    if (!$res) return;

    $count = 0;
    // Par groupe, template 6 peut retourner plusieurs tables (1 par unité)
    for ($tIdx = 0; $tIdx < 20; $tIdx++) {
        $rows = getRows($tIdx);
        if (empty($rows)) break;

        foreach ($rows as $row) {
            $c = $row['c'] ?? [];

            $regroupement = $grp;
            $conducteur   = txt($c[1]) ?: txt($c[0]);
            $infraction   = txt($c[3]);
            $debut        = parseDT($c[4]);
            $fin          = parseDT($c[5]);

            if (!$debut || !$fin || !$infraction) continue;

            try {
                $db = Cnx();
                $chk = $db->prepare("SELECT 1 FROM ecodriving WHERE regroupement=? AND conducteur=? AND debut=? AND fin=? AND infraction=?");
                $chk->execute([$regroupement, $conducteur, $debut, $fin, $infraction]);
                if ($chk->fetch()) { $stats['eco_ig']++; continue; }

                $kmRaw = trim(txt($c[7]));
                $kmRaw = preg_replace('/\s*km$/i', '', $kmRaw);
                $penStr = trim(txt($c[13]));
                $valRaw = trim(txt($c[9]));

                $db->prepare("INSERT INTO ecodriving (regroupement,conducteur,emplacement_initial,infraction,debut,fin,lieu_arrivee,kilometrage,duree,valeur,vitesse_moyenne,vitesse_finale,compte,penalites) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
                    $regroupement, $conducteur,
                    mb_substr(txt($c[2]),0,255), $infraction,
                    $debut, $fin,
                    mb_substr(txt($c[6]),0,255),
                    is_numeric($kmRaw) ? (float)$kmRaw : 0,
                    mb_substr(txt($c[8]),0,50),
                    ($valRaw==='-----'||$valRaw==='') ? null : (float)$valRaw,
                    pVal($c[10]), pVal($c[11]),
                    (int)pVal($c[12]),
                    ($penStr==='-----'||$penStr==='') ? 0 : (int)$penStr,
                ]);
                $stats['eco']++;
                $count++;
            } catch (Throwable $e) {
                logMsg("Eco: " . $e->getMessage(), 'er');
                $stats['err']++;
            }
        }
    }

    if ($count > 0) {
        logMsg("  Eco importés: {$count}", 'ok');
    }
    wialonCall('report/cleanup_result', []);
}

function importEco($uId, $uNom, $grp, $from, $to) {
    global $SID, $stats;

    $res = execRapport(RAPPORT_ECODRIVING, $uId, $from, $to);
    if (!$res) return;

    $rows = getRows(0);
    foreach ($rows as $row) {
        $c = $row['c'] ?? [];

        $regroupement = $grp;
        $conducteur   = txt($c[1]) ?: $uNom;
        $infraction   = txt($c[3]);
        $debut        = parseDT($c[4]);
        $fin          = parseDT($c[5]);

        if (!$debut || !$fin || !$infraction) continue;

        try {
            $db = Cnx();
            $chk = $db->prepare("SELECT 1 FROM ecodriving WHERE regroupement=? AND conducteur=? AND debut=? AND fin=? AND infraction=?");
            $chk->execute([$regroupement, $conducteur, $debut, $fin, $infraction]);
            if ($chk->fetch()) { $stats['eco_ig']++; continue; }

            $kmRaw = trim(txt($c[7]));
            $kmRaw = preg_replace('/\s*km$/i', '', $kmRaw);
            $penStr = trim(txt($c[13]));
            $valRaw = trim(txt($c[9]));

            $db->prepare("INSERT INTO ecodriving (regroupement,conducteur,emplacement_initial,infraction,debut,fin,lieu_arrivee,kilometrage,duree,valeur,vitesse_moyenne,vitesse_finale,compte,penalites) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
                $regroupement, $conducteur,
                mb_substr(txt($c[2]),0,255), $infraction,
                $debut, $fin,
                mb_substr(txt($c[6]),0,255),
                is_numeric($kmRaw) ? (float)$kmRaw : 0,
                mb_substr(txt($c[8]),0,50),
                ($valRaw==='-----'||$valRaw==='') ? null : (float)$valRaw,
                pVal($c[10]), pVal($c[11]),
                (int)pVal($c[12]),
                ($penStr==='-----'||$penStr==='') ? 0 : (int)$penStr,
            ]);
            $stats['eco']++;
        } catch (Throwable $e) {
            logMsg("Eco: " . $e->getMessage(), 'er');
            $stats['err']++;
        }
    }
    wialonCall('report/cleanup_result', []);
}

function importTrajByGroup($groupId, $grp, $from, $to) {
    global $SID, $stats;

    $res = execRapport(RAPPORT_TRAJETS, $groupId, $from, $to);
    if (!$res) return;

    // Par groupe, template 36 retourne plusieurs tables
    // On prend seulement table 0 (les trajets détaillés)
    $rows = getRows(0);
    $count = 0;

    foreach ($rows as $row) {
        $c = $row['c'] ?? [];

        // Détection offset: si c[0] est un numéro (1, 2, 3...), on décale de 1
        $first = trim(txt($c[0]));
        $o = preg_match('/^\d+(\.\d+)?$/', $first) ? 1 : 0;

        // 12 colonnes (sans №) ou 13 colonnes (avec №)
        // c[$o+0]  = véhicule / nom
        // c[$o+1]  = conducteur
        // c[$o+2]  = parcours (ex: "Carrière Ameskroud - AIT BAHA")
        // c[$o+3]  = depart_de (ex: "Carrière Ameskroud")
        // c[$o+4]  = trajet_vers (ex: "AIT BAHA")
        // c[$o+5]  = debut
        // c[$o+6]  = fin
        // c[$o+7]  = compte
        // c[$o+8]  = kilometrage (ex: "186 km")
        // c[$o+9]  = duree_trajet (ex: "5:35:43")
        // c[$o+10] = duree_stationnement (ex: "5:25:45")
        // c[$o+11] = penalites

        $regroupement = $grp;
        $conducteur   = txt($c[$o+1]) ?: txt($c[$o+0]);
        $debut        = parseDT($c[$o+5]);
        $fin          = parseDT($c[$o+6]);
        $trajetVers   = mb_substr(txt($c[$o+4]),0,255);

        if (!$debut || !$fin) continue;

        try {
            $db = Cnx();
            $chk = $db->prepare("SELECT 1 FROM trajet WHERE regroupement=? AND conducteur=? AND debut=? AND fin=? AND trajet_vers=?");
            $chk->execute([$regroupement, $conducteur, $debut, $fin, $trajetVers]);
            if ($chk->fetch()) { $stats['traj_ig']++; continue; }

            $kmRaw = trim(txt($c[$o+8]));
            $kmRaw = preg_replace('/\s*km$/i', '', $kmRaw);
            $penStr = trim(txt($c[$o+11]));

            $db->prepare("INSERT INTO trajet (regroupement,conducteur,parcours,depart_de,trajet_vers,debut,fin,compte,kilometrage,duree_trajet,duree_stationnement,penalites) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
                $regroupement, $conducteur,
                mb_substr(txt($c[$o+2]),0,255), mb_substr(txt($c[$o+3]),0,255),
                $trajetVers, $debut, $fin,
                (int)pVal($c[$o+7]),
                is_numeric($kmRaw) ? (float)$kmRaw : 0,
                mb_substr(txt($c[$o+9]),0,50), mb_substr(txt($c[$o+10]),0,50),
                ($penStr==='-----'||$penStr==='') ? 0 : (int)$penStr,
            ]);
            $stats['traj']++;
            $count++;
        } catch (Throwable $e) {
            logMsg("Traj: " . $e->getMessage(), 'er');
            $stats['err']++;
        }
    }

    if ($count > 0) {
        logMsg("  Trajets importés: {$count}", 'ok');
    }

    wialonCall('report/cleanup_result', []);
}


// =====================================================================
// HELPERS
// =====================================================================
function wialonCall($svc, $params) {
    global $SID;
    $ch = curl_init(WIALON_BASE_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['svc' => $svc, 'params' => json_encode($params), 'sid' => $SID]),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 180, CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $raw = curl_exec($ch); curl_close($ch);
    return json_decode($raw, true) ?: [];
}

function execRapport($tpl, $obj, $from, $to) {
    $r = wialonCall('report/exec_report', [
        'reportResourceId' => WIALON_RESOURCE_ID, 'reportTemplateId' => $tpl,
        'reportObjectId' => $obj, 'reportObjectSecId' => 0,
        'interval' => ['flags' => 0, 'from' => $from, 'to' => $to],
    ]);
    if (!empty($r['error'])) return null;
    if (!empty($r['reportResult'])) return $r['reportResult'];
    for ($i = 0; $i < 5; $i++) {
        sleep(1);
        $st = wialonCall('report/get_report_status', []);
        if (empty($st['progress'])) {
            $f = wialonCall('report/apply_report_result', []);
            return $f['reportResult'] ?? null;
        }
    }
    return null;
}

function getRows($idx) {
    $r = wialonCall('report/get_result_rows', ['tableIndex' => $idx, 'indexFrom' => 0, 'indexTo' => 9999]);
    if (isset($r['rows'])) return $r['rows'];
    if (isset($r[0])) return $r;
    return [];
}

function loadGroups() {
    $g = wialonCall('core/search_items', [
        'spec'=>['itemsType'=>'avl_unit_group','propName'=>'sys_name','propValueMask'=>'*','sortType'=>'sys_name'],
        'force'=>1,'flags'=>1,'from'=>0,'to'=>0]);
    $groups = [];
    foreach ($g['items'] ?? [] as $i)
        $groups[mb_strtoupper(trim($i['nm']))] = ['id'=>$i['id'],'unites'=>$i['u']??[]];
    return $groups;
}

function txt($c) {
    return is_array($c) ? (string)($c['t'] ?? $c['v'] ?? '') : (string)($c);
}

function pVal($v) {
    $s = trim(txt($v));
    if (is_numeric($s)) return (float)$s;
    $s = preg_replace('/[^\d.,\-]/', '', $s);
    $s = str_replace(',', '.', $s);
    return (float)$s;
}

function parseDT($v) {
    $s = trim(txt($v));
    if (!$s || $s === '-----') return null;
    if (preg_match('#^(\d{2})/(\d{2})/(\d{4})\s+(\d{2}:\d{2}:\d{2})$#', $s, $m))
        return "{$m[3]}-{$m[2]}-{$m[1]} {$m[4]}";
    if (preg_match('#^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$#', $s)) return $s;
    if (is_numeric($s) && $s > 1e9) return date('Y-m-d H:i:s', (int)$s);
    return null;
}

function logMsg($m, $l='in') {
    echo "<span class='{$l}'>" . htmlspecialchars($m) . "</span>\n";
    if (ob_get_level()) ob_flush(); flush();
}
?>
