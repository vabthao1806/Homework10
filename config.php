<?php
/**
 * config.php - Database connection, shared helpers and page layout
 * Contact Manager - INS3064 Homework 10
 * Student: Nguyễn Thị Thảo Vân (24070557)
 *
 * Included by every page. Holds the PDO connection, validation,
 * flash messages, the contact form and the common header/footer.
 */

session_start();

// ---------- Database settings (XAMPP defaults) ----------
define('DB_HOST', 'localhost');
define('DB_NAME', 'contact_manager');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Allowed values for the Group dropdown
define('GROUP_OPTIONS', ['Family', 'Friends', 'Work', 'Other', 'General']);

// ============================================================
// Database
// ============================================================

/** Return the shared PDO connection (created once per request). */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection failed. Check config.php and make sure MySQL is running in XAMPP.');
        }
    }

    return $pdo;
}

/** Fetch one contact by id, or null if it does not exist. */
function getContactById(int $id): ?array
{
    $stmt = getPDO()->prepare('SELECT * FROM contacts WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $contact = $stmt->fetch();

    return $contact ?: null;
}

/** True if the email is already used (optionally ignoring one contact id). */
function emailExists(string $email, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM contacts WHERE email = :email';
    $params = ['email' => $email];

    if ($ignoreId !== null) {
        $sql .= ' AND id <> :id';
        $params['id'] = $ignoreId;
    }

    $stmt = getPDO()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
}

// ============================================================
// Security and input helpers
// ============================================================

/** Escape any value for safe HTML output (prevents XSS). */
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Read a text field from the request as a trimmed string ('' if missing). */
function textValue(array $input, string $key): string
{
    return isset($input[$key]) && is_string($input[$key]) ? trim($input[$key]) : '';
}

/**
 * Validate a contact submitted from add.php or edit.php.
 * $ignoreId is the contact being edited, so its own email is not a duplicate.
 * Returns ['data' => cleaned values, 'errors' => list of messages].
 */
function validateContact(array $input, ?int $ignoreId = null): array
{
    $data = [
        'name'       => textValue($input, 'name'),
        'email'      => textValue($input, 'email'),
        'phone'      => textValue($input, 'phone'),
        'group_name' => textValue($input, 'group_name') ?: 'General',
        'notes'      => textValue($input, 'notes'),
    ];

    $errors = [];

    // Name: required, max 100 characters
    if ($data['name'] === '') {
        $errors[] = 'Name is required.';
    } elseif (mb_strlen($data['name']) > 100) {
        $errors[] = 'Name must be 100 characters or fewer.';
    }

    // Email: required, valid format, unique
    if ($data['email'] === '') {
        $errors[] = 'Email is required.';
    } elseif (mb_strlen($data['email']) > 150) {
        $errors[] = 'Email must be 150 characters or fewer.';
    } elseif (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Please enter a valid email address (for example, name@example.com).';
    } elseif (emailExists($data['email'], $ignoreId)) {
        $errors[] = 'This email address is already used by another contact.';
    }

    // Phone: optional, max 20 characters, digits and common symbols only
    if (mb_strlen($data['phone']) > 20) {
        $errors[] = 'Phone number must be 20 characters or fewer.';
    } elseif ($data['phone'] !== '' && !preg_match('/^[0-9+\-() ]+$/', $data['phone'])) {
        $errors[] = 'Phone number may only contain digits, spaces, +, -, ( and ).';
    }

    // Group: must be one of the dropdown options
    if (!in_array($data['group_name'], GROUP_OPTIONS, true)) {
        $errors[] = 'Please choose a valid group.';
    }

    return ['data' => $data, 'errors' => $errors];
}

// ============================================================
// Flash messages (stored in $_SESSION, shown once)
// ============================================================

/** Store a one-time message. $type is 'success', 'error' or 'warning'. */
function setFlash(string $type, string $message): void
{
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/** Show the stored message (if any), then clear it so it appears only once. */
function renderFlash(): void
{
    if (!isset($_SESSION['message'])) {
        return;
    }

    $allowedTypes = ['success', 'error', 'warning'];
    $type = $_SESSION['message_type'] ?? 'success';
    if (!in_array($type, $allowedTypes, true)) {
        $type = 'success';
    }

    echo '<div class="flash flash-' . h($type) . '">' . h($_SESSION['message']) . '</div>';

    unset($_SESSION['message'], $_SESSION['message_type']);
}

/** Redirect and stop the script. */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// ============================================================
// Layout: shared header and footer
// ============================================================

function renderHeader(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($title) ?> - Contact Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="brand">Contact Manager</a>
        <nav>
            <a href="index.php">All Contacts</a>
            <a href="add.php" class="btn btn-primary btn-small">+ Add New Contact</a>
        </nav>
    </div>
</header>
<main class="container">
    <?php renderFlash(); ?>
    <?php
}

function renderFooter(): void
{
    ?>
</main>
<footer class="site-footer">
    <p>INS3064 - Homework 10 | Contact Manager</p>
</footer>
</body>
</html>
    <?php
}

/** Shared "Contact not found" page, then stop the script. */
function renderNotFound(): void
{
    renderHeader('Contact not found');
    ?>
    <div class="card empty-state">
        <h2>Contact not found</h2>
        <p>The contact you are looking for does not exist or may have been deleted.</p>
        <a href="index.php" class="btn btn-primary">Back to contact list</a>
    </div>
    <?php
    renderFooter();
    exit;
}

// ============================================================
// Layout: contact form (shared by add.php and edit.php)
// ============================================================

/** Print the add/edit form with sticky values and error messages. */
function renderContactForm(array $values, array $errors, string $action, string $submitLabel): void
{
    if (!empty($errors)) {
        echo '<div class="flash flash-error"><strong>Please fix the following:</strong><ul>';
        foreach ($errors as $error) {
            echo '<li>' . h($error) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>
    <form method="post" action="<?= h($action) ?>" class="contact-form" novalidate>
        <label>
            Name <span class="required">*</span>
            <input type="text" name="name" maxlength="100" value="<?= h($values['name']) ?>">
        </label>

        <label>
            Email <span class="required">*</span>
            <input type="email" name="email" maxlength="150" value="<?= h($values['email']) ?>">
        </label>

        <label>
            Phone
            <input type="text" name="phone" maxlength="20" value="<?= h($values['phone']) ?>">
        </label>

        <label>
            Group
            <select name="group_name">
                <?php foreach (GROUP_OPTIONS as $option): ?>
                    <option value="<?= h($option) ?>"<?= $values['group_name'] === $option ? ' selected' : '' ?>>
                        <?= h($option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Notes
            <textarea name="notes" rows="4"><?= h($values['notes']) ?></textarea>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= h($submitLabel) ?></button>
            <a href="index.php" class="btn">Cancel</a>
        </div>
    </form>
    <?php
}
