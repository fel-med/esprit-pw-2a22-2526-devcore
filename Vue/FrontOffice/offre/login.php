<?php
session_start();

$id = '';
$role = '';
$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    $role = $_POST['role'] ?? '';

    if ($id === '') {
        $errors[] = 'ID is required.';
    } elseif (!ctype_digit($id)) {
        $errors[] = 'ID must be a whole number.';
    }

    if (!in_array($role, ['marque', 'createur'], true)) {
        $errors[] = 'Please select a valid role.';
    }

    if (empty($errors)) {
        require_once __DIR__ . '/../../../Controleur/utilisateurC.php';
        $controller = new UtilisateurC();
        $user = $controller->getUserByIdAndRole(intval($id), $role);
        if ($user) {
            $_SESSION['utilisateur'] = [
                'id' => $user->getId(),
                'role' => $user->getRole()
            ];
            $message = 'Login successful. Redirecting...';
            if ($role === 'marque') {
                header('Location: brand_index.php');
            } else {
                header('Location: creator_list.php');
            }
            exit;
        }
        $errors[] = 'No active user was found with this ID and role.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cre8Connect</title>
    <link rel="stylesheet" href="../css/frontoffice.css">
    <style>
        .form-card {
            max-width: 540px;
            margin: 3rem auto;
            padding: 2rem;
            border: 1px solid #dee2e6;
            border-radius: .75rem;
            background-color: #ffffff;
            box-shadow: 0 0.75rem 1.5rem rgba(18, 38, 63, 0.08);
        }
        .form-card h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .form-card .btn-primary {
            width: 100%;
        }
    </style>
</head>
<body>
    <main class="container py-5">
        <div class="form-card">
            <h1>Login</h1>
            <p class="text-muted">Enter your credentials to sign in.</p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php" class="row g-3 needs-validation" novalidate>
                <div class="col-12">
                    <label for="id" class="form-label">ID</label>
                    <input type="text" class="form-control" id="id" name="id" value="<?php echo htmlspecialchars($id); ?>" placeholder="Enter your ID" required>
                </div>

                <div class="col-12">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value=""<?php echo $role === '' ? ' selected' : ''; ?>>Select your role</option>
                        <option value="marque"<?php echo $role === 'marque' ? ' selected' : ''; ?>>Brand</option>
                        <option value="createur"<?php echo $role === 'createur' ? ' selected' : ''; ?>>Creator</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg">Sign in</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
