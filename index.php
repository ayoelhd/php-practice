<?php
// Day 10 Mini Project: Student List (input + data display)
session_start();

$dataFile = __DIR__ . '/data/students.json';
$courses  = ['AI', 'Cloud Computing', 'DevOps', 'Information Security', 'Data Science', 'Software Engineering'];

// ---------- Helper functions ----------

// Escape output so user input can't inject HTML/JS
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Read all students from the JSON file
function loadStudents(string $file): array
{
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    if ($json === false) {
        throw new RuntimeException('Could not read the data file.');
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

// Write all students to the JSON file
function saveStudents(string $file, array $students): void
{
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
        throw new RuntimeException('Could not create the data folder.');
    }
    $json = json_encode(array_values($students), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($file, $json) === false) {
        throw new RuntimeException('Could not write to the data file.');
    }
}

// ---------- Handle form submissions (POST) ----------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        $students = loadStudents($dataFile);

        if ($action === 'add') {
            $name   = trim($_POST['name'] ?? '');
            $email  = trim($_POST['email'] ?? '');
            $course = $_POST['course'] ?? '';
            $errors = [];

            // Validation
            if (mb_strlen($name) < 2) {
                $errors[] = 'Name must be at least 2 characters.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }
            if (!in_array($course, $courses, true)) {
                $errors[] = 'Please choose a course from the list.';
            }

            if ($errors) {
                // Keep errors and old input in the session for the next page load
                $_SESSION['errors'] = $errors;
                $_SESSION['old']    = ['name' => $name, 'email' => $email, 'course' => $course];
            } else {
                $students[] = [
                    'id'     => uniqid(),
                    'name'   => $name,
                    'email'  => $email,
                    'course' => $course,
                    'added'  => date('Y-m-d H:i'),
                ];
                saveStudents($dataFile, $students);
                $_SESSION['success'] = "Student \"$name\" was added.";
            }
        }

        if ($action === 'delete') {
            $id = $_POST['id'] ?? '';
            $students = array_filter($students, fn($s) => $s['id'] !== $id);
            saveStudents($dataFile, $students);
            $_SESSION['success'] = 'Student deleted.';
        }
    } catch (RuntimeException $ex) {
        $_SESSION['errors'] = [$ex->getMessage()];
    }

    // Redirect so refreshing the page doesn't resubmit the form
    header('Location: index.php');
    exit;
}

// ---------- Prepare data for display (GET) ----------

// Take messages out of the session (they show once, then disappear)
$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
$old     = $_SESSION['old']     ?? ['name' => '', 'email' => '', 'course' => ''];
unset($_SESSION['errors'], $_SESSION['success'], $_SESSION['old']);

try {
    $students = loadStudents($dataFile);
} catch (RuntimeException $ex) {
    $students = [];
    $errors[] = $ex->getMessage();
}

// Simple search using GET: index.php?q=ahmed
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $students = array_filter($students, fn($s) =>
        stripos($s['name'], $q) !== false || stripos($s['email'], $q) !== false
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student List</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main>
    <h1>Student List</h1>

    <?php if ($success): ?>
        <div class="alert success"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="card">
        <h2>Add a student</h2>
        <form method="post" action="index.php">
            <input type="hidden" name="action" value="add">

            <label>Name
                <input type="text" name="name" value="<?= e($old['name']) ?>" required>
            </label>

            <label>E-mail
                <input type="email" name="email" value="<?= e($old['email']) ?>" required>
            </label>

            <label>Course
                <select name="course" required>
                    <option value="">-- choose --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= e($c) ?>" <?= $old['course'] === $c ? 'selected' : '' ?>>
                            <?= e($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button type="submit">Add</button>
        </form>
    </section>

    <section class="card">
        <h2>Students (<?= count($students) ?>)</h2>

        <form method="get" action="index.php" class="search">
            <input type="text" name="q" placeholder="Search by name or email" value="<?= e($q) ?>">
            <button type="submit">Search</button>
        </form>

        <?php if (!$students): ?>
            <p class="empty">No students found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Course</th><th>Added</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= e($s['name']) ?></td>
                        <td><?= e($s['email']) ?></td>
                        <td><?= e($s['course']) ?></td>
                        <td><?= e($s['added']) ?></td>
                        <td>
                            <form method="post" action="index.php" onsubmit="return confirm('Delete this student?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e($s['id']) ?>">
                                <button type="submit" class="danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
