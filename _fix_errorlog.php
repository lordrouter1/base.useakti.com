<?php
$file = 'app/controllers/NfeDocumentController.php';
$c = file_get_contents($file);
$c = preg_replace(
    "/error_log\('\[NfeDocumentController\] (.+?) error: ' \. \\\$e->getMessage\(\)\)/",
    "Log::error('NfeDocumentController: \$1', ['exception' => \$e->getMessage()])",
    $c
);
file_put_contents($file, $c);
echo "Done. Replaced error_log calls.\n";
