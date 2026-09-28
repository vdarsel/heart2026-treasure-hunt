<?php
// This file must sit in the SAME folder as paris-scavenger-hunt.html, on a
// PHP-capable web host (this will NOT work on a static-only host like
// GitHub Pages or plain Netlify — those cannot run PHP at all).
//
// The PIN below only ever runs on your server. The browser never receives
// this file's source, only the tiny {"success":true/false} answer — so
// unlike before, nobody can find the PIN by viewing the page source.

header('Content-Type: application/json');

// Change this to whatever PIN you want the organizers to use.
$correctPin = "2026";

$submitted = isset($_POST['pin']) ? (string)$_POST['pin'] : '';

echo json_encode([
    'success' => hash_equals($correctPin, $submitted)
]);
