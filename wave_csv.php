<?php
// include the config file to get the $pdo object
require_once 'config.php';

// Set the URL and authentication credentials
$url = '***';
$username = '***';
$password = '***';

// Create a stream context with the authentication credentials
$opts = array(
  'http'=>array(
    'method'=>"GET",
    'header' => "Authorization: Basic " . base64_encode("$username:$password")                 
  )
);
$context = stream_context_create($opts);

// Columns to keep and their new names
$columns_to_keep = array(
    'Lieferantenname' => 'Lieferantenname',
    'Manufacturer Id' => 'Herstellerartikelnummer',
    'EAN' => 'EAN',
    'ArtNo' => 'Artikelnummer',
    'Name' => 'Titel',
    'Price (EUR)' => 'Preis',
    'Verfuegbar' => 'Verfuegbar',
    'Verfuegbar_Tage' => 'Verfuegbar_Tage',
    'Lieferantenbestand' => 'Lieferantenbestand',
    'Streckgengeschaeft' => 'Streckgengeschaeft',
);

// Get the contents of the CSV file from the URL
$zip_contents = file_get_contents($url, false, $context);

// Save the zip file
$zip_filename = 'export.zip';
file_put_contents($zip_filename, $zip_contents);

// Extract the CSV file from the zip file
$zip = new ZipArchive;
if ($zip->open($zip_filename) === TRUE) {
    $csv_filename = $zip->getNameIndex(0);
    $zip->extractTo('./');
    $zip->close();
} else {
    echo 'Failed to extract zip file';
    exit();
}

// Open the extracted CSV file
// Get the contents of the CSV file
$handle = fopen($csv_filename, 'r');
$csv_contents = '';
$row = 0;
$indexes_to_keep = array();
while (($data = fgetcsv($handle, 1000, "\t")) !== false) {
    if ($row == 0) {
        // Save the indexes of the columns we want to keep
        foreach ($columns_to_keep as $column => $new_name) {
            $indexes_to_keep[$column] = array_search($column, $data);
        }
        // Generate the first line of the new CSV file
        $new_column_names = array_values($columns_to_keep);
        //$new_column_names[] = 'Lieferantenbestand'; // add new column name
        $csv_contents .= implode('|', $new_column_names) . "\n";
    } else {
        // Extract the columns we want to keep and rename them
        $data_to_keep = array();
        foreach ($columns_to_keep as $column => $new_name) {
            $index = $indexes_to_keep[$column];
            $value = $data[$index];
            if ($column == 'Lieferantenname') {
                $value = 'WAVE Distribution & Computersysteme GmbH';
            } elseif ($column == 'Lieferantenbestand') {
                $value = ''; // set new column value to empty
            } elseif ($column == 'Verfuegbar' || $column == 'Verfuegbar_Tage' || $column == 'Streckgengeschaeft') {
                $value = '0';
            } elseif ($column == 'EAN') {
                // Check if the ean value is empty
                if (empty($value)) {
                    // Replace with the value of the Manufacturer Id column
                    $herstnr_index = $indexes_to_keep['Manufacturer Id'];
                    $value = $data[$herstnr_index];
                } else {
                    // Check if the ean value is float
                    if (is_numeric($value) && strpos($value, '.') !== false) {
                        // Convert float to int
                        $value = intval($value);
                    }
                }
            } elseif ($column == 'Price (EUR)') {
                // Check if the value of Price (EUR) column is comma separated
                if (strpos($value, ',') !== false) {
                    // Replace commas with points
                    $value = str_replace(',', '.', $value);
                }
            }
            $data_to_keep[] = $value;
        }
        // Add the row of data to the new CSV file
        $csv_contents .= implode('|', $data_to_keep) . "\n";

        // this will empty / truncate the table "wave"
        $sql = "TRUNCATE TABLE `wave`";
        // insert data into the database
        $stmt = $pdo->prepare("INSERT INTO wave (Lieferantenname, Herstellerartikelnummer, EAN, Artikelnummer, Titel, Preis, Verfuegbar, Verfuegbar_Tage, Lieferantenbestand, Streckgengeschaeft) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($data_to_keep);
    }
    $row++;
}
fclose($handle);

// Save the modified CSV file to a new file
$filename = 'wave_csv.csv';
file_put_contents($filename, $csv_contents);

// Force download of the modified CSV file
header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="'.$filename.'"');
readfile($filename);
