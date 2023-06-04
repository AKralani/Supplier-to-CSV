<?php error_reporting (E_ALL ^ E_NOTICE); ?>
<?php
// include the config file to get the $pdo object
require_once 'config.php';

// URL of the CSV file to download
$csv_url = '***';

// Get the contents of the CSV file
$csv_contents = file_get_contents($csv_url);

// Columns to keep and their new names
$columns_to_keep = array(
    'Lieferantenname' => 'Lieferantenname',
    'ManufacturerID' => 'ManufacturerID',
    'EAN' => 'EAN',
    'ProductID' => 'ProductID', 
    'Description1' => 'Description1',
    'NetPrice' => 'NetPrice', 
    'Verfuegbar' => 'Verfuegbar',
    'Verfuegbar_Tage' => 'Verfuegbar_Tage',
    'AvailableQuantity' => 'AvailableQuantity',
    'Streckgengeschaeft' => 'Streckgengeschaeft',
);

// Get the contents of the CSV file
$handle = fopen($csv_url, 'r');
$csv_contents = '';
$row = 0;
$indexes_to_keep = array();
while (($data = fgetcsv($handle, 10000, ';')) !== false) {
    if ($row == 0) {
        // Save the indexes of the columns we want to keep
        foreach ($columns_to_keep as $column => $new_name) {
            $indexes_to_keep[$column] = array_search($column, $data);
        }
        // Generate the first line of the new CSV file
        $new_column_names = array_keys($columns_to_keep);
        $csv_contents .= implode('|', $new_column_names) . "\n";

        // Generate the desired first line
        $new_first_line = "Lieferantenname|Herstellerartikelnummer|EAN|Artikelnummer|Titel|Preis|Verfuegbar|Verfuegbar_Tage|Lieferantenbestand|Streckgengeschaeft\n";
        $csv_contents = preg_replace('/^.+\n/', $new_first_line, $csv_contents, 1);
    } else {
        // Extract the columns we want to keep and rename them
        $data_to_keep = array();
        foreach ($columns_to_keep as $column => $new_name) {
            $index = $indexes_to_keep[$column];
            $value = $data[$index];
            if ($column == 'Lieferantenname') {
                $value = 'Siewert & Kau';
            } elseif ($column == 'Verfuegbar' || $column == 'Verfuegbar_Tage' || $column == 'Streckgengeschaeft') {
                $value = '0';
            } elseif ($column == 'EAN') {
                // Check if the EAN value is empty
                if (empty($value)) {
                    // Replace with the value of the ProductID column
                    $herstnr_index = $indexes_to_keep['ProductID'];
                    $value = $data[$herstnr_index];
                } else {
                    // Check if the EAN value is float
                    if (is_numeric($value) && strpos($value, '.') !== false) {
                        // Convert float to int
                        $value = intval($value);
                    }
                }
            } elseif ($column == 'NetPrice') {
                // Check if the value of NetPrice column is comma separated
                if (strpos($value, ',') !== false) {
                    // Replace commas with points
                    $value = str_replace(',', '.', $value);
                }
            }
            $data_to_keep[] = $value;
        }
        // Concatenate the columns into a new CSV string
        $csv_contents .= implode('|', $data_to_keep) . "\n";

        // this will empty / truncate the table "siewert"
        $sql = "TRUNCATE TABLE `siewert`";
        // insert data into the database
        $stmt = $pdo->prepare("INSERT INTO siewert (Lieferantenname, Herstellerartikelnummer, EAN, Artikelnummer, Titel, Preis, Verfuegbar, Verfuegbar_Tage, Lieferantenbestand, Streckgengeschaeft) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($data_to_keep);
    }
    $row++;
}
fclose($handle);

// Save the modified CSV file to a new file
$filename = 'siewert_csv.csv';
file_put_contents($filename, $csv_contents);

// Force download of the modified CSV file
header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="'.$filename.'"');
readfile($filename);
