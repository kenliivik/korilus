<?php
// Creates the login and sync tables from schema.sql. Safe to run again. Run once after filling api/data/config.php:
//   php ~/htdocs/korilus/api/migrate.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/db.php';

$sql = preg_replace('/--.*$/m', '', file_get_contents(__DIR__ . '/schema.sql'));
foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) db()->exec($statement);
echo "OK: tabelid on olemas.\n";

// Copy review, 2026-10-13: berry tips that still have the old built-in wording get the new one.
// Only exact matches change, so tips edited in /admin stay as they are. Safe to run again.
$tips = [
    'Pärast jaanipäeva leiad neid peamiselt tuttavatest kohtadest' => 'Pärast jaanipäeva tasub minna oma vanadesse headesse kohtadesse',
    'Parimad paigad on 3–4 aasta vanused raiesmikud' => 'Kõige rohkem marju on 3–4 aastat vanadel raiesmikel',
    'Korja täiesti siniseid marju' => 'Korja ainult läbinisti siniseid marju',
    'Parim aeg on hommikul' => 'Kõige parem on korjata hommikul',
    'Küps mari on meekollane, mitte punane' => 'Valmis mari on meekollane ja pehme, punane on alles toores',
    'Igal varrel on üks mari, seega varu kannatust' => 'Igal taimel on vaid üks mari, nii et varu kannatust',
    'Ära korja liiga vara: järelvalminud marjad on vähem maitsvad' => 'Ära korja liiga vara, sest kodus järelvalminud marjad pole nii maitsvad',
    'Vali tugevad läikivad varred ilma pruunide laikudeta' => 'Vali tugevad läikivad varred, millel pole pruune laike',
    'Ära korja pärast jaanipäeva, siis on vartes palju oblikhapet' => 'Pärast jaanipäeva enam ei korjata, siis on vartes liiga palju oblikhapet',
    'Lehed ei sobi söögiks' => 'Lehti ära söö, need on mürgised',
    "Suveõuna (nt 'Valge klaar') koor muutub rohelisest kollakasvalgeks" => 'Suveõuna (näiteks valge klaari) koor muutub valmides rohelisest kollakasvalgeks',
    'Küps mari on peaaegu must, sinakashalli kirmega ja tuleb kergelt lahti' => 'Valmis mari on peaaegu must, sinakashalli kirmega ja tuleb kergesti lahti',
    'Korja käsitsi ja ettevaatlikult, sest küpsed marjad purunevad kergesti' => 'Korja hellalt, sest valmis marjad lähevad kergesti katki',
    'Marjad ei säili kaua, seega korja iga päev ja tarvita kiiresti' => 'Marjad ei säili kaua, nii et korja tihti ja söö või keeda kohe ära',
    'Küps mari on must ja läikiv' => 'Valmis mari on must ja läikiv',
    'Võid korjata üksikmarju või terveid kobaraid' => 'Korja kas marja kaupa või terve kobar korraga',
    'Õrna koorega sorte korja valmimise alguses, mõni sort pudeneb küpsena' => 'Õrna koorega sorte korja kohe valmimise alguses, sest mõni sort pudeneb valmides maha',
];
$content = load_content();
$changed = 0;
foreach ($content['berries'] as &$b) {
    foreach ($b['tips'] as &$tip) if (isset($tips[$tip])) { $tip = $tips[$tip]; $changed++; }
    unset($tip);
}
unset($b);
if ($changed) { $content['version']++; save_content($content); }
echo "OK: marjade nõuandeid uuendatud: $changed.\n";
