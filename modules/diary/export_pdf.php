<?php
// Export one authenticated user's Diary entry as a PDF download.

$diary_export_initial_buffer_level = ob_get_level();
ob_start();

require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/diary_model.php';
require_once __DIR__ . '/diary_pdf.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Discard only output buffers opened by this endpoint.
 */
function diaryExportDiscardOutput($initial_buffer_level) {
    while (ob_get_level() > $initial_buffer_level) {
        ob_end_clean();
    }
}

/**
 * Return a small generic HTML response without exposing internal details.
 */
function diaryExportRespondWithError($message, $status_code, $initial_buffer_level) {
    diaryExportDiscardOutput($initial_buffer_level);
    http_response_code($status_code);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Diary PDF Export</title></head><body>'
        . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
        . '</body></html>';
    exit();
}

/**
 * Create the download filename from a validated entry date and Diary id.
 */
function diaryExportFilename($entry_date, $diary_id) {
    $entry_date = (string) $entry_date;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $entry_date);
    $date_errors = DateTimeImmutable::getLastErrors();
    $date_is_valid = $date !== false
        && ($date_errors === false
            || ($date_errors['warning_count'] === 0 && $date_errors['error_count'] === 0))
        && $date->format('Y-m-d') === $entry_date;

    $safe_date = $date_is_valid ? $entry_date : date('Y-m-d');

    return 'diary-entry-' . $safe_date . '-' . (int) $diary_id . '.pdf';
}

$requested_id = isset($_GET['id']) && is_string($_GET['id'])
    ? $_GET['id']
    : '';
$diary_id = preg_match('/^[1-9][0-9]*$/D', $requested_id) === 1
    ? filter_var(
        $requested_id,
        FILTER_VALIDATE_INT,
        array('options' => array('min_range' => 1))
    )
    : false;

if ($diary_id === false) {
    diaryExportRespondWithError(
        'Journal entry not found.',
        404,
        $diary_export_initial_buffer_level
    );
}

$entry = getDiaryEntryById($conn, (int) $diary_id, (int) $logged_in_user_id);

if (!is_array($entry)) {
    diaryExportRespondWithError(
        'Journal entry not found.',
        404,
        $diary_export_initial_buffer_level
    );
}

$pdf_document = diaryPdfPrepareEntryDocument($entry, $logged_in_user_id);

if (
    !is_array($pdf_document)
    || empty($pdf_document['valid'])
    || !isset($pdf_document['html'])
    || !is_string($pdf_document['html'])
    || $pdf_document['html'] === ''
) {
    diaryExportRespondWithError(
        'Journal entry not found.',
        404,
        $diary_export_initial_buffer_level
    );
}

$project_root = realpath(dirname(__DIR__, 2));
$autoload_path = $project_root !== false
    ? $project_root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php'
    : '';

if ($project_root === false || !is_file($autoload_path)) {
    diaryExportRespondWithError(
        'Journal entry could not be exported right now. Please try again.',
        500,
        $diary_export_initial_buffer_level
    );
}

try {
    require_once $autoload_path;

    if (!class_exists(Dompdf::class) || !class_exists(Options::class)) {
        throw new RuntimeException('PDF dependency unavailable.');
    }

    $options = new Options();
    $options->setChroot(array($project_root));
    $options->setAllowedProtocols(array('data://', 'file://'));
    $options->setIsRemoteEnabled(false);
    $options->setIsPhpEnabled(false);
    $options->setIsJavascriptEnabled(false);
    $options->setIsHtml5ParserEnabled(true);
    $options->setDefaultFont('DejaVu Sans');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($pdf_document['html'], 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdf_output = $dompdf->output();

    if (!is_string($pdf_output) || strncmp($pdf_output, '%PDF-', 5) !== 0) {
        throw new RuntimeException('PDF rendering failed.');
    }
} catch (Throwable $exception) {
    diaryExportRespondWithError(
        'Journal entry could not be exported right now. Please try again.',
        500,
        $diary_export_initial_buffer_level
    );
}

$filename = diaryExportFilename(
    isset($entry['entry_date']) ? $entry['entry_date'] : '',
    $diary_id
);

diaryExportDiscardOutput($diary_export_initial_buffer_level);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf_output));
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
echo $pdf_output;
exit();
?>
