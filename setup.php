<?php
/**
 * Script de création de la base de données
 * Exécuter ce fichier pour créer la base et les tables
 */

$host = 'localhost';
$username = 'root';
$password = '';

// Connexion sans base de données
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Créer la base de données
    $pdo->exec("CREATE DATABASE IF NOT EXISTS infraction_ciment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de données 'infraction_ciment' créée\n";

    // Sélectionner la base
    $pdo->exec("USE infraction_ciment");

    // Table TRAJET
    $sql = "CREATE TABLE IF NOT EXISTS trajet (
        id INT AUTO_INCREMENT PRIMARY KEY,
        regroupement VARCHAR(100),
        conducteur VARCHAR(100),
        parcours VARCHAR(255),
        depart_de VARCHAR(255),
        trajet_vers VARCHAR(255),
        debut DATETIME,
        fin DATETIME,
        compte INT DEFAULT 0,
        kilometrage DECIMAL(10,2) DEFAULT 0,
        duree_trajet VARCHAR(50),
        duree_stationnement VARCHAR(50),
        penalites INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_trajet (regroupement, conducteur, debut, fin, trajet_vers)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "✅ Table 'trajet' créée\n";

    // Table ECODRIVING
    $sql = "CREATE TABLE IF NOT EXISTS ecodriving (
        id INT AUTO_INCREMENT PRIMARY KEY,
        regroupement VARCHAR(100),
        conducteur VARCHAR(100),
        emplacement_initial VARCHAR(255),
        infraction VARCHAR(255),
        debut DATETIME,
        fin DATETIME,
        lieu_arrivee VARCHAR(255),
        kilometrage DECIMAL(10,2) DEFAULT 0,
        duree VARCHAR(50),
        valeur DECIMAL(10,2),
        vitesse_moyenne DECIMAL(10,2),
        vitesse_finale DECIMAL(10,2),
        compte INT DEFAULT 0,
        penalites INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_ecodriving (regroupement, conducteur, debut, fin, infraction)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo->exec($sql);
    echo "✅ Table 'ecodriving' créée\n";

    echo "\n🎉 Base de données et tables créées avec succès!\n";
    echo "<a href='index.php'>Aller à l'accueil</a>";

} catch(PDOException $e) {
    die("❌ Erreur: " . $e->getMessage());
}
?>
