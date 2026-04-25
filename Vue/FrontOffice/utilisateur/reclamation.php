<?php
session_start();

if (!isset($_SESSION['id'])) {
    die("Utilisateur non connecté");
}

require_once '../../../Controleur/reclamationC.php';

$reclamationC = new ReclamationC();
$liste = $reclamationC->afficherReclamationsAvecReponsesUser($_SESSION['id']);
?>
<html lang="en"><head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">
        <title>Modern Business - Start Bootstrap Template</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
        <!-- Custom Google font-->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@100;200;300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet">
        <!-- Bootstrap icons-->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css" rel="stylesheet">
    </head>
    <body class="d-flex flex-column h-100 bg-light">
        <main class="flex-shrink-0">
            <!-- Navigation-->
            <nav class="navbar navbar-expand-lg navbar-light bg-white py-3">
                <div class="container px-5">
                    <a class="navbar-brand" href="index.html"><span class="fw-bolder text-primary">Cre8connect</span></a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 small fw-bolder">
                            <li class="nav-item"><a class="nav-link" href="creator.php">Home</a></li>
                            <li class="nav-item"><a class="nav-link" href="reclamation.php">Reclamation</a></li>
                           <li class="nav-item">
    <a class="nav-link text-danger" href="logout.php">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</li>
                        </ul>
                    </div>
                </div>
            </nav>
            <!-- Projects Section-->
           <section class="py-5">
    <div class="container px-5 mb-5">
        <div class="text-center mb-5">
            <h1 class="display-5 fw-bolder mb-0">
                <span class="text-gradient d-inline">Envoyer une réclamation</span>
            </h1>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="card shadow rounded-4 border-0">
                    <div class="card-body p-5">

                        <form method="POST" action="traiterReclamation.php">

                            <!-- Description -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Description</label>
                                <textarea 
                                    name="description" 
                                    class="form-control" 
                                    rows="4" 
                                    placeholder="Décrivez votre problème..." 
                                    required></textarea>
                            </div>

                            <!-- Priorité -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Priorité</label>
                                <select name="priorite" class="form-select">
                                    <option value="faible">Faible</option>
                                    <option value="normale" selected>Normale</option>
                                    <option value="haute">Haute</option>
                                </select>
                            </div>

                            <!-- Bouton -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Envoyer la réclamation
                                </button>
                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
<section class="py-5">
    <div class="container px-5">

        <div class="text-center mb-5">
            <h2 class="fw-bolder">Mes réclamations</h2>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success text-center">
                Réclamation envoyée avec succès !
            </div>
        <?php endif; ?>

        <?php if (empty($liste)): ?>
            <p class="text-center text-muted">Aucune réclamation</p>
        <?php else: ?>

            <div class="row">
                <?php foreach ($liste as $rec): ?>

                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm border-0 h-100">
                            <div class="card-body">

                                <h5 class="fw-bold">
                                    <?php echo htmlspecialchars($rec['description']); ?>
                                </h5>

                                <p class="text-muted small">
                                    <?php echo $rec['date_creation']; ?>
                                </p>

                                <hr>

                                <?php if ($rec['reponse']): ?>
                                    <div class="alert alert-success">
                                        <strong>Réponse admin :</strong><br>
                                        <?php echo htmlspecialchars($rec['reponse']); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        En attente de réponse...
                                    </div>
                                <?php endif; ?>

                                <!-- 🔴 BOUTON SUPPRIMER -->
                                <div class="text-end mt-3 d-flex justify-content-end gap-2">

    <!-- Modifier -->
    <button class="btn btn-sm d-flex align-items-center justify-content-center"
            style="background-color:#AEEA94; width:40px; height:40px; border:none;"
            data-bs-toggle="modal"
            data-bs-target="#modalEdit<?php echo $rec['id']; ?>">
        <i class="bi bi-pencil"></i>
    </button>

    <!-- Supprimer -->
    <form method="POST" action="supprimerReclamation.php"
          onsubmit="return confirm('Voulez-vous vraiment supprimer cette réclamation ?');">

        <input type="hidden" name="id" value="<?php echo $rec['id']; ?>">

        <button type="submit"
                class="btn btn-sm d-flex align-items-center justify-content-center"
                style="background-color:#FF8383; width:40px; height:40px; border:none;">
            <i class="bi bi-trash"></i>
        </button>

    </form>

</div>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</section>
<?php foreach ($liste as $rec): ?>

<div class="modal fade" id="modalEdit<?php echo $rec['id']; ?>" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <form method="POST" action="modifierReclamation.php">

        <div class="modal-header">
          <h5 class="modal-title">Modifier Réclamation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <input type="hidden" name="id" value="<?php echo $rec['id']; ?>">

          <!-- Description -->
          <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" required><?php echo $rec['description']; ?></textarea>
          </div>

          <!-- Priorité -->
          <div class="mb-3">
            <label>Priorité</label>
            <select name="priorite" class="form-select">
              <option value="faible" <?php if($rec['priorite']=='faible') echo 'selected'; ?>>Faible</option>
              <option value="normale" <?php if($rec['priorite']=='normale') echo 'selected'; ?>>Normale</option>
              <option value="haute" <?php if($rec['priorite']=='haute') echo 'selected'; ?>>Haute</option>
            </select>
          </div>

        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Enregistrer</button>
        </div>

      </form>

    </div>
  </div>
</div>

<?php endforeach; ?>
            <!-- Call to action section-->
            <section class="py-5 bg-gradient-primary-to-secondary text-white">
                <div class="container px-5 my-5">
                    <div class="text-center">
                        <h2 class="display-4 fw-bolder mb-4">Let's build something together</h2>
                        
                    </div>
                </div>
            </section>
        </main>
        <!-- Footer-->
        <footer class="bg-white py-4 mt-auto">
            <div class="container px-5">
                <div class="row align-items-center justify-content-between flex-column flex-sm-row">
                    <div class="col-auto"><div class="small m-0">Copyright © Your Website 2023</div></div>
                    <div class="col-auto">
                        <a class="small" href="#!">Privacy</a>
                        <span class="mx-1">·</span>
                        <a class="small" href="#!">Terms</a>
                        <span class="mx-1">·</span>
                        <a class="small" href="#!">Contact</a>
                    </div>
                </div>
            </div>
        </footer>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>
    

</body></html>