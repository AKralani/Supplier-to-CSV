<?php
// include the config file to get the $pdo object
require_once 'config.php';

// FTP server details
$ftp_server = 'ftp.***.***';
$ftp_username = '***';
$ftp_password = '***';
$remote_file = 'mediacom.csv';

// Download the CSV file using cURL
$ch = curl_init();
$fp = fopen('mediacom_csv.csv', 'w');
curl_setopt($ch, CURLOPT_URL, "ftp://$ftp_username:$ftp_password@$ftp_server/$remote_file");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FILE, $fp);
$result = curl_exec($ch);
curl_close($ch);
fclose($fp);

if ($result) {
    // Get the contents of the CSV file
    $csv_contents = file_get_contents('mediacom_csv.csv');

    // Columns to keep and their new names
    $columns_to_keep = array(
        'Lieferantenname' => 'Lieferantenname',
        'Hersteller-Nr.' => 'Hersteller-Nr.', 
        'EAN' => 'EAN',
        'Artikelnummer' => 'Artikelnummer',
        'Bezeichnung1' => 'Bezeichnung1',
        'Preis' => 'Preis',
        'Verfuegbar' => 'Verfuegbar',
        'Verfuegbar_Tage' => 'Verfuegbar_Tage',
        'Lagerbestand' => 'Lagerbestand',
        'Streckgengeschaeft' => 'Streckgengeschaeft',
    );

    // Get the columns indexes to keep
    $indexes_to_keep = array();
    $rows = explode("\n", $csv_contents);
    $header_row = str_getcsv($rows[0], ';');
    foreach ($columns_to_keep as $column => $new_name) {
        $indexes_to_keep[$column] = array_search($column, $header_row);
    }

    // Generate the first line of the new CSV file
    $new_column_names = array_keys($columns_to_keep);
    $csv_contents = implode('|', $new_column_names) . "\n";

    // Generate the first line of the new CSV file
    $new_column_names = array(
        'Lieferantenname',
        'Herstellerartikelnummer',
        'EAN',
        'Artikelnummer',
        'Titel',
        'Preis',
        'Verfuegbar',
        'Verfuegbar_Tage',
        'Lieferantenbestand',
        'Streckgengeschaeft'
    );
    $csv_contents = implode('|', $new_column_names) . "\n";

    // Loop through the rows and extract the columns we want to keep and rename them
    for ($i = 1; $i < count($rows) - 1; $i++) {
        $data = str_getcsv($rows[$i], ';');
        $data_to_keep = array();
        foreach ($columns_to_keep as $column => $new_name) {
            $index = $indexes_to_keep[$column];
            $value = isset($data[$index]) ? $data[$index] : ''; // Check if the index exists
            if ($column == 'Lieferantenname') {
                $value = 'MediaCom IT-Distribution GmbH';
            } elseif ($column == 'Verfuegbar' || $column == 'Verfuegbar_Tage' || $column == 'Streckgengeschaeft') {
                $value = '0';
            } elseif ($column == 'EAN') {
                // Check if the ean value is empty
                if (empty($value)) {
                    // Replace with the value of the herstnr column
                    $herstnr_index = $indexes_to_keep['Hersteller-Nr.'];
                    $value = isset($data[$herstnr_index]) ? $data[$herstnr_index] : ''; // Check if the index exists
                } else {
                    // Check if the ean value is float
                    if (is_numeric($value) && strpos($value, '.') !== false) {
                        // Convert float to int
                        $value = intval($value);
                    }
                }
            } elseif ($column == 'Preis') {
                // Check if the value of Preis column is comma separated
                if (strpos($value, ',') !== false) {
                    // Replace commas with points
                    $value = str_replace(',', '.', $value);
                }
            }
            $data_to_keep[] = $value;
        }
        // Concatenate the columns into a new CSV string
        $csv_contents .= implode('|', $data_to_keep) . "\n";

        // this will empty / truncate the table "mediacom"
        $sql = "TRUNCATE TABLE `mediacom`";
        // insert data into the database
        $stmt = $pdo->prepare("INSERT INTO mediacom (Lieferantenname, Herstellerartikelnummer, EAN, Artikelnummer, Titel, Preis, Verfuegbar, Verfuegbar_Tage, Lieferantenbestand, Streckgengeschaeft) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute($data_to_keep);
    }
         
    // Save the modified CSV file to a new file
    $filename = 'mediacom_csv.csv';
    file_put_contents($filename, $csv_contents);

    // Force download of the modified CSV file
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($filename);

    // Delete temporary file
    unlink($temp_file);
} else {
    die("Could not download file from server.");
}

// Close FTP connection
ftp_close($ftp_conn);
?>
                    