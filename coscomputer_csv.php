<?php
// include the config file to get the $pdo object
require_once 'config.php';

// URL of the CSV file to download
$csv_url = '***';

// Credentials for authentication
$username = '***';
$password = '***';

// Create a stream context with the authentication headers
$context = stream_context_create([
    'http' => [
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
    ]
]);

// Get the contents of the CSV file with authentication
$csv_contents = file_get_contents($csv_url, false, $context);

// Columns to keep and their new names
$columns_to_keep = array(
    'Lieferantenname' => 'Lieferantenname',
    'sku' => 'Herstellerartikelnummer', 
    'ean' => 'EAN',
    'msku' => 'Artikelnummer',
    'title' => 'Titel', 
    'price' => 'Preis', 
    'Verfuegbar' => 'Verfuegbar',
    'Verfuegbar_Tage' => 'Verfuegbar_Tage',
    'stock' => 'Lieferantenbestand',
    'Streckgengeschaeft' => 'Streckgengeschaeft',
);

// Get the contents of the CSV file
$handle = fopen("data://text/plain;base64," . base64_encode($csv_contents), 'r');
$csv_contents = '';
$row = 0;
$indexes_to_keep = array();
while (($data = fgetcsv($handle, 1000, ';')) !== false) {
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
                $value = 'COS Computer GmbH';
            } elseif ($column == 'Verfuegbar' || $column == 'Verfuegbar_Tage' || $column == 'Streckgengeschaeft') {
                $value = '0';
            } elseif ($column == 'ean') {
                // Check if the ean value is empty
                if (empty($value)) {
                    // Replace with the value of the msku column
                    $herstnr_index = $indexes_to_keep['msku'];
                    $value = $data[$herstnr_index];
                } else {
                    // Check if the ean value is float
                    if (is_numeric($value) && strpos($value, '.') !== false) {
                        // Convert float to int
                        $value = intval($value);
                    }
                }
            } elseif ($column == 'price') {
                // Check if the value of price column is comma separated
                if (strpos($value, ',') !== false) {
                    // Replace commas with points
                    $value = str_replace(',', '.', $value);
                }
            }
            $data_to_keep[] = $value;
        }
        // Concatenate the columns into a new CSV string
        $csv_contents .= implode('|', $data_to_keep) . "\n";

        // this will empty / truncate the table "coscomputer"
        $sql = "TRUNCATE TABLE `coscomputer`";
        // insert data into the database
        $stmt = $pdo->prepare("INSERT INTO coscomputer (Lieferantenname, Herstellerartikelnummer, EAN, Artikelnummer, Titel, Preis, Verfuegbar, Verfuegbar_Tage, Lieferantenbestand, Streckgengeschaeft) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($data_to_keep);
    }
    $row++;
}
fclose($handle);

// Save the modified CSV file to a new file
$filename = 'coscomputer_csv.csv';
file_put_contents($filename, $csv_contents);

// Force download of the modified CSV file
header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="'.$filename.'"');
readfile($filename);
