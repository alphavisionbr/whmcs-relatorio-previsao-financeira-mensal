<?php

$reportdata["title"] = "Previsão Financeira Mensal";
$reportdata["description"] = "Valores previstos e já faturados a receber no mês selecionado, sem duplicidade.";

/**
 * Alphavision® WHMCS Relatório Previsão Financeira Mensal
 *
 * Valores previstos e já faturados a receber no mês selecionado, sem duplicidade.
 *
 * @package   AlphavisionWHMCSRelatorioPrevisaoFinanceiraMensal
 * @version   1.0.0
 * @author    Alphavision®
 * @copyright Copyright (c) 2026 Alphavision®
 * @license   MIT
 * @link      https://alphavision.com.br/
 */

use WHMCS\Database\Capsule;

$avPrintMetadata = '<span class="av-report-title-data" style="display:none">Previsão Financeira Mensal</span>'
    . '<span class="av-report-description-data" style="display:none">Valores previstos e já faturados a receber no mês selecionado, sem duplicidade.</span>';


if (!defined('WHMCS')) {
    exit('Acesso direto não permitido.');
}

const ALPHAVISION_RELATORIO_PREVISAO_FINANCEIRA_MENSAL_VERSION = '1.0.0';

$avEscape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$avClientName = static function ($row): string {
    $company = trim((string) ($row->companyname ?? ''));
    $person = trim((string) ($row->firstname ?? '') . ' ' . (string) ($row->lastname ?? ''));
    $id = (int) ($row->userid ?? $row->clientid ?? 0);

    return $company !== '' ? $company : ($person !== '' ? $person : 'Cliente #' . $id);
};

$avFormatDate = static function ($date): string {
    if (!$date || $date === '0000-00-00') {
        return '—';
    }

    $timestamp = strtotime((string) $date);

    return $timestamp ? date('d/m/Y', $timestamp) : (string) $date;
};

$avFormatMoney = static function ($amount, $prefix = 'R$ ', $suffix = ''): string {
    return trim((string) $prefix . number_format((float) $amount, 2, ',', '.') . (string) $suffix);
};

$avCurrencyParts = static function ($currencyId): array {
    static $cache = [];

    $currencyId = (int) $currencyId;

    if (isset($cache[$currencyId])) {
        return $cache[$currencyId];
    }

    $currency = Capsule::table('tblcurrencies')->where('id', $currencyId)->first();

    if (!$currency) {
        return $cache[$currencyId] = ['R$ ', ''];
    }

    return $cache[$currencyId] = [(string) $currency->prefix, (string) $currency->suffix];
};

$avPrintButton = static function (): string {
    return '
        <button type="button" class="btn btn-default av-report-print-button" onclick="avPrintReport();" style="margin-bottom:8px">
            <i class="fas fa-print"></i> Imprimir
        </button>
        <script>
        function avPrintReport() {
            var tables = Array.prototype.slice.call(document.querySelectorAll("table"));
            var reportTable = null;
            var maxRows = 0;

            tables.forEach(function(table) {
                var rows = table.querySelectorAll("tr").length;
                if (rows > maxRows) {
                    maxRows = rows;
                    reportTable = table;
                }
            });

            if (!reportTable) {
                alert("Não foi possível localizar a tabela do relatório.");
                return;
            }

            var title = document.querySelector("h2, h1, .content-header h1, .content-header h2");
            var description = null;
            var headings = document.querySelectorAll("h2, h3, h4, p");
            for (var i = 0; i < headings.length; i++) {
                if (headings[i].textContent.trim().length > 20 && headings[i].textContent.trim().length < 300) {
                    if (!headings[i].closest("nav") && !headings[i].closest(".sidebar")) {
                        description = headings[i];
                        break;
                    }
                }
            }

            var summary = document.querySelector(".av-report-summary");
            var footer = document.querySelector(".av-report-footer");

            var printWindow = window.open("", "_blank", "width=1200,height=800");
            if (!printWindow) {
                alert("O navegador bloqueou a janela de impressão. Permita pop-ups para este endereço.");
                return;
            }

            var reportTitle = document.querySelector(".av-report-title-data");
            var reportDescription = document.querySelector(".av-report-description-data");

            var html = "<!doctype html><html><head><meta charset=\"utf-8\">" +
                "<title>" + (reportTitle ? reportTitle.textContent : "Relatório") + "</title>" +
                "<style>" +
                "body{font-family:Arial,Helvetica,sans-serif;color:#222;margin:28px;font-size:12px}" +
                "h1{font-size:22px;margin:0 0 6px}p.description{font-size:13px;color:#555;margin:0 0 18px}" +
                ".summary{border:1px solid #bbb;background:#f5f5f5;padding:10px 12px;margin:0 0 18px}" +
                "table{width:100%;border-collapse:collapse;margin-top:10px}" +
                "th,td{border:1px solid #bbb;padding:6px 7px;text-align:left;vertical-align:top}" +
                "th{background:#eee;font-weight:bold}" +
                "tr:nth-child(even) td{background:#fafafa}" +
                ".footer{margin-top:14px;font-size:12px}" +
                ".generated{margin-top:20px;text-align:right;color:#666;font-size:11px}" +
                "@page{size:auto;margin:12mm}" +
                "</style></head><body>" +
                "<h1>" + (reportTitle ? reportTitle.textContent : "Relatório") + "</h1>" +
                (reportDescription ? "<p class=\"description\">" + reportDescription.textContent + "</p>" : "") +
                (summary ? "<div class=\"summary\">" + summary.innerHTML + "</div>" : "") +
                reportTable.outerHTML +
                (footer ? "<div class=\"footer\">" + footer.innerHTML + "</div>" : "") +
                "<div class=\"generated\">Gerado em " + new Date().toLocaleString("pt-BR") + "</div>" +
                "</body></html>";

            printWindow.document.open();
            printWindow.document.write(html);
            printWindow.document.close();
            printWindow.focus();

            setTimeout(function() {
                printWindow.print();
            }, 300);
        }
        </script>
    ';
};

$month = isset($_REQUEST['month']) ? max(1, min(12, (int) $_REQUEST['month'])) : (int) date('n');
$year = isset($_REQUEST['year']) ? max(2000, min(2100, (int) $_REQUEST['year'])) : (int) date('Y');
$typeFilter = isset($_REQUEST['type']) ? trim((string) $_REQUEST['type']) : '';

$start = sprintf('%04d-%02d-01', $year, $month);
$end = date('Y-m-t', strtotime($start));

$months = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro',
];

$monthOptions = '';
foreach ($months as $number => $name) {
    $monthOptions .= '<option value="' . $number . '"' . ($number === $month ? ' selected' : '') . '>' . $name . '</option>';
}

$yearOptions = '';
for ($itemYear = (int) date('Y') - 5; $itemYear <= (int) date('Y') + 10; $itemYear++) {
    $yearOptions .= '<option value="' . $itemYear . '"' . ($itemYear === $year ? ' selected' : '') . '>' . $itemYear . '</option>';
}

$typeOptions = [
    '' => 'Todos',
    'invoice' => 'Faturas emitidas',
    'service' => 'Produtos/Serviços previstos',
    'domain' => 'Domínios previstos',
    'addon' => 'Addons previstos',
    'billable' => 'Itens faturáveis previstos',
];

$typeHtml = '';
foreach ($typeOptions as $value => $label) {
    $typeHtml .= '<option value="' . $avEscape($value) . '"' . ($typeFilter === $value ? ' selected' : '') . '>' . $avEscape($label) . '</option>';
}

$items = [];

/*
 * Modelo financeiro da Previsão Mensal
 *
 * 1. Faturas já emitidas e ainda a receber prevalecem sobre qualquer previsão.
 * 2. Itens recorrentes vinculados a essas faturas são removidos da previsão para
 *    impedir dupla contagem.
 * 3. Serviços, domínios, addons e itens faturáveis só aparecem como "Previsto"
 *    enquanto ainda não existe uma fatura aberta para o período selecionado.
 *
 * Resultado:
 * - uma cobrança já faturada aparece uma única vez, pelo total da fatura;
 * - uma fatura que reúne hospedagem + renovação manual de domínio aparece em
 *   uma única linha;
 * - um Item Faturável convertido em fatura continua aparecendo no relatório.
 */

$invoicedServiceIds = [];
$invoicedDomainIds = [];
$invoicedAddonIds = [];
$invoicedBillableIds = [];
$invoiceItemCounts = [];

/*
 * Faturas a receber no mês.
 *
 * "Unpaid" é o status financeiro efetivamente em aberto no WHMCS. Mantemos
 * "Payment Pending" por compatibilidade com gateways que utilizem esse estado.
 * Faturas Paid, Cancelled, Refunded, Collections e Draft não são valores
 * atualmente a receber e, portanto, não entram na previsão.
 */
$invoiceQuery = Capsule::table('tblinvoices as i')
    ->join('tblclients as c', 'c.id', '=', 'i.userid')
    ->whereBetween('i.duedate', [$start, $end])
    ->whereIn('i.status', ['Unpaid', 'Payment Pending'])
    ->select(
        'i.id',
        'i.userid',
        'i.duedate',
        'i.total',
        'i.status',
        'c.firstname',
        'c.lastname',
        'c.companyname',
        'c.currency'
    )
    ->orderBy('i.duedate')
    ->orderBy('i.id');

$invoiceRows = $invoiceQuery->get();
$invoiceIds = [];

foreach ($invoiceRows as $invoice) {
    $invoiceIds[] = (int) $invoice->id;
}

/*
 * Lemos os itens das faturas apenas para:
 * - saber quantos itens existem em cada fatura;
 * - identificar serviços/domínios/addons/itens faturáveis já faturados;
 * - impedir que os mesmos valores apareçam novamente como previsão.
 */
if ($invoiceIds) {
    $invoiceItems = Capsule::table('tblinvoiceitems')
        ->whereIn('invoiceid', $invoiceIds)
        ->select('invoiceid', 'type', 'relid')
        ->get();

    foreach ($invoiceItems as $invoiceItem) {
        $invoiceId = (int) $invoiceItem->invoiceid;
        $relationId = (int) $invoiceItem->relid;
        $type = strtolower(trim((string) $invoiceItem->type));

        $invoiceItemCounts[$invoiceId] = ($invoiceItemCounts[$invoiceId] ?? 0) + 1;

        if ($relationId <= 0) {
            continue;
        }

        if ($type === 'hosting') {
            $invoicedServiceIds[$relationId] = true;
            continue;
        }

        if (in_array($type, ['domain', 'domainregister', 'domainrenewal', 'domaintransfer'], true)) {
            $invoicedDomainIds[$relationId] = true;
            continue;
        }

        if ($type === 'addon') {
            $invoicedAddonIds[$relationId] = true;
            continue;
        }

        /*
         * Itens faturáveis convertidos em fatura normalmente chegam como Item.
         * O relid, quando presente, identifica o registro de origem.
         */
        if ($type === 'item') {
            $invoicedBillableIds[$relationId] = true;
        }
    }
}

/*
 * Faturas emitidas aparecem como uma única linha, usando o total da própria
 * fatura. Não quebramos novamente pelos itens, porque a obrigação financeira
 * já é a fatura.
 */
if ($typeFilter === '' || $typeFilter === 'invoice') {
    $today = date('Y-m-d');

    foreach ($invoiceRows as $invoice) {
        $invoiceId = (int) $invoice->id;
        $itemCount = (int) ($invoiceItemCounts[$invoiceId] ?? 0);

        if ($invoice->status === 'Payment Pending') {
            $displayStatus = 'Pagamento pendente';
        } elseif ((string) $invoice->duedate < $today) {
            $displayStatus = 'Vencida';
        } else {
            $displayStatus = 'Faturada';
        }

        $items[] = [
            'client' => $avClientName($invoice),
            'description' => 'Fatura #' . $invoiceId
                . ($itemCount > 0 ? ' (' . $itemCount . ' ' . ($itemCount === 1 ? 'item' : 'itens') . ')' : ''),
            'type' => 'Fatura emitida',
            'date' => $invoice->duedate,
            'cycle' => 'Fatura',
            'amount' => (float) $invoice->total,
            'currency' => (int) $invoice->currency,
            'status' => $displayStatus,
        ];
    }
}

/*
 * Produtos e serviços ainda não faturados.
 */
if ($typeFilter === '' || $typeFilter === 'service') {
    $hostingAmountColumn = Capsule::schema()->hasColumn('tblhosting', 'amount')
        ? 'amount'
        : (Capsule::schema()->hasColumn('tblhosting', 'recurringamount') ? 'recurringamount' : null);

    if ($hostingAmountColumn !== null) {
        $query = Capsule::table('tblhosting as h')
            ->join('tblclients as c', 'c.id', '=', 'h.userid')
            ->leftJoin('tblproducts as p', 'p.id', '=', 'h.packageid')
            ->whereIn('h.domainstatus', ['Active', 'Pending'])
            ->whereBetween('h.nextduedate', [$start, $end])
            ->whereNotIn('h.billingcycle', ['Free Account', 'One Time'])
            ->select(
                'h.id',
                'h.userid',
                'h.domain',
                'h.nextduedate',
                'h.billingcycle',
                'p.name as productname',
                'c.firstname',
                'c.lastname',
                'c.companyname',
                'c.currency'
            )
            ->addSelect('h.' . $hostingAmountColumn . ' as recurringvalue');

        if ($invoicedServiceIds) {
            $query->whereNotIn('h.id', array_keys($invoicedServiceIds));
        }

        $rows = $query->get();

        foreach ($rows as $row) {
            $items[] = [
                'client' => $avClientName($row),
                'description' => trim((string) $row->productname . ($row->domain ? ' - ' . $row->domain : '')),
                'type' => 'Produto/Serviço',
                'date' => $row->nextduedate,
                'cycle' => $row->billingcycle,
                'amount' => (float) $row->recurringvalue,
                'currency' => (int) $row->currency,
                'status' => 'Previsto',
            ];
        }
    }
}

/*
 * Domínios ainda não faturados.
 */
if ($typeFilter === '' || $typeFilter === 'domain') {
    $query = Capsule::table('tbldomains as d')
        ->join('tblclients as c', 'c.id', '=', 'd.userid')
        ->whereIn('d.status', ['Active', 'Pending'])
        ->whereBetween('d.nextduedate', [$start, $end])
        ->select(
            'd.id',
            'd.userid',
            'd.domain',
            'd.nextduedate',
            'd.registrationperiod',
            'd.recurringamount',
            'c.firstname',
            'c.lastname',
            'c.companyname',
            'c.currency'
        );

    if ($invoicedDomainIds) {
        $query->whereNotIn('d.id', array_keys($invoicedDomainIds));
    }

    $rows = $query->get();

    foreach ($rows as $row) {
        $items[] = [
            'client' => $avClientName($row),
            'description' => $row->domain,
            'type' => 'Domínio',
            'date' => $row->nextduedate,
            'cycle' => (int) $row->registrationperiod . ' ano(s)',
            'amount' => (float) $row->recurringamount,
            'currency' => (int) $row->currency,
            'status' => 'Previsto',
        ];
    }
}

/*
 * Addons ainda não faturados.
 */
if ($typeFilter === '' || $typeFilter === 'addon') {
    $addonRecurringColumn = Capsule::schema()->hasColumn('tblhostingaddons', 'recurring')
        ? 'recurring'
        : (Capsule::schema()->hasColumn('tblhostingaddons', 'recurringamount') ? 'recurringamount' : null);

    if ($addonRecurringColumn !== null) {
        $query = Capsule::table('tblhostingaddons as a')
            ->join('tblclients as c', 'c.id', '=', 'a.userid')
            ->leftJoin('tbladdons as ad', 'ad.id', '=', 'a.addonid')
            ->whereIn('a.status', ['Active', 'Pending'])
            ->whereBetween('a.nextduedate', [$start, $end])
            ->whereNotIn('a.billingcycle', ['Free Account', 'One Time'])
            ->select(
                'a.id',
                'a.userid',
                'a.name',
                'a.nextduedate',
                'a.billingcycle',
                'ad.name as addonname',
                'c.firstname',
                'c.lastname',
                'c.companyname',
                'c.currency'
            )
            ->addSelect('a.' . $addonRecurringColumn . ' as recurringvalue');

        if ($invoicedAddonIds) {
            $query->whereNotIn('a.id', array_keys($invoicedAddonIds));
        }

        $rows = $query->get();

        foreach ($rows as $row) {
            $items[] = [
                'client' => $avClientName($row),
                'description' => trim((string) ($row->name ?: $row->addonname ?: 'Addon #' . $row->id)),
                'type' => 'Addon',
                'date' => $row->nextduedate,
                'cycle' => $row->billingcycle,
                'amount' => (float) $row->recurringvalue,
                'currency' => (int) $row->currency,
                'status' => 'Previsto',
            ];
        }
    }
}

/*
 * Itens faturáveis ainda não convertidos em fatura.
 */
if (
    ($typeFilter === '' || $typeFilter === 'billable')
    && Capsule::schema()->hasTable('tblbillableitems')
) {
    $dateColumn = Capsule::schema()->hasColumn('tblbillableitems', 'nextinvoicedate')
        ? 'nextinvoicedate'
        : (Capsule::schema()->hasColumn('tblbillableitems', 'duedate') ? 'duedate' : null);

    if ($dateColumn !== null) {
        $query = Capsule::table('tblbillableitems as b')
            ->join('tblclients as c', 'c.id', '=', 'b.userid')
            ->whereBetween('b.' . $dateColumn, [$start, $end])
            ->select(
                'b.id',
                'b.userid',
                'b.description',
                'b.amount',
                'c.firstname',
                'c.lastname',
                'c.companyname',
                'c.currency'
            );

        if (Capsule::schema()->hasColumn('tblbillableitems', 'recur')) {
            $query->where('b.recur', '>', 0);
        }

        if (Capsule::schema()->hasColumn('tblbillableitems', 'recurcycle')) {
            $query->addSelect('b.recurcycle');
        }

        if ($invoicedBillableIds) {
            $query->whereNotIn('b.id', array_keys($invoicedBillableIds));
        }

        $rows = $query->get();

        foreach ($rows as $row) {
            $items[] = [
                'client' => $avClientName($row),
                'description' => $row->description ?: 'Item faturável #' . $row->id,
                'type' => 'Item faturável',
                'date' => $row->{$dateColumn},
                'cycle' => isset($row->recurcycle) ? (string) $row->recurcycle : 'Recorrente',
                'amount' => (float) $row->amount,
                'currency' => (int) $row->currency,
                'status' => 'Previsto',
            ];
        }
    }
}

usort($items, static function (array $left, array $right): int {
    $dateCompare = strcmp((string) ($left['date'] ?? ''), (string) ($right['date'] ?? ''));

    return $dateCompare !== 0
        ? $dateCompare
        : strcmp((string) ($left['client'] ?? ''), (string) ($right['client'] ?? ''));
});

$totals = [];
$typeTotals = [];
$clients = [];

foreach ($items as $item) {
    $currencyId = $item['currency'];
    $totals[$currencyId] = ($totals[$currencyId] ?? 0) + $item['amount'];
    $typeTotals[$item['type']][$currencyId] = ($typeTotals[$item['type']][$currencyId] ?? 0) + $item['amount'];
    $clients[$item['client']] = true;
}

$summaryParts = [];
foreach ($totals as $currencyId => $total) {
    [$prefix, $suffix] = $avCurrencyParts($currencyId);
    $summaryParts[] = '<strong>' . $avEscape($avFormatMoney($total, $prefix, $suffix)) . '</strong>';
}

$totalText = $summaryParts ? implode(' + ', $summaryParts) : 'R$ 0,00';

$reportdata["headertext"] = $avPrintMetadata . '
<form method="get" action="reports.php" class="form-inline av-report-filters" style="margin-bottom:15px">
    <input type="hidden" name="report" value="previsao_mensal">
    <div class="form-group" style="margin:0 8px 8px 0">
        <label>Mês&nbsp;</label>
        <select name="month" class="form-control">' . $monthOptions . '</select>
    </div>
    <div class="form-group" style="margin:0 8px 8px 0">
        <label>Ano&nbsp;</label>
        <select name="year" class="form-control">' . $yearOptions . '</select>
    </div>
    <div class="form-group" style="margin:0 8px 8px 0">
        <label>Origem&nbsp;</label>
        <select name="type" class="form-control">' . $typeHtml . '</select>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-bottom:8px">Gerar relatório</button>
    <span class="av-report-print-button">' . $avPrintButton() . '</span>
</form>
<div class="alert alert-info av-report-summary" style="margin-bottom:15px">
    <strong>Período:</strong> ' . $avEscape($months[$month] . '/' . $year) . '
    &nbsp; | &nbsp; <strong>Previsão:</strong> ' . $totalText . '
    &nbsp; | &nbsp; <strong>Clientes:</strong> ' . count($clients) . '
    &nbsp; | &nbsp; <strong>Itens:</strong> ' . count($items) . '
</div>';

$reportdata["tableheadings"] = [
    'Cliente',
    'Descrição',
    'Origem',
    'Vencimento',
    'Ciclo',
    'Valor',
    'Situação',
];

foreach ($items as $item) {
    [$prefix, $suffix] = $avCurrencyParts($item['currency']);

    $reportdata["tablevalues"][] = [
        $item['client'],
        $item['description'],
        $item['type'],
        $avFormatDate($item['date']),
        $item['cycle'],
        $avFormatMoney($item['amount'], $prefix, $suffix),
        $item['status'],
    ];
}

$footer = '<div class="av-report-footer"><strong>Resumo por origem:</strong><br>';

if (!$typeTotals) {
    $footer .= 'Nenhum valor previsto para o período selecionado.';
} else {
    foreach ($typeTotals as $type => $currencyTotals) {
        $parts = [];

        foreach ($currencyTotals as $currencyId => $total) {
            [$prefix, $suffix] = $avCurrencyParts($currencyId);
            $parts[] = $avEscape($avFormatMoney($total, $prefix, $suffix));
        }

        $footer .= $avEscape($type) . ': ' . implode(' + ', $parts) . '<br>';
    }
}

$reportdata["footertext"] = $footer . '</div>';
