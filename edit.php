<?php
/**
 * edit.php - Update an existing contact
 * Contact Manager - INS3064 Homework 10
 * Student: Nguyễn Thị Thảo Vân (24070557)
 */

require_once __DIR__ . '/config.php';

$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$contact = $id ? getContactById($id) : null;

if ($contact === null) {
    renderNotFound();   // prints the page and stops
}

// Pre-fill the form with the current values
$values = [
    'name'       => $contact['name'],
    'email'      => $contact['email'] ?? '',
    'phone'      => $contact['phone'] ?? '',
    'group_name' => $contact['group_name'] ?? 'General',
    'notes'      => $contact['notes'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pass the id so this contact's own email is not flagged as a duplicate
    $result = validateContact($_POST, $id);
    $values = $result['data'];
    $errors = $result['errors'];

    if (empty($errors)) {
        try {
            $stmt = getPDO()->prepare(
                'UPDATE contacts
                 SET name = :name, email = :email, phone = :phone,
                     group_name = :group_name, notes = :notes
                 WHERE id = :id'
            );
            $stmt->execute($values + ['id' => $id]);

            setFlash('success', 'Contact updated successfully!');
            redirect('index.php');
        } catch (PDOException $e) {
            error_log('Update contact failed: ' . $e->getMessage());
            $errors[] = $e->getCode() === '23000'
                ? 'This email address is already used by another contact.'
                : 'Could not update the contact. Please try again.';
        }
    }
}

renderHeader('Edit Contact');
?>

<div class="page-title">
    <h1>Edit Contact</h1>
</div>

<div class="card">
    <?php renderContactForm($values, $errors, 'edit.php?id=' . (int) $id, 'Update Contact'); ?>
</div>

<?php renderFooter(); ?>
