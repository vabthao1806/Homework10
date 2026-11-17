<?php
/**
 * delete.php - Confirm, then delete a contact
 * GET  shows the confirmation page (no change to the database).
 * POST performs the deletion.
 * Contact Manager - INS3064 Homework 10
 * Student: Nguyễn Thị Thảo Vân (24070557)
 */

require_once __DIR__ . '/config.php';

$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$contact = $id ? getContactById($id) : null;

if ($contact === null) {
    renderNotFound();   // prints the page and stops
}

// Deletion happens only on POST, so a link cannot delete a contact by itself
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = getPDO()->prepare('DELETE FROM contacts WHERE id = :id');
        $stmt->execute(['id' => $id]);

        setFlash('success', 'Contact "' . $contact['name'] . '" was deleted.');
    } catch (PDOException $e) {
        error_log('Delete contact failed: ' . $e->getMessage());
        setFlash('error', 'Could not delete the contact. Please try again.');
    }
    redirect('index.php');
}

renderHeader('Delete Contact');
?>

<div class="page-title">
    <h1>Delete Contact</h1>
</div>

<div class="card confirm-card">
    <p class="confirm-question">Are you sure you want to delete this contact?</p>

    <table class="details">
        <tr>
            <th>Name</th>
            <td><?= h($contact['name']) ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?= h($contact['email'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>Phone</th>
            <td><?= h($contact['phone'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>Group</th>
            <td><?= h($contact['group_name'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>Notes</th>
            <td><?= h($contact['notes'] ?: '-') ?></td>
        </tr>
    </table>

    <p class="warning-text">This action cannot be undone.</p>

    <form method="post" action="delete.php?id=<?= (int) $id ?>" class="form-actions">
        <button type="submit" class="btn btn-danger">Yes, delete</button>
        <a href="index.php" class="btn">Cancel</a>
    </form>
</div>

<?php renderFooter(); ?>
