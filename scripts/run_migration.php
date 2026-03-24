<?php
require 'app/config/database.php';
$db = (new Database())->getConnection();

// Check if the new config exists
$r = $db->query("SELECT * FROM comissao_config WHERE config_key = 'criterio_liberacao_comissao'");
$row = $r->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "Config 'criterio_liberacao_comissao' already exists: " . $row['config_value'] . PHP_EOL;
} else {
    echo "Config 'criterio_liberacao_comissao' NOT found. Running migration..." . PHP_EOL;
    $db->exec("INSERT INTO comissao_config (config_key, config_value, descricao) VALUES ('criterio_liberacao_comissao', 'pagamento_total', 'Criterio de liberacao: sem_confirmacao, primeira_parcela, pagamento_total')");
    echo "Config inserted." . PHP_EOL;
}

// Check if status ENUM has 'aguardando_pagamento'
$r2 = $db->query("SHOW COLUMNS FROM comissoes_registradas LIKE 'status'");
$col = $r2->fetch(PDO::FETCH_ASSOC);
echo "Status column type: " . ($col['Type'] ?? 'N/A') . PHP_EOL;

if (strpos($col['Type'], 'aguardando_pagamento') === false) {
    echo "Adding 'aguardando_pagamento' to status enum..." . PHP_EOL;
    $db->exec("ALTER TABLE comissoes_registradas MODIFY COLUMN status ENUM('calculada','aprovada','aguardando_pagamento','paga','cancelada') NOT NULL DEFAULT 'calculada'");
    echo "Done." . PHP_EOL;
} else {
    echo "'aguardando_pagamento' already in enum." . PHP_EOL;
}

echo "Migration check complete." . PHP_EOL;
