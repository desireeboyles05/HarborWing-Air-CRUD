<?php
declare(strict_types=1);
function e(mixed $value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function go(string $url): never { header('Location: ' . $url); exit; }
function flash(string $message, string $type='success'): void { $_SESSION['flash'] = ['message'=>$message, 'type'=>$type]; }
function showFlash(): void { if (!empty($_SESSION['flash'])) { $f=$_SESSION['flash']; echo '<div class="notice '.e($f['type']).'">'.e($f['message']).'</div>'; unset($_SESSION['flash']); } }
function csrfToken(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function checkCsrf(): void { if ($_SERVER['REQUEST_METHOD']==='POST' && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(403); exit('Invalid request token. Refresh the page and try again.'); } }
function urlFor(string $table, array $extra=[]): string { return '?' . http_build_query(array_merge(['table'=>$table], $extra)); }
