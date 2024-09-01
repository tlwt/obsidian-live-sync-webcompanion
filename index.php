<?php

// Konfiguration laden
$config = require 'config.php';

// Funktion zur Verbindung mit CouchDB und Abrufen von Daten
function connectToCouchDB($config, $path, $postFields = null) {
    $url = "{$config['protocol']}://{$config['user']}:{$config['pass']}@{$config['host']}:{$config['port']}/{$config['dbname']}/$path";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($postFields) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        die("Verbindung zu CouchDB fehlgeschlagen.\n");
    }

    return json_decode($response, true);
}

// Funktion zum Filtern von Dokumenten nach "type", "deleted" und ID-Endung
function filterDocuments($documents) {
    $filteredDocs = [];
    $childrenIds = [];

    if (isset($documents['rows'])) {
        foreach ($documents['rows'] as $row) {
            if (isset($row['doc']['type']) && $row['doc']['type'] === 'plain' &&
                (!isset($row['doc']['deleted']) || $row['doc']['deleted'] !== true) &&
                substr($row['id'], -3) === '.md') {

                // Füge das Dokument zu den gefilterten hinzu
                $filteredDocs[] = $row['doc'];

                // Sammle die IDs der Children zusammen mit der Parent-ID
                if (isset($row['doc']['children'])) {
                    foreach ($row['doc']['children'] as $childId) {
                        $childrenIds[$childId] = $row['id'];
                    }
                }
            }
        }
    }

    return ['docs' => $filteredDocs, 'childrenIds' => $childrenIds];
}

// Funktion zur Filterung der Children-Dokumente nach offenen Aufgaben
function filterChildrenDocuments($childrenDocs) {
    $filteredChildrenDocs = [];

    foreach ($childrenDocs as $childId => $childDoc) {
        if (isset($childDoc['data'])) {
            // Filtere nur die Zeilen, die `- [ ]` enthalten
            $lines = explode("\n", $childDoc['data']);
            $filteredLines = array_filter($lines, function($line) {
                return strpos($line, '- [ ]') !== false;
            });
            
            if (!empty($filteredLines)) {
                $filteredChildrenDocs[$childId] = implode("\n", $filteredLines);
            }
        }
    }

    return $filteredChildrenDocs;
}

// Funktion zur Erzeugung des Obsidian-Links
function generateObsidianLink($parentId) {
    $encodedPath = str_replace(' ', '%20', $parentId); // Nur Leerzeichen kodieren
    $obsidianLink = "obsidian://open?vault=Obsidian&file=" . $encodedPath;
    return $obsidianLink;
}

// Funktion zur Ausgabe der gefilterten Children-Dokumente unter den entsprechenden Parent IDs
function displayFilteredChildrenGroupedByParent($filteredChildrenDocs, $childrenIds) {
    $groupedByParent = [];

    // Gruppiere die Children-Dokumente unter den entsprechenden Parent IDs
    foreach ($filteredChildrenDocs as $childId => $childData) {
        $parentId = $childrenIds[$childId];
        if (!isset($groupedByParent[$parentId])) {
            $groupedByParent[$parentId] = [];
        }
        $groupedByParent[$parentId][] = $childData;
    }

    // Ausgabe der gruppierten Daten
    if (count($groupedByParent) > 0) {
        echo "<ul>";
        foreach ($groupedByParent as $parentId => $childrenData) {
            // Erzeuge den Obsidian-Link
            $obsidianLink = generateObsidianLink($parentId);

            echo "<li><a href=\"{$obsidianLink}\">{$parentId}</a><ul>";
            foreach ($childrenData as $childData) {
                echo "<li><pre>" . htmlspecialchars($childData) . "</pre></li>";
            }
            echo "</ul></li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Keine offenen Aufgaben gefunden.</p>";
    }
}

// Optional: Limit für die Abfrage setzen
$limit = isset($config['limit']) ? intval($config['limit']) : 100;

// Verbindung herstellen und alle Dokumente abrufen
$documents = connectToCouchDB($config, "_all_docs?include_docs=true&limit={$limit}");

// Filtern der Dokumente und Sammeln der Children-IDs
$result = filterDocuments($documents);
$filteredDocs = $result['docs'];
$childrenIds = $result['childrenIds'];

// Abrufen der Children-Dokumente
$childrenDocs = [];
if (count($childrenIds) > 0) {
    $postFields = ['keys' => array_keys($childrenIds)];
    $childrenResponse = connectToCouchDB($config, "_all_docs?include_docs=true", $postFields);
    if (isset($childrenResponse['rows'])) {
        foreach ($childrenResponse['rows'] as $childRow) {
            if (isset($childRow['doc'])) {
                $childrenDocs[$childRow['id']] = $childRow['doc'];
            }
        }
    }
}

// Filtern der Children-Dokumente nach offenen Aufgaben
$filteredChildrenDocs = filterChildrenDocuments($childrenDocs);

// Ausgabe der gefilterten Children-Dokumente gruppiert nach Parent IDs
displayFilteredChildrenGroupedByParent($filteredChildrenDocs, $childrenIds);

?>