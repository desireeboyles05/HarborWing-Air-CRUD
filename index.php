<?php
session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/catalog.php';
checkCsrf();
$table = (string)($_GET['table'] ?? '');
$action = (string)($_GET['action'] ?? '');
if ($table !== '' && !isset($catalog[$table]) && !isset($referenceTables[$table])) { http_response_code(404); exit('Unknown table.'); }
if (isset($catalog[$table])) {
    $meta = $catalog[$table]; $pk = $meta['pk']; $fields = $meta['fields'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['save', 'delete'], true)) {
        try {
            if ($action === 'delete') {
                $where = []; $params = [];
                foreach ($pk as $key) { if (!isset($_POST[$key]) || $_POST[$key] === '') throw new RuntimeException('Missing primary key.'); $where[] = "`$key` = ?"; $params[] = $_POST[$key]; }
                $stmt = $pdo->prepare('DELETE FROM `' . $table . '` WHERE ' . implode(' AND ', $where)); $stmt->execute($params);
                flash($stmt->rowCount() ? 'Record deleted.' : 'No matching record was found.');
            } else {
                $editing = ($_POST['mode'] ?? 'create') === 'edit'; $values = [];
                foreach ($fields as $col => $def) { $v = trim((string)($_POST[$col] ?? '')); if ($v === '' && !$def[2]) $values[$col] = null; else { if ($def[2] && $v === '') throw new RuntimeException($def[0] . ' is required.'); $values[$col] = $v; } }
                if (isset($values['email_address']) && $values['email_address'] !== null && !filter_var($values['email_address'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address.');
                if (isset($values['party_size']) && (int)$values['party_size'] < 1) throw new RuntimeException('Party size must be at least 1.');
                if (isset($values['payment_amount']) && (float)$values['payment_amount'] <= 0) throw new RuntimeException('Payment amount must be greater than zero.');
                if (isset($values['cost_amount']) && (float)$values['cost_amount'] < 0) throw new RuntimeException('Cost amount cannot be negative.');
                if (isset($values['airport_code'])) { $values['airport_code'] = strtoupper($values['airport_code']); if (!preg_match('/^[A-Z]{3}$/', $values['airport_code'])) throw new RuntimeException('Airport code must be exactly 3 letters.'); }
                foreach (['scheduled_departure_utc','scheduled_arrival_utc','created_at_utc','payment_date_utc','actual_departure_utc','actual_arrival_utc'] as $dt) { if (isset($values[$dt]) && $values[$dt] !== null) { $values[$dt] = str_replace('T', ' ', $values[$dt]); if (strlen($values[$dt]) === 16) $values[$dt] .= ':00'; } }
                if (isset($values['scheduled_departure_utc'], $values['scheduled_arrival_utc']) && $values['scheduled_arrival_utc'] <= $values['scheduled_departure_utc']) throw new RuntimeException('Arrival must be after departure.');
                if (isset($values['origin_airport_code'], $values['destination_airport_code']) && $values['origin_airport_code'] === $values['destination_airport_code']) throw new RuntimeException('Origin and destination airports must differ.');
                if (isset($values['valid_from'], $values['valid_to']) && $values['valid_to'] < $values['valid_from']) throw new RuntimeException('Valid-to date must not be earlier than valid-from date.');
                if ($editing) {
                    $set = []; $params = []; $where = [];
                    foreach ($values as $col => $v) { $set[] = "`$col` = ?"; $params[] = $v; }
                    foreach ($pk as $key) { $originalKey = '__pk_' . $key; if (!isset($_POST[$originalKey])) throw new RuntimeException('Missing original primary key.'); $where[] = "`$key` = ?"; $params[] = $_POST[$originalKey]; }
                    $stmt = $pdo->prepare('UPDATE `' . $table . '` SET ' . implode(',', $set) . ' WHERE ' . implode(' AND ', $where)); $stmt->execute($params); flash('Record updated.');
                } else {
                    $cols = array_keys($values); $stmt = $pdo->prepare('INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')'); $stmt->execute(array_values($values)); flash('Record created.');
                }
            }
        } catch (Throwable $ex) { $msg = $ex instanceof PDOException ? 'Database rejected the operation. Check duplicate values, required fields, and linked records.' : $ex->getMessage(); flash($msg, 'error'); }
        go(urlFor($table));
    }
    $editRow = null;
    if ($action === 'edit') { $where = []; $params = []; foreach ($pk as $key) { $where[] = "`$key` = ?"; $params[] = $_GET[$key] ?? ''; } $stmt = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE ' . implode(' AND ', $where)); $stmt->execute($params); $editRow = $stmt->fetch(); if (!$editRow) { flash('Record not found.', 'error'); go(urlFor($table)); } }
    $search = trim((string)($_GET['q'] ?? ''));
    $sql = 'SELECT * FROM `' . $table . '`';
    $params = [];
    if ($search !== '') { $parts = []; foreach (array_keys($fields) as $col) { $parts[] = '`' . $col . '` LIKE ?'; $params[] = '%' . $search . '%'; } $sql .= ' WHERE ' . implode(' OR ', $parts); }
    $sql .= ' ORDER BY ' . implode(',', array_map(fn($k) => '`' . $k . '`', $pk)) . ' LIMIT 300';
    $stmt = $pdo->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>HarborWing Air CRUD</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header><a class="brand" href="index.php">✈ HarborWing Air</a><span>Reservation Management</span></header>
<div class="layout"><aside><h3>Modules</h3>
<?php foreach ($catalog as $key => $item): ?><a class="navlink <?php echo $table === $key ? 'active' : ''; ?>" href="<?php echo e(urlFor($key)); ?>"><?php echo e($item['title']); ?></a><?php endforeach; ?>
<a class="navlink" href="relations.php">Reservation Links</a><a class="navlink" href="reservation_details.php">Reservation Details</a><a class="navlink" href="reference.php">Calendar / Reference</a></aside>
<main><?php showFlash(); ?>
<?php if ($table === '' || !isset($catalog[$table])): ?>
<section class="hero"><p class="eyebrow">CRUD APPLICATION</p><h1>HarborWing Air</h1><p>Manage passengers, reservations, payments, airports, and flight information using forms instead of writing SQL.</p></section>
<div class="cards"><?php foreach ($catalog as $key => $item): $count = (int)$pdo->query('SELECT COUNT(*) FROM `' . $key . '`')->fetchColumn(); ?><a class="card" href="<?php echo e(urlFor($key)); ?>"><span><?php echo e($item['title']); ?></span><strong><?php echo number_format($count); ?></strong><small>records · Open module →</small></a><?php endforeach; ?></div>
<section class="panel"><h2>Getting started</h2><ol><li>Use your existing Midterm database.</li><li>Configure <code>config.php</code> using <code>config.example.php</code>.</li><li>Use the modules on the left to create, view, update, and delete records.</li><li>Use Reservation Links to attach or detach payments and flight legs without deleting those records.</li></ol></section>
<?php else: ?>
<div class="pagehead"><div><p class="eyebrow">DATA MANAGEMENT</p><h1><?php echo e($meta['title']); ?></h1><p>Showing up to 300 records.</p></div><a class="button" href="<?php echo e(urlFor($table, ['action'=>'create'])); ?>#editor">+ Add record</a></div>
<?php if ($action === 'create' || $action === 'edit'): ?>
<section class="panel" id="editor"><h2><?php echo $action === 'edit' ? 'Edit record' : 'Create record'; ?></h2><form method="post" action="<?php echo e(urlFor($table, ['action'=>'save'])); ?>" class="formgrid"><input type="hidden" name="csrf" value="<?php echo e(csrfToken()); ?>"><input type="hidden" name="mode" value="<?php echo $action === 'edit' ? 'edit' : 'create'; ?>">
<?php foreach ($fields as $col => $def): $type = $def[1]; $value = $editRow[$col] ?? ''; ?>
<?php if (str_starts_with($type, 'fk:')): $parts = explode(':', $type); $ft = $parts[1]; $fpk = $parts[2]; $labelCols = explode(',', $parts[3]); $opts = $pdo->query('SELECT * FROM `' . $ft . '` ORDER BY `' . $fpk . '` LIMIT 1000')->fetchAll(); ?>
<label><?php echo e($def[0]); ?><?php if ($def[2]) echo '<span class="required">*</span>'; ?><select name="<?php echo e($col); ?>" <?php echo $def[2] ? 'required' : ''; ?>><option value="">Choose...</option><?php foreach ($opts as $o): $lbl = implode(' ', array_map(fn($c) => $o[$c] ?? '', $labelCols)); ?><option value="<?php echo e($o[$fpk]); ?>" <?php echo ((string)$value === (string)$o[$fpk]) ? 'selected' : ''; ?>><?php echo e($o[$fpk] . ' — ' . $lbl); ?></option><?php endforeach; ?></select></label>
<?php elseif ($type === 'textarea'): ?><label class="wide"><?php echo e($def[0]); ?><textarea name="<?php echo e($col); ?>" rows="3" <?php echo $def[2] ? 'required' : ''; ?>><?php echo e($value); ?></textarea></label>
<?php else: $step = null; if (str_starts_with($type, 'number:')) { $step = substr($type, 7); $type = 'number'; } if ($type === 'datetime-local' && $value) $value = str_replace(' ', 'T', substr((string)$value, 0, 16)); ?>
<label><?php echo e($def[0]); ?><?php if ($def[2]) echo '<span class="required">*</span>'; ?><input type="<?php echo e($type); ?>" name="<?php echo e($col); ?>" value="<?php echo e($value); ?>" <?php if ($step) echo 'step="' . e($step) . '"'; ?> <?php echo $def[2] ? 'required' : ''; ?>></label>
<?php endif; endforeach; ?>
<?php if ($action === 'edit') foreach ($pk as $key): ?><input type="hidden" name="__pk_<?php echo e($key); ?>" value="<?php echo e($editRow[$key]); ?>"><?php endforeach; ?>
<div class="wide actions"><button class="button" type="submit">Save record</button><a class="button secondary" href="<?php echo e(urlFor($table)); ?>">Cancel</a></div></form></section>
<?php endif; ?>
<section class="panel"><form method="get" class="inlineform searchform"><input type="hidden" name="table" value="<?php echo e($table); ?>"><label>Search this module<input type="search" name="q" value="<?php echo e($search); ?>" placeholder="Search visible fields..."></label><button class="button" type="submit">Search</button><a class="button secondary" href="<?php echo e(urlFor($table)); ?>">Clear</a></form><div class="tablewrap"><table><thead><tr><?php foreach (array_keys($fields) as $col): ?><th><?php echo e($fields[$col][0]); ?></th><?php endforeach; ?><th>Actions</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><?php foreach ($fields as $col => $def): ?><td><?php echo e($r[$col] ?? '—'); ?></td><?php endforeach; ?><td class="rowactions"><a href="<?php echo e(urlFor($table, array_merge(['action'=>'edit'], array_intersect_key($r, array_flip($pk))))); ?>#editor">Edit</a><form method="post" action="<?php echo e(urlFor($table, ['action'=>'delete'])); ?>" onsubmit="return confirm('Delete this record? If another record references it, deletion will be blocked safely.');"><input type="hidden" name="csrf" value="<?php echo e(csrfToken()); ?>"><?php foreach ($pk as $key): ?><input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($r[$key]); ?>"><?php endforeach; ?><button class="textbutton danger" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="<?php echo count($fields)+1; ?>" class="empty">No records yet. Use “Add record” to create one.</td></tr><?php endif; ?></tbody></table></div></section>
<?php endif; ?></main></div><footer>HarborWing Air · Training CRUD app · Use synthetic/demo data only</footer></body></html>
