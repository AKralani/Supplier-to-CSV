<?php
// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'csv');

try {
    // Connect to the MySQL database
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if tables are selected
    if (isset($_POST['tables']) && is_array($_POST['tables']) && count($_POST['tables']) > 0) {

        // Get the selected table names
        $selectedTables = $_POST['tables'];

        // Create a new CSV file
        $filename = 'all_in_one.csv';
        $file = fopen($filename, 'w');

        // Define the static column headers
        $staticColumnHeaders = ['Lieferantenname', 'Herstellerartikelnummer', 'EAN', 'Artikelnummer', 'Titel', 'Preis', 'Verfuegbar', 'Verfuegbar_Tage', 'Lieferantenbestand', 'Streckgengeschaeft'];

        // Write static column headers as the first line in the CSV file
        fwrite($file, implode('|', $staticColumnHeaders) . PHP_EOL); // Manually format and write the column headers

        $dataInclusionStock = $_POST['data_inclusion_Stock'];
        $dataInclusionEAN = $_POST['data_inclusion_EAN'];

        // Fetch data from each selected table and write to the CSV file
        foreach ($selectedTables as $tableName) {
            if ($dataInclusionStock === 'excludeStock' && $dataInclusionEAN === 'includeEAN') {
                $sql = "SELECT * FROM $tableName WHERE Lieferantenbestand != '' AND Lieferantenbestand != '0'";
            } elseif ($dataInclusionStock === 'excludeStock' && $dataInclusionEAN === 'excludeEAN') {
                $sql = "SELECT t1.*
                FROM $tableName t1
                INNER JOIN (
                    SELECT EAN, MIN(Preis) AS minPreis
                    FROM $tableName
                    GROUP BY EAN
                ) t2 ON t1.EAN = t2.EAN AND t1.Preis = t2.minPreis
                WHERE t1.Lieferantenbestand != '' AND t1.Lieferantenbestand != '0'
                ORDER BY t1.id ASC";
            } elseif ($dataInclusionStock === 'includeStock' && $dataInclusionEAN === 'includeEAN') {
                $sql = "SELECT * FROM $tableName";
            } elseif ($dataInclusionStock === 'includeStock' && $dataInclusionEAN === 'excludeEAN') {
                $sql = "SELECT t1.*
                FROM $tableName t1
                INNER JOIN (
                    SELECT EAN, MIN(Preis) AS minPreis
                    FROM $tableName
                    GROUP BY EAN
                ) t2 ON t1.EAN = t2.EAN AND t1.Preis = t2.minPreis
                ORDER BY t1.id ASC";
            }
            
            $stmt = $pdo->query($sql);

            // Get column names from the table
            $columnNames = [];
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $columnMeta = $stmt->getColumnMeta($i);
                $columnNames[] = $columnMeta['name'];
            }

            // Remove the 'id' column from the column names
            $columnNames = array_diff($columnNames, ['id']);
            $columnNames = array_diff($columnNames, ['created_at']);

            // Write data rows
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Exclude the 'id' column from the row data
                unset($row['id']);
                unset($row['created_at']);
                fwrite($file, implode('|', $row) . PHP_EOL); // Manually format and write the row data
            }
        }

        fclose($file);

        // Set the appropriate headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        readfile($filename);
        exit();
    } else {
        // No tables selected, redirect to the welcome page
        header("Location: welcome.php");
        exit();
    }
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>
