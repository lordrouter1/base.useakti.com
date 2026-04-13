<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro — Akti</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1e293b">
    <link rel="icon" type="image/x-icon" href="assets/logos/akti-icon-dark.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .error-container {
            max-width: 720px;
            width: 100%;
        }
        .error-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .error-icon {
            width: 48px; height: 48px;
            background: #7f1d1d;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .error-icon svg { width: 24px; height: 24px; color: #fca5a5; }
        .error-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fca5a5;
        }
        .error-header p {
            font-size: 0.82rem;
            color: #94a3b8;
            margin-top: 0.15rem;
        }
        .error-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .error-card-header {
            padding: 0.75rem 1.25rem;
            background: #1a2332;
            border-bottom: 1px solid #334155;
            font-size: 0.78rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .error-card-body {
            padding: 1.25rem;
        }
        .error-message {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.88rem;
            line-height: 1.7;
            color: #fbbf24;
            word-break: break-word;
        }
        .error-meta {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.4rem 1rem;
            font-size: 0.82rem;
            margin-top: 1rem;
        }
        .error-meta dt {
            color: #64748b;
            font-weight: 500;
        }
        .error-meta dd {
            font-family: 'JetBrains Mono', monospace;
            color: #cbd5e1;
            font-size: 0.8rem;
        }
        .trace-list {
            list-style: none;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            line-height: 1.8;
            color: #94a3b8;
            max-height: 320px;
            overflow-y: auto;
        }
        .trace-list li {
            padding: 0.25rem 0;
            border-bottom: 1px solid #1e293b;
        }
        .trace-list li:last-child { border-bottom: none; }
        .trace-num {
            color: #475569;
            display: inline-block;
            min-width: 2rem;
            text-align: right;
            margin-right: 0.75rem;
        }
        .trace-file { color: #7dd3fc; }
        .trace-line { color: #fbbf24; }
        .trace-func { color: #c4b5fd; }
        .error-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid #334155;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            background: #1e293b;
            color: #e2e8f0;
        }
        .btn:hover { background: #334155; border-color: #475569; }
        .btn-primary { background: #3b82f6; border-color: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; border-color: #2563eb; }
        .error-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.75rem;
            color: #475569;
        }
        .error-footer img { height: 16px; opacity: 0.3; vertical-align: middle; margin-right: 0.25rem; }
        .toggle-trace { cursor: pointer; user-select: none; }
        .toggle-trace::after { content: ' ▸'; font-size: 0.7rem; }
        .toggle-trace.open::after { content: ' ▾'; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div>
                <h1>Erro Interno do Servidor</h1>
                <p><?= date('d/m/Y H:i:s') ?> — HTTP 500</p>
            </div>
        </div>

        <?php
        // Dados do erro: passados via $errorException, $errorData ou extraídos do contexto
        $msg   = '';
        $file  = '';
        $line  = '';
        $trace = '';

        if (isset($errorException) && $errorException instanceof \Throwable) {
            $msg   = $errorException->getMessage();
            $file  = $errorException->getFile();
            $line  = $errorException->getLine();
            $trace = $errorException->getTraceAsString();
        } elseif (isset($errorData) && is_array($errorData)) {
            $msg   = $errorData['message'] ?? '';
            $file  = $errorData['file'] ?? '';
            $line  = $errorData['line'] ?? '';
            $trace = $errorData['trace'] ?? '';
        }
        ?>

        <?php if ($msg): ?>
        <div class="error-card">
            <div class="error-card-header">Mensagem</div>
            <div class="error-card-body">
                <div class="error-message"><?= htmlspecialchars($msg) ?></div>
                <?php if ($file): ?>
                <dl class="error-meta">
                    <dt>Arquivo</dt>
                    <dd><?= htmlspecialchars($file) ?></dd>
                    <?php if ($line): ?>
                    <dt>Linha</dt>
                    <dd><?= htmlspecialchars((string) $line) ?></dd>
                    <?php endif; ?>
                </dl>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="error-card">
            <div class="error-card-body">
                <div class="error-message">Ocorreu um erro inesperado. Verifique os logs em <code>storage/logs/</code> para mais detalhes.</div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($trace): ?>
        <div class="error-card">
            <div class="error-card-header toggle-trace" onclick="this.classList.toggle('open');this.nextElementSibling.style.display=this.classList.contains('open')?'block':'none'">
                Stack Trace
            </div>
            <div class="error-card-body" style="display:none">
                <ol class="trace-list">
                    <?php foreach (explode("\n", $trace) as $i => $frame):
                        $frame = trim($frame);
                        if ($frame === '') continue;
                        // Colorir partes do trace
                        $colored = htmlspecialchars($frame);
                        $colored = preg_replace('/^(#\d+)\s/', '<span class="trace-num">$1</span>', $colored);
                        $colored = preg_replace('/([\w\\\\\/\.]+\.php)/', '<span class="trace-file">$1</span>', $colored);
                        $colored = preg_replace('/\((\d+)\)/', '(<span class="trace-line">$1</span>)', $colored);
                        $colored = preg_replace('/([\w\\\\]+)->([\w]+)\(/', '<span class="trace-func">$1->$2</span>(', $colored);
                        $colored = preg_replace('/([\w\\\\]+)::([\w]+)\(/', '<span class="trace-func">$1::$2</span>(', $colored);
                    ?>
                    <li><?= $colored ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <?php endif; ?>

        <div class="error-actions">
            <a href="?" class="btn btn-primary">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Ir ao Início
            </a>
            <a href="javascript:location.reload()" class="btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
                Tentar Novamente
            </a>
        </div>

        <div class="error-footer">
            <img src="assets/logos/akti-logo-dark.svg" alt="Akti">
            Akti — Gestão em Produção
        </div>
    </div>
</body>
</html>
