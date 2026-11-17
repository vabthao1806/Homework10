<?php
/**
 * index.php - Contact list with search, sorting, totals and actions
 * Contact Manager - INS3064 Homework 10
 * Student: Nguyễn Thị Thảo Vân (24070557)
 */

require_once __DIR__ . '/config.php';

// Sortable columns. The URL can only pick one of these fixed keys,
// so user input never reaches the SQL text directly.
$sortColumns = [
    'name'    => 'name',
    'email'   => 'email',
    'phone'   => 'phone',
    'group'   => 'group_name',
    'created' => 'created_at',
];

$sortKey = isset($_GET['sort']) && is_string($_GET['sort']) ? $_GET['sort'] : 'name';
if (!array_key_exists($sortKey, $sortColumns)) {
    $sortKey = 'name';
}
$sortDir = (isset($_GET['dir']) && $_GET['dir'] === 'desc') ? 'DESC' : 'ASC';
$search  = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';

/**
 * Build a sort link for a column header.
 * Clicking the active column flips the direction; the search term is kept.
 */
function sortLink(string $key, string $label, string $currentKey, string $currentDir, string $search): string
{
    $isCurrent = ($key === $currentKey);
    $nextDir   = ($isCurrent && $currentDir === 'ASC') ? 'desc' : 'asc';
    $arrow     = $isCurrent ? ($currentDir === 'ASC' ? ' ▲' : ' ▼') : '';
    $query     = http_build_query(['sort' => $key, 'dir' => $nextDir, 'q' => $search]);

    return '<a href="index.php?' . h($query) . '">' . h($label . $arrow) . '</a>';
}

$pdo = getPDO();

// Total number of contacts in the database
$total = (int) $pdo->query('SELECT COUNT(*) FROM contacts')->fetchColumn();

// Build the list query with prepared statements
$sql    = 'SELECT id, name, email, phone, group_name, created_at FROM contacts';
$params = [];

if ($search !== '') {
    // Escape LIKE wildcards in the user's text, then wrap it in %...%
    $likePattern = '%' . addcslashes($search, '%_\\') . '%';
    $sql .= ' WHERE name LIKE :search_name OR email LIKE :search_email';
    $params = ['search_name' => $likePattern, 'search_email' => $likePattern];
}

// Column and direction come from the whitelists above
$sql .= ' ORDER BY ' . $sortColumns[$sortKey] . ' ' . $sortDir . ', id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contacts  = $stmt->fetchAll();
$shownCount = count($contacts);

renderHeader('Contacts');
?>

<div class="page-title">
    <h1>Contacts</h1>
    <p class="total">
        Total contacts: <strong><?= $total ?></strong>
        <?php if ($search !== ''): ?>
            &middot; Found <strong><?= $shownCount ?></strong> result(s) for "<?= h($search) ?>"
        <?php endif; ?>
    </p>
</div>

<form method="get" action="index.php" class="search-form">
    <input type="search" name="q" value="<?= h($search) ?>" placeholder="Search by name or email">
    <input type="hidden" name="sort" value="<?= h($sortKey) ?>">
    <input type="hidden" name="dir" value="<?= h(strtolower($sortDir)) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search !== ''): ?>
        <a href="index.php" class="btn">Clear</a>
    <?php endif; ?>
</form>

<div class="table-wrap">
    <table class="contacts-table">
        <thead>
            <tr>
                <th><?= sortLink('name', 'Name', $sortKey, $sortDir, $search) ?></th>
                <th><?= sortLink('email', 'Email', $sortKey, $sortDir, $search) ?></th>
                <th><?= sortLink('phone', 'Phone', $sortKey, $sortDir, $search) ?></th>
                <th><?= sortLink('group', 'Group', $sortKey, $sortDir, $search) ?></th>
                <th><?= sortLink('created', 'Created At', $sortKey, $sortDir, $search) ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($contacts)): ?>
                <tr>
                    <td colspan="6" class="empty">No contacts found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($contacts as $contact): ?>
                    <tr>
                        <td><?= h($contact['name']) ?></td>
                        <td><?= h($contact['email']) ?></td>
                        <td><?= h($contact['phone'] ?: '-') ?></td>
                        <td>
                            <span class="badge badge-<?= h(strtolower($contact['group_name'])) ?>">
                                <?= h($contact['group_name']) ?>
                            </span>
                        </td>
                        <td><?= h(date('d/m/Y H:i', strtotime($contact['created_at']))) ?></td>
                        <td class="actions">
                            <a href="edit.php?id=<?= (int) $contact['id'] ?>">Edit</a>
                            |
                            <a href="delete.php?id=<?= (int) $contact['id'] ?>" class="danger">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php renderFooter(); ?>
