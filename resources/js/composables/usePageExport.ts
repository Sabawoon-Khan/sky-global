import { useNotify } from '@/composables/useNotify';
import { useTranslations } from '@/composables/useTranslations';

export type ExportSheet = {
    name: string;
    rows: string[][];
};

const ACTION_HEADERS = /^(actions|عملیات|کړنې)$/i;
const IGNORE_TABLE_IN =
    '[role="dialog"], [data-slot="dialog-content"], [data-export-ignore]';

type ExportLabels = {
    summary: string;
    metric: string;
    value: string;
    generated: string;
};

function xmlEscape(value: string): string {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&apos;');
}

function cellText(el: Element | null | undefined): string {
    if (!el) {
        return '';
    }

    const clone = el.cloneNode(true);

    if (clone instanceof Element) {
        clone
            .querySelectorAll('svg, [data-slot="dropdown-menu-trigger"]')
            .forEach((node) => node.remove());
    }

    return (clone.textContent ?? '').replace(/\s+/g, ' ').trim();
}

function isDisplayed(el: Element): boolean {
    if (!(el instanceof HTMLElement)) {
        return true;
    }

    if (el.hidden) {
        return false;
    }

    const style = window.getComputedStyle(el);

    return style.display !== 'none' && style.visibility !== 'hidden';
}

function sanitizeSheetName(name: string, used: Set<string>): string {
    const cleaned =
        name.replace(/[\\/*?:[\]]/g, ' ').replace(/\s+/g, ' ').trim() || 'Sheet';
    const base = cleaned.slice(0, 31);
    let candidate = base;
    let index = 2;

    while (used.has(candidate.toLowerCase())) {
        const suffix = ` ${index}`;
        candidate = `${base.slice(0, Math.max(1, 31 - suffix.length))}${suffix}`;
        index += 1;
    }

    used.add(candidate.toLowerCase());

    return candidate;
}

function slugify(value: string): string {
    const slug = value
        .toLowerCase()
        .replace(/[^\p{L}\p{N}]+/gu, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 60);

    return slug || 'export';
}

function pageTitle(): string {
    const heading = document.querySelector(
        '[data-export-root] h1, .hero-copy h1, .v2-form-page-copy h1, .sheet-title, h1',
    );

    return cellText(heading) || document.title.replace(/\s+-.+$/, '') || 'export';
}

function exportRoot(): ParentNode {
    return (
        document.querySelector('[data-export-root]') ??
        document.getElementById('main-content') ??
        document.body
    );
}

function columnShouldSkip(header: string, sample: Element | undefined): boolean {
    if (ACTION_HEADERS.test(header)) {
        return true;
    }

    if (!sample) {
        return false;
    }

    const hasMenu = Boolean(
        sample.querySelector(
            '[data-slot="dropdown-menu-trigger"], [role="menu"]',
        ),
    );

    return hasMenu && cellText(sample).length < 2;
}

function tableCells(row: HTMLTableRowElement): HTMLTableCellElement[] {
    return [...row.children].filter(
        (cell): cell is HTMLTableCellElement => cell instanceof HTMLTableCellElement,
    );
}

function tableToRows(table: HTMLTableElement): string[][] {
    const headerRow = table.tHead?.rows[0] ?? table.rows[0];

    if (!headerRow) {
        return [];
    }

    const headerCells = tableCells(headerRow);
    const firstBodyRow = table.tBodies[0]?.rows[0];
    const skip = headerCells.map(
        (cell, index) =>
            !isDisplayed(cell) ||
            columnShouldSkip(cellText(cell), firstBodyRow?.children[index]),
    );
    const visibleHeaders = headerCells
        .map((cell, index) => (skip[index] ? null : cellText(cell)))
        .filter((header): header is string => header !== null);

    if (visibleHeaders.length === 0) {
        return [];
    }

    const rows: string[][] = [visibleHeaders];
    const dataRows = [
        ...[...table.tBodies].flatMap((body) => [...body.rows]),
        ...(table.tFoot ? [...table.tFoot.rows] : []),
    ];

    for (const row of dataRows) {
        if (row === headerRow || !isDisplayed(row)) {
            continue;
        }

        const cells = tableCells(row);

        if (cells.length === 1 && cells[0].colSpan > 1) {
            continue;
        }

        const values = cells
            .map((cell, index) => ({
                skip: skip[index] ?? false,
                visible: isDisplayed(cell),
                text: cellText(cell),
            }))
            .filter((cell) => !cell.skip && cell.visible)
            .map((cell) => cell.text);

        if (values.length > 0 && values.some((value) => value !== '')) {
            rows.push(values);
        }
    }

    return rows.length > 1 ? rows : [];
}

function nearestTitle(el: Element, fallback: string): string {
    const panel = el.closest(
        'section, article, [data-slot="card"], .table-panel, .v2-panel, .sheet-section',
    );
    const heading = panel?.querySelector(
        'h1, h2, h3, [data-slot="card-title"], .card-title, .section-title',
    );

    return cellText(heading) || fallback;
}

function extractStatRows(root: ParentNode, labels: ExportLabels): string[][] {
    const cards = [...root.querySelectorAll('.stat-card')].filter(isDisplayed);
    const rows: string[][] = [[labels.metric, labels.value]];

    for (const card of cards) {
        const title = cellText(card.querySelector('span'));
        const value = cellText(card.querySelector('strong'));

        if (title || value) {
            rows.push([title, value]);
        }
    }

    return rows.length > 1 ? rows : [];
}

function isDayCalendarTable(table: HTMLTableElement): boolean {
    const rows = table.tHead ? [...table.tHead.rows] : [...table.rows].slice(0, 2);

    return rows.some((row) => {
        const labels = tableCells(row).map((cell) => cellText(cell));

        return labels.filter((header) => /^\d{1,2}$/.test(header)).length >= 10;
    });
}

function isExportableTable(table: HTMLTableElement): boolean {
    if (table.closest(IGNORE_TABLE_IN)) {
        return false;
    }

    if (isDayCalendarTable(table) && !table.closest('.sheet-document')) {
        return false;
    }

    return isDisplayed(table);
}

export function collectPageSheets(labels: ExportLabels): ExportSheet[] {
    const root = exportRoot();
    const used = new Set<string>();
    const sheets: ExportSheet[] = [];
    const title = pageTitle();
    const stats = extractStatRows(root, labels);

    if (stats.length > 1) {
        sheets.push({
            name: sanitizeSheetName(labels.summary, used),
            rows: stats,
        });
    }

    [...root.querySelectorAll('table')]
        .filter(
            (table): table is HTMLTableElement =>
                table instanceof HTMLTableElement && isExportableTable(table),
        )
        .forEach((table, index) => {
            const rows = tableToRows(table);

            if (rows.length === 0) {
                return;
            }

            sheets.push({
                name: sanitizeSheetName(
                    nearestTitle(table, index === 0 ? title : `${title} ${index + 1}`),
                    used,
                ),
                rows,
            });
        });

    return sheets;
}

function isNumericLike(value: string): boolean {
    return /^-?\d+(\.\d+)?$/.test(value.replaceAll(',', ''));
}

export function sheetsToSpreadsheetXml(sheets: ExportSheet[]): string {
    const worksheets = sheets
        .map((sheet) => {
            const columnCount = Math.max(
                1,
                ...sheet.rows.map((row) => row.length),
            );
            const columnsXml = Array.from(
                { length: columnCount },
                () => '<Column ss:AutoFitWidth="1" ss:Width="120"/>',
            ).join('');
            const rowsXml = sheet.rows
                .map((row, rowIndex) => {
                    const cells = row
                        .map((value) => {
                            const text = xmlEscape(value);
                            const numeric =
                                rowIndex > 0 && isNumericLike(value);

                            return `<Cell><Data ss:Type="${numeric ? 'Number' : 'String'}">${numeric ? value.replaceAll(',', '') : text}</Data></Cell>`;
                        })
                        .join('');

                    return `<Row>${cells}</Row>`;
                })
                .join('');

            return `<Worksheet ss:Name="${xmlEscape(sheet.name)}"><Table>${columnsXml}${rowsXml}</Table></Worksheet>`;
        })
        .join('');

    return `<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
${worksheets}
</Workbook>`;
}

function downloadBlob(filename: string, contents: string, mime: string): void {
    const blob = new Blob([contents], { type: mime });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;
    document.body.append(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function printTableHtml(
    title: string,
    sheets: ExportSheet[],
    generatedLabel: string,
): void {
    const dir = document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr';
    const generated = `${generatedLabel}: ${new Date().toLocaleString()}`;
    const sections = sheets
        .map((sheet) => {
            const header = sheet.rows[0] ?? [];
            const body = sheet.rows.slice(1);
            const head = header
                .map((cell) => `<th>${xmlEscape(cell)}</th>`)
                .join('');
            const bodyRows = body
                .map((row) => {
                    const cells = row
                        .map((cell, index) => {
                            const align = isNumericLike(cell) ? ' num' : '';
                            const headerAlign = isNumericLike(header[index] ?? '')
                                ? ' num'
                                : '';

                            return `<td class="${align || headerAlign}">${xmlEscape(cell)}</td>`;
                        })
                        .join('');

                    return `<tr>${cells}</tr>`;
                })
                .join('');

            const heading =
                sheets.length > 1 && sheet.name !== title
                    ? `<h2>${xmlEscape(sheet.name)}</h2>`
                    : '';

            return `${heading}<table><thead><tr>${head}</tr></thead><tbody>${bodyRows}</tbody></table>`;
        })
        .join('');

    const html = `<!DOCTYPE html>
<html lang="${document.documentElement.lang || 'en'}" dir="${dir}">
<head>
<meta charset="utf-8">
<title>${xmlEscape(title)}</title>
<style>
  @page { size: A4 landscape; margin: 10mm; }
  html, body { background: #fff; color: #111; }
  body { margin: 0; font: 12px/1.4 system-ui, -apple-system, "Segoe UI", sans-serif; }
  h1 { margin: 0; font-size: 18px; }
  .meta { margin: 4px 0 14px; font-size: 11px; color: #444; }
  h2 { margin: 18px 0 8px; font-size: 13px; border-bottom: 2px solid #111; padding-bottom: 4px; }
  table { width: 100%; border-collapse: collapse; margin: 0 0 18px; }
  th, td { border: 1px solid #222; padding: 5px 7px; text-align: start; vertical-align: top; }
  th { background: #111; color: #fff; font-weight: 600; }
  tr:nth-child(even) td { background: #f4f4f4; }
  td.num, th.num { text-align: end; font-variant-numeric: tabular-nums; }
</style>
</head>
<body>
  <h1>${xmlEscape(title)}</h1>
  <p class="meta">${xmlEscape(generated)}</p>
  ${sections}
</body>
</html>`;

    const iframe = document.createElement('iframe');
    iframe.setAttribute('aria-hidden', 'true');
    iframe.style.cssText =
        'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
    document.body.append(iframe);

    const doc = iframe.contentDocument;
    const win = iframe.contentWindow;

    if (!doc || !win) {
        iframe.remove();
        return;
    }

    doc.open();
    doc.write(html);
    doc.close();

    const cleanup = (): void => iframe.remove();

    win.addEventListener('afterprint', cleanup, { once: true });
    win.setTimeout(() => {
        win.focus();
        win.print();
    }, 50);
    win.setTimeout(cleanup, 60_000);
}

export function usePageExport() {
    const { t } = useTranslations();
    const notify = useNotify();

    const labels = (): ExportLabels => ({
        summary: t('Summary'),
        metric: t('Metric'),
        value: t('Value'),
        generated: t('Generated'),
    });

    const sheetsOrNotify = (): ExportSheet[] | null => {
        const sheets = collectPageSheets(labels());

        if (sheets.length === 0) {
            notify.info(t('Nothing to export.'));

            return null;
        }

        return sheets;
    };

    const printPage = (): void => {
        const sheets = sheetsOrNotify();

        if (!sheets) {
            return;
        }

        printTableHtml(pageTitle(), sheets, labels().generated);
    };

    const exportExcel = (filename?: string): void => {
        const sheets = sheetsOrNotify();

        if (!sheets) {
            return;
        }

        const stamp = new Date().toISOString().slice(0, 10);
        const name = `${filename ?? slugify(pageTitle())}-${stamp}.xls`;

        downloadBlob(
            name,
            `\uFEFF${sheetsToSpreadsheetXml(sheets)}`,
            'application/vnd.ms-excel;charset=utf-8',
        );
    };

    return {
        printPage,
        exportExcel,
    };
}
