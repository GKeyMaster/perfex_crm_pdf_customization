<?php

defined('BASEPATH') or exit('No direct script access allowed');

add_action('pdf_footer', function($data) use ($pdf) {
    $pdf = $data['pdf_instance'];

    $headerImage = __DIR__ . '/header.png';
    $pdf->Image($headerImage, 0, 0, $pdf->getPageWidth(), 7);

    $footerImage = __DIR__ . '/footer.png';
    $pdf->Image($footerImage, 0, $pdf->getPageHeight() -20, $pdf->getPageWidth(), 20);
});

$pdf->setY(14);

$pdf->SetFont('Zain', '', 12);
$pdf->SetFont('Zain', 'B', 12);

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column .= '<span style="font-weight:bold;font-size:56px;">' . _l('invoice_pdf_heading') . '</span><br />';
$info_right_column .= '<b style="font-size:22px;color:#4e4e4e;"># ' . $invoice_number . '</b>';

if (get_option('show_status_on_pdf_ei') == 1) {
    $info_right_column .= '<br /><span style="font-weight:bold;font-size:24px;color:rgb(' . invoice_status_color_pdf($status) . ');text-transform:uppercase;">' . format_invoice_status($status, '', false) . '</span>';
}

if ($status != Invoices_model::STATUS_PAID && $status != Invoices_model::STATUS_CANCELLED && get_option('show_pay_link_to_invoice_pdf') == 1
    && found_invoice_mode($payment_modes, $invoice->id, false)) {
    $info_right_column .= ' - <a style="color:#84c529;text-decoration:none;text-transform:uppercase;" href="' . site_url('invoice/' . $invoice->id . '/' . $invoice->hash) . '"><1b>' . _l('view_invoice_pdf_link_pay') . '</1b></a>';
}

// Add logo
$info_left_column .= '<img width="160px" src="' . __DIR__ . '/logo.png">';

// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(10);

$client = $invoice->client;

file_put_contents('client_debug.log', print_r($client, true));

// Bill to
$invoice_info = '<b>' . _l('invoice_bill_to') . ':</b><br />';
$invoice_info .= '<b style="font-size:15px;color:#666666;">';
$invoice_info .= $client->company . '</b><br />';
$invoice_info .= '<b style="font-size:15px;color:#666666;">';
$invoice_info .= $client->phonenumber . '</b><br />';
$invoice_info .= '<b style="font-size:15px;color:#666666;">';
$invoice_info .= $client->address . ', ' . $client->city . ', ' . $client->state . ', ' . $client->zip . ', ' . get_country($client->country)->short_name . '</b>';

$organization_info = hooks()->apply_filters('invoicepdf_organization_info', $organization_info, $invoice);
$invoice_info      = hooks()->apply_filters('invoice_pdf_info', $invoice_info, $invoice);

$organization_info = '';
$organization_info .= '<b>Diwan Style</b>';
$organization_info .= '<br /><b style="font-size:15px;color:#666666;">+973 39150033</b><br />';
$organization_info .= '<b style="font-size:15px;color:#666666;">info@diwanstyle.bh</b>';
$organization_info .= '<br /><b style="font-size:15px;color:#666666;">www.diwanstyle.com, .bh, .uk</b>';
$organization_info .= '<br /><b style="font-size:15px;color:#666666;">Cr.٣-
١١٦٨٤١
, Kingdom of Bahrain.</b>';

$left_info  = $swap == '1' ? $invoice_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $invoice_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 15));

// The items table
$items = get_items_table_data($invoice, 'invoice', 'pdf');

$tblhtml = $items->table();

$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->Ln(3);

$tbltotal = '<div style="border-top-color:#eeeeee;border-top-width:1px;border-top-style:solid; 1px solid black;"></div><br />';
$tbltotal .= '<table>';
$tbltotal .= '<tr>';
$tbltotal .= '<td width="5%" style="background-color:#dddddd;"></td>';
$tbltotal .= '<td width="35%" align="left" style="background-color:#dddddd;color:#888888;">';
$tbltotal .= '<br/><br/><b style="color:#000000;font-size:12px;">BANK TRANSFER</b>';
$tbltotal .= '<br/><b style="font-size:12px;">NAME: DIWAN STYLE</b>';
$tbltotal .= '<br/><b style="font-size:12px;">IBAN: BH
٨٥
BIBB
٠٠١٠٠٠٠٠٦٧٠٨٢٢</b><br/>';
$tbltotal .= '<b style="font-size:12px;">ACCOUNT NUMBER: 
١٠٠٠٠٠٦٧٠٨٢٢</b><br/>';
$tbltotal .= '<b style="font-size:12px;">SWIFT CODE: BIBBBHBM</b><br/>';
$tbltotal .= '<b style="font-size:12px;">BAHRAIN ISLAMIC BANK B.S.C.</b>';
$tbltotal .= '</td>';
$tbltotal .= '<td align="right" width="15%" style="background-color:#5cf7dd;">';
$tbltotal .= '<br/><br/><b style="font-size:16px;">Invoice Date</b>';
$tbltotal .= '<br/><b style="font-size:16px;">' . _d($invoice->date) . '</b>';
$tbltotal .= '<br/><br/><b style="font-size:16px;">Due Date</b>';
$tbltotal .= '<br/><b style="font-size:16px;">' . _d($invoice->duedate) . '</b>';
$tbltotal .= '</td>';
$tbltotal .= '<td width="5%" style="background-color:#5cf7dd;"></td>';
$tbltotal .= '<td align="left" width="40%" style="background-color:#231f20;color:#ffffff;">';
$tbltotal .= '<table cellpadding="6" style="font-size:' . ($font_size + 4) . 'px">';
$tbltotal .= '<tr><td width="50%"></td><td width="50%"></td></tr>';
$tbltotal .= '
<tr style="font-size:18px">
    <td align="right" width="50%"><strong>' . _l('invoice_subtotal') . '</strong></td>
    <td align="left" width="50%">' . (string)$invoice->subtotal . ' BHD' . '</td>
</tr>';

$tbltotal .= '
<tr style="font-size:18px">
    <td align="right" width="50%"><strong>' . _l('invoice_total') . '</strong></td>
    <td align="left" width="50%">' . (string)$invoice->total . ' BHD' . '</td>
</tr>';

if (get_option('show_amount_due_on_invoice') == 1 && $invoice->status != Invoices_model::STATUS_CANCELLED) {
    $tbltotal .= '<tr style="font-size:24px">
       <td align="right" width="50%"><strong>' . _l('invoice_amount_due') . '</strong></td>
       <td align="left" width="50%">' . (string)$invoice->total_left_to_pay . ' BHD' . '</td>
   </tr>';
}

$tbltotal .= '<tr><td></td><td></td></tr>';

$tbltotal .= '</table>';

$tbltotal .= '</td></tr></table>';
$pdf->writeHTML($tbltotal, true, false, false, false, '');

$tbltotal = '<br /><div style="border-top-color:#eeeeee;border-top-width:1px;border-top-style:solid; 1px solid black;"></div>';

$pdf->writeHTML($tbltotal, true, false, false, false, '');

if (!empty($invoice->terms)) {
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', 8);
    $pdf->Cell(0, 0, _l('terms_and_conditions') . ':', 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', 8);
    $pdf->SetTextColor(210, 210, 210);
    $pdf->Ln(2);
    $pdf->writeHTMLCell('', '', '', '', $invoice->terms, 0, 1, false, true, 'L', true);
    $pdf->SetColor(255, 255, 255);
    $pdf->SetFont($font_name, '', 1);
}