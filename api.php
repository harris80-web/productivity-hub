<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

function addTask($conn) {
    $task_name = $_POST['task_name'];
    $subject = $_POST['subject'];
    $task_type = $_POST['task_type'];
    $due_date = $_POST['due_date'];
    $estimate_min = (int)$_POST['estimate_min'];

    $sql = "INSERT INTO tasks (task_name, subject, task_type, due_date, estimate_min, is_completed)
            VALUES (?, ?, ?, ?, ?, 0)";

    $stmt = $conn->prepare($sql); // FIXED

    $stmt->bind_param("ssssi", $task_name, $subject, $task_type, $due_date, $estimate_min);

    if ($stmt->execute()) {
        file_put_contents('task_log.txt', date("Y-m-d H:i:s") . " - New Task Added: $task_name\n", FILE_APPEND);
        
        echo json_encode([
            'success' => true,
            'message' => 'Task added successfully.',
            'id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error adding task: ' . $stmt->error
        ]);
    }

    $stmt->close();
}

function getTasks($conn) {
    $sql = "SELECT * FROM tasks ORDER BY is_completed ASC, due_date ASC";
    $result = $conn->query($sql);

    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }

    echo json_encode(['success' => true, 'tasks' => $tasks]);
}

function toggleComplete($conn) {
    $id = (int)$_POST['id'];
    $is_completed = (int)$_POST['is_completed'];

    $sql = "UPDATE tasks SET is_completed = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    $stmt->bind_param("ii", $is_completed, $id);

    if ($stmt->execute()) {
        $status_text = ($is_completed == 1) ? 'COMPLETED' : 'REOPENED';
        file_put_contents('task_log.txt', date("Y-m-d H:i:s") . " - Task ID $id status changed to $status_text\n", FILE_APPEND);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error toggling task: ' . $stmt->error]);
    }

    $stmt->close();
}

function updateTask($conn) {
    $id = (int)$_POST['id'];
    $task_name = $_POST['task_name'];
    $subject = $_POST['subject'];
    $task_type = $_POST['task_type'];
    $due_date = $_POST['due_date'];
    $estimate_min = (int)$_POST['estimate_min'];

    $sql = "UPDATE tasks SET 
                task_name = ?, 
                subject = ?, 
                task_type = ?, 
                due_date = ?, 
                estimate_min = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("ssssii", $task_name, $subject, $task_type, $due_date, $estimate_min, $id);

    if ($stmt->execute()) {
        file_put_contents('task_log.txt', date("Y-m-d H:i:s") . " - Task ID $id updated.\n", FILE_APPEND);

        echo json_encode(['success' => true, 'message' => 'Task updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating task: ' . $stmt->error]);
    }

    $stmt->close();
}

function deleteTask($conn) {
    $id = (int)$_POST['id'];

    $sql = "DELETE FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($sql);

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Task deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting task: ' . $stmt->error]);
    }

    $stmt->close();
}

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_task': addTask($conn); break;
        case 'get_tasks': getTasks($conn); break;
        case 'toggle_complete': toggleComplete($conn); break;
        case 'update_task': updateTask($conn); break;
        case 'delete_task': deleteTask($conn); break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No action specified.']);
}

$conn->close();

?>