/**
 * supply-movements.js — Movimentações de Insumos
 * Extraído de app/views/supply_stock/movements.php (FE-003)
 */
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const typeConfig = {
        entrada:       { label: 'Entrada',       icon: 'fa-arrow-down', color: 'success', sign: '+' },
        saida:         { label: 'Saída',         icon: 'fa-arrow-up',   color: 'danger',  sign: '-' },
        ajuste:        { label: 'Ajuste',        icon: 'fa-sliders-h',  color: 'warning', sign: '' },
        transferencia: { label: 'Transferência', icon: 'fa-exchange-alt', color: 'info', sign: '' }
    };

    // Select2 para insumo
    $('#fSupply').select2({
        ajax: {
            url: '?page=supply_stock&action=searchSupplies',
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results || [] })
        },
        placeholder: 'Todos',
        minimumInputLength: 1,
        allowClear: true,
        width: '100%'
    }).on('change', () => loadMovements(1));

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    let _movAbort = null;
    function loadMovements(page) {
        if (_movAbort) _movAbort.abort();
        _movAbort = new AbortController();
        page = page || 1;
        const params = new URLSearchParams({
            page: 'supply_stock',
            action: 'movements',
            format: 'json',
            pg: page,
            warehouse_id: document.getElementById('fWarehouse').value,
            supply_id: $('#fSupply').val() || '',
            type: document.getElementById('fType').value,
            date_from: document.getElementById('fDateFrom').value,
            date_to: document.getElementById('fDateTo').value
        });

        fetch('?' + params, { headers: { 'X-CSRF-TOKEN': csrfToken }, signal: _movAbort.signal })
            .then(r => r.json())
            .then(data => {
                const tbody = document.getElementById('movTableBody');
                if (!data.success || !data.items.length) {
                    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Nenhuma movimentação encontrada.</td></tr>';
                    document.getElementById('movPaginationInfo').textContent = '';
                    document.getElementById('movPagination').innerHTML = '';
                    return;
                }

                let html = '';
                data.items.forEach(item => {
                    const cfg = typeConfig[item.type] || { label: item.type, icon: 'fa-circle', color: 'secondary', sign: '' };
                    const qty = parseFloat(item.quantity);
                    const qtyStr = (qty >= 0 ? '+' : '') + qty.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    const qtyColor = qty >= 0 ? 'text-success' : 'text-danger';
                    const price = item.unit_price ? 'R$ ' + parseFloat(item.unit_price).toLocaleString('pt-BR', {minimumFractionDigits: 2}) : '—';
                    const dt = new Date(item.created_at).toLocaleString('pt-BR');

                    html += `<tr>
                        <td class="ps-4 text-muted">${escapeHtml(String(item.id))}</td>
                        <td><small>${dt}</small></td>
                        <td><span class="badge bg-${cfg.color}"><i class="fas ${cfg.icon} me-1"></i>${cfg.label}</span></td>
                        <td><code>${escapeHtml(item.supply_code || '')}</code> ${escapeHtml(item.supply_name || '')}</td>
                        <td class="text-center">${escapeHtml(item.warehouse_name || '—')}</td>
                        <td class="text-center">${item.batch_number ? escapeHtml(item.batch_number) : '—'}</td>
                        <td class="text-end fw-semibold ${qtyColor}">${qtyStr}</td>
                        <td class="text-end">${price}</td>
                        <td><small class="text-muted">${escapeHtml(item.reason || '—')}</small></td>
                    </tr>`;
                });
                tbody.innerHTML = html;

                const start = (data.page - 1) * data.per_page + 1;
                const end = Math.min(data.page * data.per_page, data.total);
                document.getElementById('movPaginationInfo').textContent = `${start}-${end} de ${data.total}`;

                let pagHtml = '';
                for (let p = 1; p <= data.total_pages; p++) {
                    pagHtml += `<li class="page-item ${p === data.page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="event.preventDefault(); loadMovements(${p})">${p}</a>
                    </li>`;
                }
                document.getElementById('movPagination').innerHTML = pagHtml;
            })
            .catch(err => {
                if (err.name === 'AbortError') return;
                document.getElementById('movTableBody').innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4">Erro ao carregar.</td></tr>';
            });
    }

    // Filtros
    document.querySelectorAll('.mov-filter').forEach(el => {
        el.addEventListener('change', () => loadMovements(1));
    });

    document.getElementById('btnClearFilters').addEventListener('click', function() {
        document.getElementById('fWarehouse').value = '';
        document.getElementById('fType').value = '';
        document.getElementById('fDateFrom').value = '';
        document.getElementById('fDateTo').value = '';
        $('#fSupply').val(null).trigger('change');
        loadMovements(1);
    });

    window.loadMovements = loadMovements;
    loadMovements(1);
});
