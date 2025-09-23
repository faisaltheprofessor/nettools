import confettiModule from "canvas-confetti";

var count = 200;
var defaults = {
    origin: { y: 0.7 }
}

function fire(particleRatio, opts) {
    confettiModule({
        ...defaults,
        ...opts,
        particleCount: Math.floor(count * particleRatio)
    });
}

window.confetti =  () => {
    fire(0.25, {
        spread: 26,
        startVelocity: 55,
    });
    fire(0.2, {
        spread: 60,
    });
    fire(0.35, {
        spread: 100,
        decay: 0.91,
        scalar: 0.8
    });
    fire(0.1, {
        spread: 120,
        startVelocity: 25,
        decay: 0.92,
        scalar: 1.2
    });
    fire(0.1, {
        spread: 120,
        startVelocity: 45,
    });
}

window._rowsForExport = (headEl, bodyEl) => {
    const headRow = headEl?.querySelector('tr');
    const head = headRow ? _cellsWithColspan(headRow).map(_clean) : [];
    const body = _rows(bodyEl).map(r => _cellsWithColspan(r).map(_clean));
    return [head, ...body];
}

window._escapeHtml = (s) => {
    return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

window._rowsToExcelHTML = (rows) => {
    const thead = rows.length ? `<tr>${rows[0].map(c => `<th style="text-align:left;">${_escapeHtml(c)}</th>`).join('')}</tr>` : '';
    const bodyRows = rows.slice(1).map(r =>
        `<tr>${r.map(c => {
            // preserve newlines inside cells for Excel by using <br>
            const html = _escapeHtml(c).replace(/\n/g, '<br>');
            return `<td style="mso-number-format:'\\@';">${html}</td>`;
        }).join('')}</tr>`
    ).join('');

    // Minimal Excel-friendly HTML
    return `
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
<x:Name>Export</x:Name>
<x:WorksheetOptions><x:Print><x:ValidPrinterInfo/></x:Print></x:WorksheetOptions>
</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
</head>
<body>
<table border="1" cellspacing="0" cellpadding="3">
<thead>${thead}</thead>
<tbody>${bodyRows}</tbody>
</table>
</body>
</html>`;
}

window._downloadExcelFromRows = (rows, filename = 'export.xls') => {
    const html = _rowsToExcelHTML(rows);
    const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => {
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }, 0);
}

