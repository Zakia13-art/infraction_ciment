<?php
require_once "db.php";
$db = Cnx();

// Vider les tables
$db->exec("DELETE FROM trajet");
$db->exec("DELETE FROM ecodriving");
echo "Tables vidées - Réinitialise les AUTO_INCREMENT\n";
$db->exec("ALTER TABLE trajet AUTO_INCREMENT = 1");
$db->exec("ALTER TABLE ecodriving AUTO_INCREMENT = 1");

echo "<form method='POST' style='text-align:center; padding:50px;'>
    <h2>Vider la base et ré-importer</h2>
    <button type='submit' name='confirm' style='padding:15px 30px;font-size:16px;background:#dc2626;color:white;border:none;border-radius:5px;cursor:pointer;'>🔄 Lancer l'import avec Template 1</button>
    <br><br>
    <a href='index.php' style='color:#666;'>Annuler</a>
</form>";

if (isset($_POST['confirm'])) {
    echo "<script>window.location.href='import.php';</script>";
}
?>
