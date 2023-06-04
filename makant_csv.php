<?php error_reporting (E_ALL ^ E_NOTICE); ?>
<?php
// include the config file to get the $pdo object
require_once 'config.php';

// URL of the CSV file to download
// German
$csv_url = '***';

// Get the contents of the CSV file
$csv_contents = file_get_contents($csv_url);

// Columns to keep and their new names
$columns_to_keep = array(
    'Lieferantenname' => 'Lieferantenname',
    'products_mpn' => 'products_mpn', 
    'products_ean' => 'products_ean',
    'products_id' => 'products_id', 
    'product_name' => 'product_name', 
    'product_price' => 'product_price', 
    'Verfuegbar' => 'Verfuegbar',
    'Verfuegbar_Tage' => 'Verfuegbar_Tage',
    'products_quantity' => 'products_quantity', 
    'Streckgengeschaeft' => 'Streckgengeschaeft',
);

// Get the contents of the CSV file
$handle = fopen($csv_url, 'r');
$csv_contents = '';
$row = 0;
$indexes_to_keep = array();
while (($data = fgetcsv($handle, 20000, ';')) !== false) {
    if ($row == 0) {
        // Save the indexes of the columns we want to keep
        foreach ($columns_to_keep as $column => $new_name) {
            $indexes_to_keep[$column] = array_search($column, $data);
        }
        // Generate the first line of the new CSV file
        $new_column_names = array_keys($columns_to_keep);
        $csv_contents .= implode('|', $data) . "\n";

        // Generate the desired first line
        $new_first_line = "Lieferantenname|Herstellerartikelnummer|EAN|Artikelnummer|Titel|Preis|Verfuegbar|Verfuegbar_Tage|Lieferantenbestand|Streckgengeschaeft\n";
        $csv_contents = preg_replace('/^.+\n/', $new_first_line, $csv_contents, 1);
    } else {
        // Check if the desired first line has been generated before adding the remaining lines
        if (strpos($csv_contents, $new_first_line) !== false) {
            // Extract the columns we want to keep and rename them
            $data_to_keep = array();
            foreach ($columns_to_keep as $column => $new_name) {
                if (isset($indexes_to_keep[$column])) {
                    $index = $indexes_to_keep[$column];
                    $value = $data[$index];
                    if ($column == 'Lieferantenname') {
                        $value = 'MaKant Europe GmbH';
                    } elseif ($column == 'Verfuegbar' || $column == 'Verfuegbar_Tage' || $column == 'Streckgengeschaeft') {
                        $value = '0';
                    } elseif ($column == 'products_ean') {
                        // Check if the products_ean value is empty
                        if (empty($value)) {
                            // Replace with the value of the products_mpn column
                            $herstnr_index = $indexes_to_keep['products_mpn'];
                            $value = $data[$herstnr_index];
                        } else {
                            // Check if the products_ean value is float
                            if (is_numeric($value) && strpos($value, '.') !== false) {
                                // Convert float to int
                                $value = intval($value);
                            }
                        }
                    } elseif ($column == 'product_price') {
                        // Check if the value of product_price column is comma separated
                        if (strpos($value, ',') !== false) {
                            // Replace commas with points
                            $value = str_replace(',', '.', $value);
                        }
                    }
                    $data_to_keep[] = $value;
                }
            }
            // Concatenate the columns into a new CSV string
            $csv_contents .= implode('|', $data_to_keep) . "\n";

            // this will empty / truncate the table "makant"
            $sql = "TRUNCATE TABLE `makant`";
            // insert data into the database
            $stmt = $pdo->prepare("INSERT INTO makant (Lieferantenname, Herstellerartikelnummer, EAN, Artikelnummer, Titel, Preis, Verfuegbar, Verfuegbar_Tage, Lieferantenbestand, Streckgengeschaeft) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute($data_to_keep);
        }
    }
    $row++;
}
fclose($handle);

// Save the modified CSV file to a new file
$filename = 'makant_csv.csv';
file_put_contents($filename, $csv_contents);

// Force download of the modified CSV file
header('Content-Type: application/csv');
header('Content-Disposition: attachment; filename="'.$filename.'"');
readfile($filename);
