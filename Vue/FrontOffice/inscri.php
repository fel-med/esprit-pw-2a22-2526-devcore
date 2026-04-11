<?php
session_start();

$id = $_SESSION['inscri_id'] ?? '';
$role = $_SESSION['inscri_role'] ?? '';
$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id'] ?? '');
    $role = $_POST['role'] ?? '';

    if ($id === '') {
        $errors[] = 'L\'ID est requis.';
    }

    if (!in_array($role, ['marque', 'creator'], true)) {
        $errors[] = 'Veuillez sélectionner un rôle valide.';
    }

    if (empty($errors)) {
        $_SESSION['inscri_id'] = $id;
        $_SESSION['inscri_role'] = $role;
        $message = 'Vos informations ont bien été enregistrées.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Cre8Connect</title>
    <link rel="stylesheet" href="css/frontoffice.css">
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
            <h1>Inscription rapide</h1>
            <p class="text-muted">Entrez votre identifiant et choisissez votre rôle.</p>

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

            <form method="post" action="inscri.php" class="row g-3 needs-validation" novalidate>
                <div class="col-12">
                    <label for="id" class="form-label">Identifiant</label>
                    <input type="text" class="form-control" id="id" name="id" value="<?php echo htmlspecialchars($id); ?>" placeholder="Entrez votre ID" required>
                </div>

                <div class="col-12">
                    <label for="role" class="form-label">Rôle</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value=""<?php echo $role === '' ? ' selected' : ''; ?>>Sélectionnez votre rôle</option>
                        <option value="marque"<?php echo $role === 'marque' ? ' selected' : ''; ?>>Marque</option>
                        <option value="creator"<?php echo $role === 'creator' ? ' selected' : ''; ?>>Creator</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg">Valider</button>
                </div>
            </form>

            <?php if (!empty($message)): ?>
                <div class="mt-4">
                    <h2 class="h5">Infos enregistrées</h2>
                    <p>ID : <strong><?php echo htmlspecialchars($id); ?></strong></p>
                    <p>Rôle : <strong><?php echo htmlspecialchars(ucfirst($role)); ?></strong></p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
