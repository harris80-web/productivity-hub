<?php
require_once __DIR__ . '/db_connection.php';

header('Content-Type: application/json');

function addTask($pdo) {
    $stmt = $pdo->prepare(
        "INSERT INTO tasks (task_name, subject, task_type, due_date, estimate_min, is_completed)
         VALUES (:task_name, :subject, :task_type, :due_date, :estimate_min, FALSE)"
    );
    $stmt->execute([
        ':task_name'    => $_POST['task_name'],
        ':subject'      => $_POST['subject'],
        ':task_type'    => $_POST['task_type'],
        ':due_date'     => $_POST['due_date'],
        ':estimate_min' => (int)$_POST['estimate_min'],
    ]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
}

function getTasks($pdo) {
    $stmt = $pdo->query("SELECT * FROM tasks ORDER BY is_completed ASC, due_date ASC");
    echo json_encode(['success' => true, 'tasks' => $stmt->fetchAll()]);
}

function toggleComplete($pdo) {
    $is_completed = $_POST['is_completed'] == '1' ? 'TRUE' : 'FALSE';
    $stmt = $pdo->prepare("UPDATE tasks SET is_completed = :is_completed WHERE id = :id");
    $stmt->execute([
        ':is_completed' => $is_completed,
        ':id'           => (int)$_POST['id'],
    ]);
    echo json_encode(['success' => true]);
}

function updateTask($pdo) {
    $stmt = $pdo->prepare(
        "UPDATE tasks SET
            task_name    = :task_name,
            subject      = :subject,
            task_type    = :task_type,
            due_date     = :due_date,
            estimate_min = :estimate_min
         WHERE id = :id"
    );
    $stmt->execute([
        ':task_name'    => $_POST['task_name'],
        ':subject'      => $_POST['subject'],
        ':task_type'    => $_POST['task_type'],
        ':due_date'     => $_POST['due_date'],
        ':estimate_min' => (int)$_POST['estimate_min'],
        ':id'           => (int)$_POST['id'],
    ]);
    echo json_encode(['success' => true]);
}

function deleteTask($pdo) {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
    $stmt->execute([':id' => (int)$_POST['id']]);
    echo json_encode(['success' => true]);
}

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_task':       addTask($pdo);       break;
        case 'get_tasks':      getTasks($pdo);      break;
        case 'toggle_complete': toggleComplete($pdo); break;
        case 'update_task':    updateTask($pdo);    break;
        case 'delete_task':    deleteTask($pdo);    break;
        default: echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No action specified.']);
}
?>