<?php
require_once __DIR__ . '/auth.php';

/** Escape for safe HTML output. */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/* -----------------------------------------------------------
 * CSRF protection
 * ----------------------------------------------------------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Invalid or expired form submission (CSRF check failed). Please go back and try again.');
    }
}

/* -----------------------------------------------------------
 * Flash messages (one-time notices shown after redirect)
 * ----------------------------------------------------------- */
function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/* -----------------------------------------------------------
 * Formatting helpers
 * ----------------------------------------------------------- */
function format_dt(?string $mysqlDatetime, string $format = 'd M Y, h:i A'): string {
    if (!$mysqlDatetime) return '—';
    $ts = strtotime($mysqlDatetime);
    return $ts ? date($format, $ts) : '—';
}

/* -----------------------------------------------------------
 * Photo upload handling
 * A single check-in or check-out submission can include several
 * photo files (input name="photos[]" multiple).
 * ----------------------------------------------------------- */
function save_session_photos(int $sessionId, string $type, array $filesInput, PDO $pdo): array {
    $errors = [];
    if (empty($filesInput['name'][0] ?? null)) {
        return $errors; // no photos submitted is allowed (comment-only is fine)
    }

    $count = count($filesInput['name']);
    if ($count > MAX_PHOTOS_PER_SUBMIT) {
        $errors[] = 'You can upload a maximum of ' . MAX_PHOTOS_PER_SUBMIT . ' photos at once.';
        return $errors;
    }

    $targetDir = UPLOAD_DIR . $sessionId . '/' . $type . '/';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        $errors[] = 'Could not create upload folder on the server.';
        return $errors;
    }

    $insertStmt = $pdo->prepare(
        'INSERT INTO session_photos (session_id, photo_path, type) VALUES (?, ?, ?)'
    );

    for ($i = 0; $i < $count; $i++) {
        if ($filesInput['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($filesInput['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload error for "' . e($filesInput['name'][$i]) . '".';
            continue;
        }
        if ($filesInput['size'][$i] > MAX_PHOTO_SIZE) {
            $errors[] = e($filesInput['name'][$i]) . ' is too large (max ' . (MAX_PHOTO_SIZE / 1024 / 1024) . 'MB).';
            continue;
        }

        $tmpPath  = $filesInput['tmp_name'][$i];
        $mimeType = mime_content_type($tmpPath) ?: '';
        if (!in_array($mimeType, ALLOWED_PHOTO_TYPES, true)) {
            $errors[] = e($filesInput['name'][$i]) . ' is not a supported image type.';
            continue;
        }

        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'jpg',
        };
        $filename = bin2hex(random_bytes(12)) . '.' . $ext;
        $destPath = $targetDir . $filename;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            $errors[] = 'Failed to save ' . e($filesInput['name'][$i]) . '.';
            continue;
        }

        // Store a relative path (from the app root) for later display.
        $relativePath = 'uploads/sessions/' . $sessionId . '/' . $type . '/' . $filename;
        $insertStmt->execute([$sessionId, $relativePath, $type]);
    }

    return $errors;
}

/* -----------------------------------------------------------
 * Core business rules for check-in / check-out
 * ----------------------------------------------------------- */

/** Returns the user's currently active (checked-in, not yet checked-out) session for a toilet, or null. */
function get_active_session(PDO $pdo, int $userId, int $toiletId): ?array {
    $stmt = $pdo->prepare(
        "SELECT * FROM toilet_sessions
         WHERE user_id = ? AND toilet_id = ? AND status = 'active'
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([$userId, $toiletId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Creates a new check-in session. A user cannot check in to the same
 * toilet twice while an earlier check-in there is still active.
 * Returns ['ok' => bool, 'session_id' => int|null, 'error' => string|null]
 */
function do_checkin(PDO $pdo, int $userId, int $toiletId, string $comment): array {
    if (get_active_session($pdo, $userId, $toiletId)) {
        return ['ok' => false, 'session_id' => null, 'error' => 'You already have an active check-in for this toilet. Please check out first.'];
    }
    $stmt = $pdo->prepare(
        "INSERT INTO toilet_sessions (toilet_id, user_id, checkin_time, checkin_comment, status)
         VALUES (?, ?, NOW(), ?, 'active')"
    );
    $stmt->execute([$toiletId, $userId, $comment !== '' ? $comment : null]);
    return ['ok' => true, 'session_id' => (int)$pdo->lastInsertId(), 'error' => null];
}

/**
 * Completes (checks out) an existing active session. A check-out is only
 * permitted against a session that is currently active for this user and
 * this toilet — enforced here regardless of what the form claims.
 * Returns ['ok' => bool, 'session_id' => int|null, 'error' => string|null]
 */
function do_checkout(PDO $pdo, int $userId, int $toiletId, string $comment): array {
    $session = get_active_session($pdo, $userId, $toiletId);
    if (!$session) {
        return ['ok' => false, 'session_id' => null, 'error' => 'No active check-in was found for this toilet, so you cannot check out. Please check in first.'];
    }
    $stmt = $pdo->prepare(
        "UPDATE toilet_sessions
         SET checkout_time = NOW(), checkout_comment = ?, status = 'completed'
         WHERE id = ? AND status = 'active'"
    );
    $stmt->execute([$comment !== '' ? $comment : null, $session['id']]);

    if ($stmt->rowCount() === 0) {
        // Session was already closed by a concurrent request; treat as an error, not a silent overwrite.
        return ['ok' => false, 'session_id' => null, 'error' => 'This session was already checked out.'];
    }
    return ['ok' => true, 'session_id' => (int)$session['id'], 'error' => null];
}

/** Toilets assigned to a given student/user. */
function get_assigned_toilets(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare(
        "SELECT t.* FROM toilets t
         JOIN user_toilets ut ON ut.toilet_id = t.id
         WHERE ut.user_id = ? AND t.status = 'active'
         ORDER BY t.code"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/** Confirms a student is actually assigned to a toilet (defence in depth against URL tampering). */
function user_is_assigned_to_toilet(PDO $pdo, int $userId, int $toiletId): bool {
    $stmt = $pdo->prepare('SELECT 1 FROM user_toilets WHERE user_id = ? AND toilet_id = ?');
    $stmt->execute([$userId, $toiletId]);
    return (bool)$stmt->fetchColumn();
}

/** All photos for a session, split by type. */
function get_session_photos(PDO $pdo, int $sessionId): array {
    $stmt = $pdo->prepare('SELECT * FROM session_photos WHERE session_id = ? ORDER BY id');
    $stmt->execute([$sessionId]);
    $all = $stmt->fetchAll();
    return [
        'checkin'  => array_values(array_filter($all, fn($p) => $p['type'] === 'checkin')),
        'checkout' => array_values(array_filter($all, fn($p) => $p['type'] === 'checkout')),
    ];
}
