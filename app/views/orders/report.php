<?php
$date = $date ?? date('Y-m-d');
$contacts = $contacts ?? [];
$dateFormatted = date('d/m/Y', strtotime($date));
$dayNames = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
$dayOfWeek = $dayNames[(int)date('w', strtotime($date))];
$prioLabels = ['urgente'=>'URGENTE','alta'=>'Alta','normal'=>'Normal','baixa'=>'Baixa'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Contatos - <?= $dateFormatted ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            font-size: 12px; 
            color: #333; 
            padding: 20px;
            background: #fff;
        }
        
        .header { 
            border-bottom: 3px solid #333; 
            padding-bottom: 15px; 
            margin-bottom: 20px; 
        }
        .header h1 { 
            font-size: 20px; 
            margin-bottom: 5px;
        }
        .header .subtitle { 
            font-size: 14px; 
            color: #666; 
        }
        .header .date-info {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
        }
        .header .company-info {
            float: right;
            text-align: right;
            font-size: 11px;
            color: #666;
        }
        
        .summary {
            background: #f5f5f5;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            gap: 30px;
        }
        .summary-item {
            text-align: center;
        }
        .summary-item .number {
            font-size: 22px;
            font-weight: bold;
            color: #333;
        }
        .summary-item .label {
            font-size: 10px;
            color: #888;
            text-transform: uppercase;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
        }
        th { 
            background: #333; 
            color: #fff; 
            padding: 8px 10px; 
            text-align: left; 
            font-size: 11px; 
            text-transform: uppercase;
        }
        td { 
            padding: 8px 10px; 
            border-bottom: 1px solid #ddd; 
            vertical-align: top;
        }
        tr:nth-child(even) { background: #f9f9f9; }
        
        .priority-urgente { 
            background: #e74c3c; 
            color: #fff; 
            padding: 2px 8px; 
            border-radius: 10px; 
            font-size: 10px; 
            font-weight: bold;
        }
        .priority-alta { 
            background: #f39c12; 
            color: #fff; 
            padding: 2px 8px; 
            border-radius: 10px; 
            font-size: 10px; 
        }
        .priority-normal { 
            background: #3498db; 
            color: #fff; 
            padding: 2px 8px; 
            border-radius: 10px; 
            font-size: 10px; 
        }
        .priority-baixa { 
            background: #95a5a6; 
            color: #fff; 
            padding: 2px 8px; 
            border-radius: 10px; 
            font-size: 10px; 
        }

        .contact-notes {
            font-size: 11px;
            color: #555;
            margin-top: 3px;
            font-style: italic;
        }
        
        .contact-detail {
            font-size: 11px;
            color: #666;
        }

        .footer { 
            border-top: 2px solid #333; 
            padding-top: 10px; 
            margin-top: 30px; 
            font-size: 10px; 
            color: #999; 
            display: flex;
            justify-content: space-between;
        }

        .no-contacts {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 16px;
        }

        .checkbox-col {
            width: 30px;
            text-align: center;
        }
        .checkbox-col input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }

        .print-actions {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: #e8f4fd;
            border-radius: 5px;
        }
        .print-actions button {
            padding: 8px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-print { background: #3498db; color: #fff; }
        .btn-close-report { background: #95a5a6; color: #fff; }

        @media print {
            .print-actions { display: none !important; }
            body { padding: 10px; }
        }
    </style>
</head>
<body>
    <!-- Botões de ação (não aparecem na impressão) -->
    <div class="print-actions">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
        <button class="btn-close-report" onclick="window.close()">✖ Fechar</button>
    </div>

    <!-- Cabeçalho -->
    <div class="header" style="overflow:hidden;">
        <div class="company-info">
            Sistema de Gestão - Gráfica<br>
            Relatório gerado em: <?= date('d/m/Y H:i') ?>
        </div>
        <h1>📋 Relatório de Contatos</h1>
        <div class="subtitle"><?= $dayOfWeek ?></div>
        <div class="date-info"><?= $dateFormatted ?></div>
    </div>

    <?php if (empty($contacts)): ?>
        <div class="no-contacts">
            <p>📭 Nenhum contato agendado para esta data.</p>
        </div>
    <?php else: ?>
        <!-- Resumo -->
        <?php
        $totalContacts = count($contacts);
        $urgentes = count(array_filter($contacts, fn($c) => $c['priority'] === 'urgente'));
        $altas = count(array_filter($contacts, fn($c) => $c['priority'] === 'alta'));
        ?>
        <div class="summary">
            <div class="summary-item">
                <div class="number"><?= $totalContacts ?></div>
                <div class="label">Total de Contatos</div>
            </div>
            <div class="summary-item">
                <div class="number" style="color:#e74c3c;"><?= $urgentes ?></div>
                <div class="label">Urgentes</div>
            </div>
            <div class="summary-item">
                <div class="number" style="color:#f39c12;"><?= $altas ?></div>
                <div class="label">Alta Prioridade</div>
            </div>
        </div>

        <!-- Tabela de Contatos -->
        <table>
            <thead>
                <tr>
                    <th class="checkbox-col">✓</th>
                    <th style="width:70px;">Pedido</th>
                    <th>Cliente</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th style="width:80px;">Prioridade</th>
                    <th>Observações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $c): ?>
                <tr>
                    <td class="checkbox-col"><input type="checkbox"></td>
                    <td><strong>#<?= str_pad($c['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                    <td>
                        <strong><?= htmlspecialchars($c['customer_name']) ?></strong>
                        <?php if (!empty($c['customer_document'])): ?>
                            <div class="contact-detail">Doc: <?= htmlspecialchars($c['customer_document']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($c['customer_address'])): ?>
                            <?php $fmtAddr = \Akti\Models\CompanySettings::formatCustomerAddress($c['customer_address']); ?>
                            <?php if ($fmtAddr): ?>
                            <div class="contact-detail">📍 <?= htmlspecialchars(mb_substr($fmtAddr, 0, 50)) ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($c['customer_phone'] ?? '—') ?></td>
                    <td class="contact-detail"><?= htmlspecialchars($c['customer_email'] ?? '—') ?></td>
                    <td>
                        <span class="priority-<?= $c['priority'] ?>">
                            <?= $prioLabels[$c['priority']] ?? 'Normal' ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($c['notes'])): ?>
                            <div class="contact-notes"><?= nl2br(htmlspecialchars($c['notes'])) ?></div>
                        <?php else: ?>
                            <span style="color:#ccc;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Espaço para anotações -->
        <div style="margin-top:30px;">
            <strong>Anotações:</strong>
            <div style="border: 1px solid #ddd; min-height: 100px; border-radius: 5px; margin-top: 5px; padding: 10px;">
                &nbsp;
            </div>
        </div>
    <?php endif; ?>

    <!-- Rodapé -->
    <div class="footer">
        <span>Sistema de Gestão - Gráfica | Relatório de Contatos</span>
        <span><?= $dateFormatted ?> | Gerado por: <?= $_SESSION['user_name'] ?? 'Sistema' ?></span>
    </div>
</body>
</html>
