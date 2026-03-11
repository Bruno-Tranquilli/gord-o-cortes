<?php
require __DIR__ . '/app/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET' && $action === 'state') {
        $user = current_user();
        if ($user) {
            $user['is_admin'] = ($user['role'] ?? 'client') === 'admin';
        }
        json_response(['ok' => true, 'user' => $user]);
    }

    if ($method === 'POST' && $action === 'register') {
        $data = input_json();
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($name === '' || !is_valid_email($email) || strlen($password) < 6) {
            json_response(['ok' => false, 'message' => 'Preencha nome, email vÃ¡lido e senha com 6+ caracteres.'], 422);
        }

        $pdo = db();
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            json_response(['ok' => false, 'message' => 'Este email jÃ¡ estÃ¡ cadastrado.'], 409);
        }

        $role = 'client';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, active) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$name, $email, $hash, $role]);

        $id = (int) $pdo->lastInsertId();
        $_SESSION['user'] = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ];

        json_response(['ok' => true, 'user' => $_SESSION['user']]);
    }

    if ($method === 'POST' && $action === 'login') {
        $data = input_json();
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if (!is_valid_email($email) || $password === '') {
            json_response(['ok' => false, 'message' => 'Credenciais invÃ¡lidas.'], 422);
        }

        $pdo = db();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role, active FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !(bool) $user['active']) {
            json_response(['ok' => false, 'message' => 'UsuÃ¡rio nÃ£o encontrado ou inativo.'], 404);
        }

        $storedHash = (string) $user['password_hash'];
        $passwordOk = false;

        if (str_starts_with($storedHash, '$2y$') || str_starts_with($storedHash, '$argon2')) {
            $passwordOk = password_verify($password, $storedHash);
        } else {
            $passwordOk = hash_equals($storedHash, $password);
            if ($passwordOk) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upgrade = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $upgrade->execute([$newHash, $user['id']]);
            }
        }

        if (!$passwordOk) {
            json_response(['ok' => false, 'message' => 'Senha incorreta.'], 401);
        }

        $role = ($user['role'] === 'admin') ? 'admin' : 'client';

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $role,
        ];

        json_response(['ok' => true, 'user' => $_SESSION['user']]);
    }

    if ($method === 'POST' && $action === 'logout') {
        $_SESSION = [];
        session_destroy();
        json_response(['ok' => true]);
    }

    if ($method === 'GET' && $action === 'cuts') {
        $stmt = db()->query('SELECT id, name, description, duration_minutes, price, image_url, active FROM haircuts ORDER BY id DESC');
        $cuts = $stmt->fetchAll();
        json_response(['ok' => true, 'cuts' => $cuts]);
    }

    if ($method === 'POST' && $action === 'book') {
        $user = require_login();
        $data = input_json();

        $haircutId = (int) ($data['haircut_id'] ?? 0);
        $date = trim((string) ($data['appointment_date'] ?? ''));
        $time = trim((string) ($data['appointment_time'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));

        if ($haircutId < 1 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
            json_response(['ok' => false, 'message' => 'Dados de agendamento invÃ¡lidos.'], 422);
        }

        $pdo = db();
        $checkCut = $pdo->prepare('SELECT id FROM haircuts WHERE id = ? AND active = 1 LIMIT 1');
        $checkCut->execute([$haircutId]);
        if (!$checkCut->fetch()) {
            json_response(['ok' => false, 'message' => 'Corte indisponÃ­vel.'], 404);
        }

        $checkSlot = $pdo->prepare('SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status IN ("scheduled", "confirmed") LIMIT 1');
        $checkSlot->execute([$date, $time]);
        if ($checkSlot->fetch()) {
            json_response(['ok' => false, 'message' => 'HorÃ¡rio indisponÃ­vel, escolha outro.'], 409);
        }

        $stmt = $pdo->prepare('INSERT INTO appointments (user_id, haircut_id, appointment_date, appointment_time, notes, status) VALUES (?, ?, ?, ?, ?, "scheduled")');
        $stmt->execute([$user['id'], $haircutId, $date, $time, $notes]);

        json_response(['ok' => true, 'message' => 'HorÃ¡rio agendado com sucesso.']);
    }

    if ($method === 'GET' && $action === 'appointments') {
        $user = require_login();
        $pdo = db();

        if (($user['role'] ?? 'client') === 'admin') {
            $stmt = $pdo->query('SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes, u.name AS user_name, u.email, h.name AS haircut_name FROM appointments a INNER JOIN users u ON u.id = a.user_id INNER JOIN haircuts h ON h.id = a.haircut_id ORDER BY a.appointment_date DESC, a.appointment_time DESC');
            $appointments = $stmt->fetchAll();
        } else {
            $stmt = $pdo->prepare('SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.notes, h.name AS haircut_name FROM appointments a INNER JOIN haircuts h ON h.id = a.haircut_id WHERE a.user_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC');
            $stmt->execute([$user['id']]);
            $appointments = $stmt->fetchAll();
        }

        json_response(['ok' => true, 'appointments' => $appointments]);
    }

    if ($method === 'POST' && $action === 'admin-cut-save') {
        require_admin();
        $data = input_json();

        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $duration = (int) ($data['duration_minutes'] ?? 30);
        $price = (float) ($data['price'] ?? 0);
        $imageUrl = trim((string) ($data['image_url'] ?? ''));
        $active = !empty($data['active']) ? 1 : 0;

        if ($name === '' || $price <= 0 || $duration < 5) {
            json_response(['ok' => false, 'message' => 'Nome, preÃ§o e duraÃ§Ã£o sÃ£o obrigatÃ³rios.'], 422);
        }

        $pdo = db();

        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE haircuts SET name = ?, description = ?, duration_minutes = ?, price = ?, image_url = ?, active = ? WHERE id = ?');
            $stmt->execute([$name, $description, $duration, $price, $imageUrl, $active, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO haircuts (name, description, duration_minutes, price, image_url, active) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $description, $duration, $price, $imageUrl, $active]);
        }

        json_response(['ok' => true, 'message' => 'Corte salvo com sucesso.']);
    }

    if ($method === 'POST' && $action === 'admin-cut-delete') {
        require_admin();
        $data = input_json();
        $id = (int) ($data['id'] ?? 0);

        if ($id < 1) {
            json_response(['ok' => false, 'message' => 'ID invÃ¡lido.'], 422);
        }

        $stmt = db()->prepare('DELETE FROM haircuts WHERE id = ?');
        $stmt->execute([$id]);

        json_response(['ok' => true]);
    }

    if ($method === 'GET' && $action === 'admin-users') {
        require_admin();
        $stmt = db()->query('SELECT id, name, email, role, active, created_at FROM users ORDER BY id DESC');
        json_response(['ok' => true, 'users' => $stmt->fetchAll()]);
    }

    if ($method === 'POST' && $action === 'admin-user-save') {
        $admin = require_admin();
        $data = input_json();

        $id = (int) ($data['id'] ?? 0);
        $role = ($data['role'] ?? 'client') === 'admin' ? 'admin' : 'client';
        $active = !empty($data['active']) ? 1 : 0;

        if ($id < 1) {
            json_response(['ok' => false, 'message' => 'UsuÃ¡rio invÃ¡lido.'], 422);
        }

        if ($admin['id'] === $id) {
            $active = 1;
        }

        $stmt = db()->prepare('UPDATE users SET role = ?, active = ? WHERE id = ?');
        $stmt->execute([$role, $active, $id]);

        json_response(['ok' => true]);
    }

    if ($method === 'POST' && $action === 'admin-appointment-status') {
        require_admin();
        $data = input_json();

        $id = (int) ($data['id'] ?? 0);
        $status = (string) ($data['status'] ?? 'scheduled');
        $allowed = ['scheduled', 'confirmed', 'done', 'cancelled'];

        if ($id < 1 || !in_array($status, $allowed, true)) {
            json_response(['ok' => false, 'message' => 'Dados invÃ¡lidos.'], 422);
        }

        $stmt = db()->prepare('UPDATE appointments SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);

        json_response(['ok' => true]);
    }

    json_response(['ok' => false, 'message' => 'Rota nÃ£o encontrada.'], 404);
} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
}

