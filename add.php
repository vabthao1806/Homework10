<?php
/**
 * add.php - Create a new contact
 * Contact Manager - INS3064 Homework 10
 * Student: Nguyễn Thị Thảo Vân (24070557)
 */

require_once __DIR__ . '/config.php';

$values = ['name' => '', 'email' => '', 'phone' => '', 'group_name' => 'General', 'notes' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = validateContact($_POST);
    $values = $result['data'];
    $errors = $result['errors'];

    if (empty($errors)) {
        try {
            $stmt = getPDO()->prepare(
                'INSERT INTO contacts (name, email, phone, group_name, notes)
                 VALUES (:name, :email, :phone, :group_name, :notes)'
            );
            $stmt->execute($values);

            setFlash('success', 'Contact added successfully!');
            redirect('index.php');
        } catch (PDOException $e) {
            // 23000 = duplicate key (the UNIQUE email constraint in the database)
            error_log('Add contact failed: ' . $e->getMessage());
            $errors[] = $e->getCode() === '23000'
                ? 'This email address is already used by another contact.'
                : 'Could not save the contact. Please try again.';
        }
    }
}

renderHeader('Add Contact');
?>

<div class="page-title">
    <h1>Add New Contact</h1>
</div>

<div class="card">
    <?php renderContactForm($values, $errors, 'add.php', 'Save Contact'); ?>
</div>

<?php renderFooter(); ?>
