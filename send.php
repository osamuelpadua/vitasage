<?php

declare(strict_types=1);

const CONTACT_RECIPIENT = 'contato@vitalagro.com.br';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metodo nao permitido.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('mail')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A funcao mail() nao esta habilitada neste servidor.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize_input(string $value): string
{
    $clean = strip_tags($value);
    $clean = trim($clean);
    return htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitize_single_line(string $value): string
{
    $clean = sanitize_input($value);
    return preg_replace('/[\r\n]+/', ' ', $clean) ?? '';
}

function respond_with_error(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $message,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$fullName = sanitize_input((string) ($_POST['full-name'] ?? ''));
$company = sanitize_input((string) ($_POST['company'] ?? ''));
$role = sanitize_input((string) ($_POST['role-title'] ?? $_POST['role'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$message = sanitize_input((string) ($_POST['message'] ?? ''));
$sourcePage = sanitize_input((string) ($_POST['source-page'] ?? 'Website'));

if ($fullName === '' || $email === '' || $message === '') {
    respond_with_error(400, 'Preencha os campos obrigatorios: Full name, E-mail e Message.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond_with_error(400, 'Informe um endereco de e-mail valido.');
}

$httpHost = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
$httpHost = strtolower($httpHost);
$httpHost = preg_replace('/:\d+$/', '', $httpHost) ?? '';
$httpHost = preg_replace('/^www\./', '', $httpHost) ?? '';
$fromAddress = $httpHost !== '' ? 'noreply@' . $httpHost : 'noreply@localhost';

$replyTo = sanitize_single_line($email);
$subjectSource = sanitize_single_line($sourcePage);
$mailSubject = 'Novo contato do site';

if ($subjectSource !== '') {
    $mailSubject .= ' - ' . $subjectSource;
}

$mailBody = implode("\n", [
    'Source Page: ' . ($sourcePage !== '' ? $sourcePage : 'N/A'),
    'Full name: ' . ($fullName !== '' ? $fullName : 'N/A'),
    'Company: ' . ($company !== '' ? $company : 'N/A'),
    'Role/Title: ' . ($role !== '' ? $role : 'N/A'),
    'E-mail: ' . sanitize_input($email),
    'Message:',
    $message !== '' ? $message : 'N/A',
]);

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: ' . sanitize_single_line($fromAddress),
    'Reply-To: ' . $replyTo,
];

$sent = mail(
    CONTACT_RECIPIENT,
    $mailSubject,
    $mailBody,
    implode("\r\n", $headers)
);

if (!$sent) {
    respond_with_error(500, 'Nao foi possivel enviar a mensagem. Tente novamente.');
}

echo json_encode([
    'success' => true,
    'message' => 'Mensagem enviada com sucesso!',
], JSON_UNESCAPED_UNICODE);
