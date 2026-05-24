<?php
require_once __DIR__ . '/../layout/session_bridge.php';
cre8_front_start_session();

require_once __DIR__ . '/../../../Controleur/produitC.php';
require_once __DIR__ . '/../layout/avatar_helper.php';

$frontActive = 'campaigns';
$currentFrontUser = cre8_front_session_user();
$isAdminVisitor = cre8_front_is_admin_visitor($currentFrontUser);
$currentFrontRole = cre8_front_normalize_role($currentFrontUser['role'] ?? '');
$currentFrontUserId = (int) ($currentFrontUser['id'] ?? 0);
$canReviewProducts = !empty($currentFrontUser['isLoggedIn']) && $currentFrontRole === 'createur';
$controller = new ProduitC();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'product_reviews') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $productId = (int) ($_GET['idProduit'] ?? $_GET['product_id'] ?? 0);
        $product = $controller->recupererProduit($productId);
        if (!$product || !empty($product['estArchive'])) {
            http_response_code(404);
            throw new RuntimeException('Product not found.');
        }

        $rating = isset($_GET['rating']) && $_GET['rating'] !== '' ? (int) $_GET['rating'] : null;
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = (int) ($_GET['per_page'] ?? 5);
        $stats = $controller->getProductReviewStats([$productId])[$productId] ?? ['avgRating' => null, 'reviewCount' => 0];

        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'reviewPage' => $controller->getPaginatedProductReviews($productId, $page, $perPage, $rating),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        if (http_response_code() < 400) {
            http_response_code(400);
        }
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productAction = trim((string) ($_POST['action'] ?? $_POST['product_action'] ?? ''));
    if (in_array($productAction, ['interested', 'product_interest', 'apply_product'], true)) {
        http_response_code(403);
        $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Product applications were replaced by reviews.',
            ]);
        } else {
            echo 'Product applications were replaced by reviews.';
        }
        exit;
    }

    if ($productAction === 'save_review') {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (!$canReviewProducts) {
                http_response_code(403);
                throw new RuntimeException('Only creators can write product reviews.');
            }

            $productId = (int) ($_POST['idProduit'] ?? $_POST['product_id'] ?? 0);
            $rating = (int) ($_POST['rating'] ?? 0);
            $title = (string) ($_POST['title'] ?? '');
            $reviewText = (string) ($_POST['reviewText'] ?? $_POST['review_text'] ?? '');

            $review = $controller->saveOrUpdateProductReview($productId, $currentFrontUserId, $rating, $title, $reviewText);
            $stats = $controller->getProductReviewStats([$productId])[$productId] ?? ['avgRating' => null, 'reviewCount' => 0];

            echo json_encode([
                'success' => true,
                'message' => 'Review saved.',
                'review' => $review,
                'stats' => $stats,
                'reviewPage' => $controller->getPaginatedProductReviews($productId, 1, 5, null),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            if (http_response_code() < 400) {
                http_response_code(400);
            }
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }
}

$cre8SelfPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8VuePos = strpos($cre8SelfPath, '/Vue/');
$baseUrl = $cre8VuePos !== false ? substr($cre8SelfPath, 0, $cre8VuePos) : '';
if (!function_exists('cre8_product_image_url')) {
    function cre8_product_image_url($filename) {
        global $baseUrl;
        $filename = trim((string) $filename);
        if ($filename === '') return '';
        return $baseUrl . '/Vue/public/produits/' . rawurlencode(basename($filename));
    }
}

// Load all active products (no marque filter — public catalog)
$initialSort = in_array(($_GET['sort'] ?? ''), ['rating_desc', 'rating_asc', 'reviews_desc'], true) ? (string) $_GET['sort'] : '';
$produits   = $controller->afficherProduits(null, $initialSort);
$categories = $controller->getCategories();
$productIds = array_map(static fn($p) => (int) ($p['idProduit'] ?? 0), $produits);
$reviewStats = $controller->getProductReviewStats($productIds);
$productReviews = $controller->getProductReviewsForProducts($productIds);
$myProductReviews = $canReviewProducts ? $controller->getCreatorReviewsForProducts($currentFrontUserId, $productIds) : [];

// Detail view: ?voir=ID
$produitDetail = null;
if (isset($_GET['voir'])) {
    $produitDetail = $controller->recupererProduit(intval($_GET['voir']));
}

// Stats
$totalProduits = count($produits);
$allPrix       = array_column($produits, 'prix');
$prixMin       = $totalProduits > 0 ? min($allPrix) : 0;
$prixMax       = $totalProduits > 0 ? max($allPrix) : 0;
$nbEpingles    = count(array_filter($produits, fn($p) => !empty($p['estEpingle'])));

// Shared categories list — same as brand FO and admin BO for consistency
$categoriesDisponibles = ['Beauty & Care','Fashion & Accessories','Tech & Gadgets','Food & Nutrition','Sport & Fitness','Home & Decor','Travel','Wellness','Gaming','Kids'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php require_once __DIR__ . '/../layout/front-theme-bootstrap.php'; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Catalog – Cre8Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,700;0,9..144,900;1,9..144,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/front-header.css?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/front-header.css')); ?>">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:        #5b4fff;
            --primary-light:  #ece9ff;
            --primary-hover:  #4438e0;
            --primary-glow:   rgba(91,79,255,0.15);
            --primary-border: rgba(91,79,255,0.2);
            --text-main:      #0f0e1a;
            --text-sub:       #6b6f80;
            --text-dim:       #a0a4b2;
            --border:         #ebebf2;
            --bg:             #f6f6fc;
            --white:          #ffffff;
            --danger:         #f43f5e;
            --danger-light:   #fff1f3;
            --danger-border:  rgba(244,63,94,0.2);
            --success:        #0ea370;
            --success-light:  #edfaf5;
            --success-border: rgba(14,163,112,0.2);
            --warning:        #f59e0b;
            --warning-light:  #fffbeb;
            --warning-border: rgba(245,158,11,0.2);
            --pin:            #f59e0b;
            --card-shadow:    0 1px 3px rgba(15,14,26,0.06), 0 4px 16px rgba(91,79,255,0.06);
            --card-shadow-hover: 0 8px 32px rgba(91,79,255,0.14);
            --radius:         14px;
            --radius-sm:      8px;
            --nav-h:          66px;
        }

        /* ===== ADDED FEATURE: DARK MODE CSS VARIABLES ===== */
        body.dark-mode {
            --primary:        #7c6fff;
            --primary-light:  rgba(124,111,255,0.15);
            --primary-hover:  #6358e8;
            --primary-glow:   rgba(124,111,255,0.2);
            --primary-border: rgba(124,111,255,0.25);
            --text-main:      #eef0f8;
            --text-sub:       #9097b8;
            --text-dim:       #5a6080;
            --border:         #2a2f42;
            --bg:             #0d0f14;
            --white:          #141720;
            --danger:         #f87171;
            --danger-light:   rgba(248,113,113,0.1);
            --danger-border:  rgba(248,113,113,0.2);
            --success:        #34d399;
            --success-light:  rgba(52,211,153,0.1);
            --success-border: rgba(52,211,153,0.2);
            --warning:        #fbbf24;
            --warning-light:  rgba(251,191,36,0.1);
            --warning-border: rgba(251,191,36,0.2);
            --pin:            #fbbf24;
            --card-shadow:    0 1px 3px rgba(0,0,0,0.3), 0 4px 16px rgba(0,0,0,0.2);
            --card-shadow-hover: 0 8px 32px rgba(124,111,255,0.2);
        }
        body.dark-mode .catalog-hero { background: linear-gradient(135deg, #1a1730 0%, #1c1a35 60%, #151c30 100%); }
        body.dark-mode .pinned-card { background: linear-gradient(135deg, #1f1a0a, #2a2010); border-color: #92750a; }
        body.dark-mode .pinned-card:hover { box-shadow: 0 4px 16px rgba(251,191,36,0.15); }
        body.dark-mode .pinned-thumb { background: #2a2010; }
        body.dark-mode .btn-see-pinned { background: #2a2010; color: #fbbf24; border-color: #92750a; }
        body.dark-mode .btn-see-pinned:hover { background: #3a2e12; }
        body.dark-mode .pcard-img-empty { background: linear-gradient(135deg, #1a1730, #0d0f14); color: #3a3660; }
        body.dark-mode .detail-box { background: #1a1e2a; }
        body.dark-mode .detail-content { background: #1a1e2a; }
        body.dark-mode .detail-close { background: #0d0f14; }
        body.dark-mode .btn-close-detail { background: #0d0f14; }
        body.dark-mode .btn-close-detail:hover { background: #141720; }
        body.dark-mode .no-results { background: var(--white); border-color: var(--border); }
        body.dark-mode .empty-state { background: var(--white); border-color: var(--border); }
        body { transition: background .25s, color .25s; }
        /* ===== END ADDED FEATURE ===== */

        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text-main); min-height: 100vh; }
        .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .nav-logo img { width: 36px; height: 36px; object-fit: contain; border-radius: 9px; }
        .nav-logo-text { font-family: 'Fraunces', serif; font-size: 19px; font-weight: 800; color: var(--primary); letter-spacing: -0.5px; }
        .nav-links { display: flex; gap: 6px; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--text-sub); font-size: 13.5px; font-weight: 600; padding: 6px 14px; border-radius: 8px; transition: background .18s, color .18s; }
        .nav-links a:hover { background: var(--bg); color: var(--text-main); }
        .nav-links a.active { background: var(--primary-light); color: var(--primary); }
        .nav-right { display: flex; align-items: center; gap: 12px; }
        .nav-badge-creator { background: linear-gradient(135deg, var(--primary), #a78bfa); color: #fff; border-radius: 20px; padding: 5px 14px; font-size: 12px; font-weight: 700; }
        .nav-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; cursor: pointer; border: 2px solid var(--primary-border); }

        /* ===== ADDED FEATURE: NAV CONTROLS (theme) ===== */
        .nav-controls { display: flex; align-items: center; gap: 8px; }
        .theme-btn { background: var(--bg); border: 1.5px solid var(--border); border-radius: 7px; padding: 5px 10px; font-size: 13px; cursor: pointer; color: var(--text-sub); display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; font-family: 'DM Sans', sans-serif; transition: all .15s; white-space: nowrap; }
        .theme-btn:hover { border-color: var(--primary); color: var(--primary); }
        /* ===== END ADDED FEATURE ===== */

        /* ── PAGE WRAPPER ── */
        .page-wrapper { max-width: 1220px; margin: 0 auto; padding: 40px 24px 80px; }

        /* ── HERO ── */
        .catalog-hero { background: linear-gradient(135deg, #f0effb 0%, #ece9ff 60%, #e8f4ff 100%); border-radius: 18px; padding: 40px 44px; margin-bottom: 36px; display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; border: 1px solid var(--primary-border); transition: background .25s; }
        .product-front .catalog-hero-aside {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
            flex-shrink: 0;
        }
        .hero-left h1 { font-family: 'Fraunces', serif; font-size: 32px; font-weight: 900; color: var(--text-main); letter-spacing: -0.8px; line-height: 1.1; }
        .hero-left p { font-size: 14px; color: var(--text-sub); margin-top: 7px; max-width: 480px; line-height: 1.6; }
        .hero-stats { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 18px; }
        .hero-stat { display: flex; align-items: center; gap: 8px; }
        .hero-stat-val { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 800; color: var(--primary); }
        .hero-stat-label { font-size: 12px; color: var(--text-sub); font-weight: 600; }
        .hero-right { font-size: 64px; opacity: 0.3; flex-shrink: 0; }

        /* ── PINNED BANNER ── */
        .pinned-banner { margin-bottom: 28px; }
        .pinned-banner-header { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .pinned-banner-header h2 { font-family: 'Fraunces', serif; font-size: 16px; font-weight: 800; color: #92400e; }
        body.dark-mode .pinned-banner-header h2 { color: #fbbf24; }
        .pinned-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 10px; }
        .pinned-card { background: linear-gradient(135deg, #fffbeb, #fef3c7); border: 1.5px solid #fcd34d; border-radius: var(--radius-sm); padding: 14px; position: relative; transition: box-shadow .2s, background .25s, border-color .25s; }
        .pinned-card:hover { box-shadow: 0 4px 16px rgba(245,158,11,0.2); }
        .pin-badge-fo { position: absolute; top: -6px; right: 10px; background: var(--pin); color: #fff; border-radius: 20px; padding: 2px 9px; font-size: 10px; font-weight: 800; }
        .pinned-thumb { width: 100%; height: 80px; object-fit: cover; border-radius: 6px; background: #fde68a; display: flex; align-items: center; justify-content: center; font-size: 24px; overflow: hidden; margin-bottom: 8px; }
        .pinned-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .pinned-name { font-size: 13px; font-weight: 700; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pinned-price { font-size: 13px; font-weight: 800; color: var(--primary); font-family: 'Fraunces', serif; margin-top: 3px; }
        .pinned-cat-fo { font-size: 11px; color: var(--text-sub); margin-top: 2px; }
        .btn-see-pinned { display: inline-flex; align-items: center; gap: 5px; background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; border-radius: 7px; padding: 5px 12px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; margin-top: 8px; transition: background .18s; font-family: 'DM Sans', sans-serif; }
        .btn-see-pinned:hover { background: #fde68a; }

        /* ── SECTION HEADER ── */
        .section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; gap: 12px; flex-wrap: wrap; }
        .section-head-left { display: flex; align-items: center; gap: 10px; }
        .section-head-left h2 { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 800; letter-spacing: -0.4px; }
        .count-pill { background: var(--primary-light); color: var(--primary); border-radius: 20px; padding: 3px 11px; font-size: 12px; font-weight: 700; }

        /* ── TOOLS BAR ── */
        .tools-bar { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .search-wrap { position: relative; }
        .search-wrap svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-dim); pointer-events: none; width: 14px; height: 14px; }
        .search-input { background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 9px 12px 9px 32px; font-size: 13px; font-family: 'DM Sans', sans-serif; color: var(--text-main); width: 220px; outline: none; transition: border-color .2s, box-shadow .2s, background .25s, color .25s; }
        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .sort-select { background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 9px 12px; font-size: 13px; font-family: 'DM Sans', sans-serif; outline: none; cursor: pointer; color: var(--text-main); transition: background .25s, border-color .25s, color .25s; }
        .view-toggle { display: flex; border: 1.5px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; background: var(--white); transition: background .25s, border-color .25s; }
        .view-btn { background: transparent; border: none; padding: 8px 11px; cursor: pointer; color: var(--text-dim); display: flex; align-items: center; transition: background .15s, color .15s; }
        .view-btn.active, .view-btn:hover { background: var(--primary-light); color: var(--primary); }
        .view-btn svg { width: 15px; height: 15px; }

        /* ── CATEGORY FILTER ── */
        .cat-filter-bar { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; align-items: center; padding: 14px 18px; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--card-shadow); transition: background .25s, border-color .25s; }
        .cat-filter-label { font-size: 12px; font-weight: 700; color: var(--text-sub); margin-right: 4px; white-space: nowrap; }
        .cat-chip { display: inline-flex; align-items: center; gap: 4px; padding: 5px 13px; border-radius: 20px; border: 1.5px solid var(--border); background: var(--bg); font-size: 12px; font-weight: 600; color: var(--text-sub); cursor: pointer; transition: all .15s; font-family: 'DM Sans', sans-serif; }
        .cat-chip.active, .cat-chip:hover { background: var(--primary-light); color: var(--primary); border-color: var(--primary-border); }
        .cat-chip.active { font-weight: 700; }

        /* ── PRICE RANGE FILTER ── */
        .price-filter-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; padding: 12px 18px; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 14px; box-shadow: var(--card-shadow); transition: background .25s, border-color .25s; }
        .pf-label { font-size: 12px; font-weight: 700; color: var(--text-sub); }
        .pf-range { display: flex; align-items: center; gap: 8px; }
        .pf-input { width: 80px; padding: 7px 10px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 13px; font-family: 'DM Sans', sans-serif; outline: none; transition: border-color .2s, background .25s, color .25s; background: var(--bg); color: var(--text-main); }
        .pf-input:focus { border-color: var(--primary); }
        .pf-sep { font-size: 12px; color: var(--text-dim); }
        .pf-stat { font-size: 12px; color: var(--text-dim); }
        .pf-reset { background: transparent; color: var(--text-sub); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 6px 12px; font-size: 12px; font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: all .15s; margin-left: auto; }
        .pf-reset:hover { color: var(--text-main); border-color: var(--text-sub); }

        /* ── NO RESULTS ── */
        .no-results { text-align: center; padding: 48px 24px; background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); transition: background .25s, border-color .25s; }
        .no-results .icon { font-size: 40px; margin-bottom: 12px; display: block; }
        .no-results h3 { font-family: 'Fraunces', serif; font-size: 18px; font-weight: 800; margin-bottom: 6px; }
        .no-results p { font-size: 14px; color: var(--text-sub); }

        /* ── PRODUCT GRID ── */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .products-grid.view-list { grid-template-columns: 1fr; }

        /* ── PRODUCT CARD ── */
        .product-card { background: var(--white); border-radius: var(--radius); overflow: hidden; box-shadow: var(--card-shadow); border: 1px solid var(--border); transition: transform .22s, box-shadow .22s, border-color .22s, background .25s; display: flex; flex-direction: column; position: relative; }
        .product-card:hover { transform: translateY(-3px); box-shadow: var(--card-shadow-hover); border-color: var(--primary-border); }
        .view-list .product-card { flex-direction: row; }
        .product-card.is-pinned { border: 1.5px solid #fcd34d; }
        body.dark-mode .product-card.is-pinned { border-color: #92750a; }

        /* Card image */
        .pcard-img { position: relative; height: 200px; overflow: hidden; background: var(--bg); flex-shrink: 0; }
        .view-list .pcard-img { width: 200px; height: auto; min-height: 150px; }
        .pcard-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s; display: block; }
        .product-card:hover .pcard-img img { transform: scale(1.05); }
        .pcard-img-empty { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 44px; background: linear-gradient(135deg, #f0effb, var(--bg)); color: #c0bde0; }
        .pcard-price-badge { position: absolute; bottom: 10px; right: 10px; background: var(--primary); color: #fff; border-radius: 20px; padding: 5px 13px; font-size: 14px; font-weight: 800; font-family: 'Fraunces', serif; box-shadow: 0 2px 8px rgba(91,79,255,0.3); }

        /* Pinned badge */
        .badge-epingle { position: absolute; top: 10px; right: 10px; z-index: 10; background: var(--pin); color: #fff; border-radius: 20px; padding: 4px 10px; font-size: 11px; font-weight: 700; }

        /* Availability badge */
        .badge-dispo { position: absolute; top: 10px; left: 10px; z-index: 10; border-radius: 20px; padding: 4px 10px; font-size: 11px; font-weight: 700; }
        .badge-dispo.available { background: rgba(14,163,112,0.9); color: #fff; }
        .badge-dispo.future    { background: rgba(245,158,11,0.9); color: #fff; }

        /* Quickview overlay */
        .pcard-quickview { position: absolute; inset: 0; background: rgba(15,14,26,0.65); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity .2s; }
        .product-card:hover .pcard-quickview { opacity: 1; }
        .btn-quickview { background: var(--white); color: var(--text-main); border: none; border-radius: var(--radius-sm); padding: 9px 18px; font-size: 13px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; display: flex; align-items: center; gap: 6px; transition: background .18s; }
        .btn-quickview:hover { background: var(--primary-light); color: var(--primary); }

        /* Card body */
        .pcard-body { padding: 18px; display: flex; flex-direction: column; flex: 1; }
        .view-list .pcard-body { padding: 20px 24px; }

        .pcard-cat { font-size: 11px; font-weight: 700; color: var(--text-sub); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; display: flex; align-items: center; gap: 5px; }
        .pcard-cat .cat-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--primary); opacity: 0.5; }
        .product-brand-row { display: inline-flex; align-items: center; gap: 7px; color: var(--text-sub); font-size: 12px; font-weight: 700; margin-bottom: 8px; max-width: 100%; }
        .product-brand-row span:last-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pcard-name { font-family: 'Fraunces', serif; font-size: 16px; font-weight: 800; color: var(--text-main); line-height: 1.25; margin-bottom: 6px; letter-spacing: -0.2px; }
        .pcard-rating { display: inline-flex; align-items: center; gap: 4px; width: fit-content; margin-bottom: 8px; padding: 4px 9px; border-radius: 999px; background: var(--warning-light); color: var(--warning); border: 1px solid var(--warning-border); font-size: 12px; font-weight: 800; }
        .pcard-rating.is-empty { background: var(--bg); color: var(--text-sub); border-color: var(--border); }
        .pcard-desc { font-size: 13px; color: var(--text-sub); line-height: 1.6; margin-bottom: 10px; flex: 1; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* Tags on card */
        .pcard-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; }
        .pcard-tag { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: var(--primary); background: var(--primary-light); border-radius: 6px; padding: 4px 9px; font-weight: 600; }

        /* Availability row on card */
        .pcard-dispo-row { font-size: 11px; color: var(--text-sub); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .dot-avail  { width: 7px; height: 7px; border-radius: 50%; background: var(--success); flex-shrink: 0; }
        .dot-future { width: 7px; height: 7px; border-radius: 50%; background: var(--warning); flex-shrink: 0; }

        /* Card actions */
        .pcard-actions { display: flex; gap: 8px; padding-top: 14px; border-top: 1px solid var(--border); flex-wrap: wrap; }
        .btn-see-detail { flex: 1; min-width: 80px; padding: 9px; background: var(--primary); color: #fff; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; text-align: center; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: background .18s, transform .15s; box-shadow: 0 2px 8px var(--primary-glow); }
        .btn-see-detail:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-review-product { flex: 0 0 auto; padding: 9px 14px; background: var(--primary-light); color: var(--primary); border: 1.5px solid var(--primary-border); border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: background .18s; }
        .btn-review-product:hover { background: var(--primary-light); filter: brightness(.92); }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 64px 24px; background: var(--white); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--card-shadow); transition: background .25s, border-color .25s; }
        .empty-state .empty-icon { font-size: 56px; margin-bottom: 18px; display: block; }
        .empty-state h3 { font-family: 'Fraunces', serif; font-size: 22px; font-weight: 800; margin-bottom: 8px; }
        .empty-state p { font-size: 14px; color: var(--text-sub); }

        /* ── DETAIL MODAL ── */
        .detail-overlay { display: none; position: fixed; inset: 0; background: rgba(15,14,26,0.55); z-index: 300; align-items: center; justify-content: center; padding: 20px; }
        .detail-overlay.open { display: flex; }
        .detail-box { background: var(--white); border-radius: var(--radius); width: 720px; max-width: 100%; overflow: hidden; box-shadow: 0 24px 64px rgba(15,14,26,0.22); animation: slideUp .24s ease; display: flex; max-height: 90vh; transition: background .25s; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(18px); } to { opacity: 1; transform: translateY(0); } }
        .detail-img { width: 240px; flex-shrink: 0; background: var(--bg); display: flex; align-items: center; justify-content: center; font-size: 60px; color: #c0bde0; overflow: hidden; }
        .detail-img img { width: 100%; height: 100%; object-fit: cover; }
        .detail-content { flex: 1; padding: 30px 28px; overflow-y: auto; position: relative; background: var(--white); transition: background .25s; }
        .detail-close { position: absolute; top: 14px; right: 14px; background: var(--bg); border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; color: var(--text-sub); box-shadow: var(--card-shadow); transition: all .2s; }
        .detail-cat { font-size: 11px; font-weight: 700; color: var(--text-sub); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
        .detail-name { font-family: 'Fraunces', serif; font-size: 24px; font-weight: 900; color: var(--text-main); margin-bottom: 8px; letter-spacing: -0.5px; }
        .detail-price { font-family: 'Fraunces', serif; font-size: 30px; font-weight: 900; color: var(--primary); margin-bottom: 12px; }
        .detail-rating { display: inline-flex; align-items: center; gap: 5px; margin-bottom: 12px; padding: 6px 11px; border-radius: 999px; background: var(--warning-light); border: 1px solid var(--warning-border); color: var(--warning); font-size: 13px; font-weight: 900; }
        .detail-rating.is-empty { background: var(--bg); border-color: var(--border); color: var(--text-sub); }
        .detail-dispo { font-size: 13px; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
        .detail-dispo.available { color: var(--success); }
        .detail-dispo.future { color: var(--warning); }
        .detail-desc { font-size: 14px; color: var(--text-sub); line-height: 1.75; margin-bottom: 18px; }
        .detail-section-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--text-dim); margin-bottom: 8px; margin-top: 16px; }
        .detail-carac { font-size: 13px; color: var(--primary); background: var(--primary-light); border-radius: 8px; padding: 10px 14px; }
        .detail-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px; }
        .detail-tag { font-size: 12px; font-weight: 600; color: var(--primary); background: var(--primary-light); border-radius: 6px; padding: 5px 11px; }
        .detail-marque { display: inline-flex; align-items: center; gap: 7px; background: var(--bg); border: 1px solid var(--border); border-radius: 20px; padding: 4px 12px 4px 5px; font-size: 12px; font-weight: 600; color: var(--text-sub); margin-bottom: 14px; }
        .detail-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--border); }
        .btn-primary-detail { display: inline-flex; align-items: center; gap: 7px; background: var(--primary); color: #fff; border: none; border-radius: var(--radius-sm); padding: 11px 22px; font-size: 14px; font-weight: 700; font-family: 'DM Sans', sans-serif; cursor: pointer; transition: background .2s; box-shadow: 0 3px 10px var(--primary-glow); }
        .btn-primary-detail:hover { background: var(--primary-hover); }
        .btn-close-detail { background: var(--bg); color: var(--text-sub); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 10px 20px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: 'DM Sans', sans-serif; margin-left: auto; transition: all .2s; }
        .btn-close-detail:hover { background: var(--white); }

        .reviews-panel { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border); }
        .reviews-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
        .reviews-head h4 { margin: 0; font-family: 'Fraunces', serif; font-size: 17px; font-weight: 900; color: var(--text-main); }
        .review-filter-row { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; margin-bottom: 12px; }
        .review-filter-chip { border: 1.5px solid var(--border); background: var(--bg); color: var(--text-sub); border-radius: 999px; padding: 6px 10px; font: 800 12px 'DM Sans', sans-serif; cursor: pointer; transition: all .16s; }
        .review-filter-chip:hover { border-color: var(--primary); color: var(--primary); }
        .review-filter-chip.active { background: var(--primary); border-color: var(--primary); color: #fff; box-shadow: 0 3px 10px var(--primary-glow); }
        .review-list { display: grid; gap: 10px; max-height: 360px; overflow-y: auto; padding-right: 4px; }
        .review-item { border: 1px solid var(--border); border-radius: 12px; background: var(--bg); padding: 12px; }
        .review-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 6px; }
        .review-author { color: var(--text-main); font-size: 13px; font-weight: 900; }
        .review-stars { color: var(--warning); font-size: 13px; letter-spacing: 0.03em; white-space: nowrap; }
        .review-title { margin: 0 0 4px; color: var(--text-main); font-size: 13px; font-weight: 900; }
        .review-text { margin: 0; color: var(--text-sub); font-size: 13px; line-height: 1.55; }
        .review-date { margin-top: 7px; color: var(--text-dim); font-size: 11px; font-weight: 700; }
        .review-empty { border: 1px dashed var(--border); border-radius: 12px; padding: 14px; color: var(--text-sub); font-size: 13px; background: var(--bg); }
        .review-pager { display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: wrap; margin-top: 12px; }
        .review-pager .pg-btn { padding: 6px 10px; font-size: 12px; min-width: 32px; }
        .review-loading { border: 1px dashed var(--border); border-radius: 12px; padding: 14px; color: var(--text-sub); font-size: 13px; background: var(--bg); }

        .review-overlay { display: none; position: fixed; inset: 0; z-index: 420; align-items: center; justify-content: center; padding: 20px; background: rgba(15,14,26,0.58); }
        .review-overlay.open { display: flex; }
        .review-box { width: 520px; max-width: 100%; background: var(--white); border: 1px solid var(--border); border-radius: 18px; box-shadow: 0 24px 70px rgba(15,14,26,0.26); padding: 24px; animation: slideUp .22s ease; }
        .review-box-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .review-box h3 { margin: 0; font-family: 'Fraunces', serif; font-size: 22px; font-weight: 900; color: var(--text-main); }
        .review-product-name { margin-top: 5px; color: var(--text-sub); font-size: 13px; font-weight: 700; }
        .review-close { width: 34px; height: 34px; border: 0; border-radius: 999px; background: var(--bg); color: var(--text-sub); cursor: pointer; font-size: 16px; }
        .review-field { display: grid; gap: 7px; margin-bottom: 14px; }
        .review-field label { color: var(--text-main); font-size: 12px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.04em; }
        .review-field input, .review-field textarea { width: 100%; border: 1.5px solid var(--border); border-radius: 10px; background: var(--bg); color: var(--text-main); padding: 11px 12px; font: 600 14px 'DM Sans', sans-serif; outline: none; transition: border-color .18s, box-shadow .18s; }
        .review-field textarea { min-height: 116px; resize: vertical; line-height: 1.55; }
        .review-field input:focus, .review-field textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
        .star-picker { display: inline-flex; gap: 4px; width: fit-content; padding: 6px 8px; border: 1px solid var(--border); border-radius: 999px; background: var(--bg); }
        .star-picker button { border: 0; background: transparent; color: var(--text-dim); font-size: 24px; line-height: 1; cursor: pointer; transition: color .15s, transform .15s; }
        .star-picker button.is-selected, .star-picker button:hover { color: var(--warning); transform: translateY(-1px); }
        .review-message { min-height: 19px; color: var(--danger); font-size: 13px; font-weight: 800; margin-bottom: 12px; }
        .review-message.success { color: var(--success); }
        .review-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 10px; }
        .review-cancel { background: var(--bg); color: var(--text-sub); border: 1.5px solid var(--border); border-radius: 10px; padding: 10px 16px; font-weight: 800; cursor: pointer; }
        .review-submit { background: var(--primary); color: #fff; border: 0; border-radius: 10px; padding: 10px 18px; font-weight: 900; cursor: pointer; box-shadow: 0 3px 10px var(--primary-glow); }
        .review-submit:disabled { opacity: 0.65; cursor: wait; }

        /* ===== ADDED FEATURE: PAGINATION ===== */
        .pagination-bar { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 28px 0 4px; flex-wrap: wrap; }
        .pg-btn { background: var(--white); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 7px 13px; font-size: 13px; font-weight: 600; cursor: pointer; color: var(--text-sub); font-family: 'DM Sans', sans-serif; transition: all .15s; min-width: 36px; text-align: center; }
        .pg-btn:hover:not(:disabled) { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
        .pg-btn:disabled { opacity: .35; cursor: not-allowed; }
        .pg-btn.current { background: var(--primary); color: #fff; border-color: var(--primary); font-weight: 700; }
        .pg-dots { font-size: 13px; color: var(--text-dim); padding: 0 4px; }
        .pg-info { font-size: 12px; color: var(--text-dim); font-weight: 600; margin-left: 8px; }
        /* ===== END ADDED FEATURE ===== */

        /* Unified FrontOffice indicators */
        .hero-stat {
            background: linear-gradient(135deg, rgba(236,233,255,0.82), rgba(255,255,255,0.9));
            border: 1px solid var(--primary-border);
            border-radius: 16px;
            padding: 8px 12px;
            box-shadow: 0 8px 22px rgba(91,79,255,0.08);
        }
        .hero-stat-val {
            color: var(--primary);
            font-family: 'Fraunces', serif;
            font-weight: 900;
            line-height: 1;
        }
        .hero-stat-label {
            color: var(--text-sub);
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            font-weight: 700;
        }
        .count-pill,
        .pcard-tag,
        .detail-tag {
            background: var(--primary-light);
            border: 1px solid var(--primary-border);
            border-radius: 999px;
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
        }
        .pcard-tag { font-size: 11px; padding: 4px 10px; }
        .pcard-tags .pcard-tag[style] {
            background: var(--bg) !important;
            border-color: var(--border) !important;
            color: var(--text-sub) !important;
        }
        .cat-chip {
            background: var(--white);
            border-color: var(--border);
            border-radius: 999px;
            color: var(--text-sub);
            font-size: 12px;
            font-weight: 700;
        }
        .cat-chip.active,
        .cat-chip:hover {
            background: var(--primary-light);
            border-color: var(--primary-border);
            color: var(--primary);
        }
        .pcard-price-badge,
        .badge-epingle,
        .badge-dispo {
            border-radius: 999px;
            font-weight: 700;
        }
        .pcard-price-badge {
            background: var(--primary);
            font-family: 'Fraunces', serif;
        }
        .badge-dispo.available {
            background: var(--success-light);
            border: 1px solid var(--success-border);
            color: var(--success);
        }
        .badge-dispo.future {
            background: var(--warning-light);
            border: 1px solid var(--warning-border);
            color: var(--warning);
        }
        .badge-epingle {
            background: var(--warning-light);
            border: 1px solid var(--warning-border);
            color: var(--warning);
        }
        body.dark-mode .hero-stat {
            background: linear-gradient(135deg, rgba(42,38,96,0.76), rgba(20,23,32,0.92));
            border-color: var(--primary-border);
            box-shadow: 0 8px 22px rgba(0,0,0,0.24);
        }
        body.dark-mode .cat-chip,
        body.dark-mode .pcard-tags .pcard-tag[style] {
            background: var(--bg) !important;
            border-color: var(--border) !important;
            color: var(--text-sub) !important;
        }
        body.dark-mode .cat-chip.active,
        body.dark-mode .cat-chip:hover {
            background: var(--primary-light) !important;
            border-color: var(--primary-border) !important;
            color: var(--primary) !important;
        }

        @media (max-width: 900px) { .nav-links { display: none; } .catalog-hero { padding: 28px 24px; } .detail-box { flex-direction: column; } .detail-img { width: 100%; height: 200px; } }
        @media (max-width: 600px) { .products-grid { grid-template-columns: 1fr; } .hero-left h1 { font-size: 24px; } .nav-controls { gap: 5px; } }
    </style>
<link rel="icon" type="image/png" sizes="16x16" href="../../public/images/favicon-16.png">
<link rel="icon" type="image/png" sizes="32x32" href="../../public/images/favicon-32.png">
<link rel="shortcut icon" type="image/png" href="../../public/images/favicon-32.png">
<link rel="apple-touch-icon" sizes="180x180" href="../../public/images/apple-touch-icon.png">
</head>
<body>

<!-- NAV -->
<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="page-wrapper product-front">

    <!-- HERO -->
    <div class="catalog-hero">
        <div class="hero-left">
            <h1 data-i18n="heroTitle">Discover Products<br>Made for Creators</h1>
            <p data-i18n="heroSubtitle">Browse the full catalog of products brands have made available for collaboration. Find what fits your audience and apply for campaigns.</p>
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="hero-stat-val"><?= $totalProduits ?></span>
                    <span class="hero-stat-label" data-i18n="statProducts">Products available</span>
                </div>
                <?php if (!empty($categories)): ?>
                <div class="hero-stat">
                    <span class="hero-stat-val"><?= count($categories) ?></span>
                    <span class="hero-stat-label" data-i18n="statCategories">Categories</span>
                </div>
                <?php endif; ?>
                <?php if ($nbEpingles > 0): ?>
                <div class="hero-stat">
                    <span class="hero-stat-val"><?= $nbEpingles ?></span>
                    <span class="hero-stat-label" data-i18n="statFeatured">Featured picks</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="catalog-hero-aside">
            <div class="hero-right">🛍️</div>
        </div>
    </div>

    <!-- PINNED PRODUCTS -->
    <?php $epingles = array_filter($produits, fn($p) => !empty($p['estEpingle']));
    if (!empty($epingles)): ?>
    <div class="pinned-banner">
        <div class="pinned-banner-header">
            <span style="font-size:18px;">📌</span>
            <h2 data-i18n="featuredTitle">Featured Products</h2>
            <span class="count-pill" style="font-size:11px;"><?= count($epingles) ?> <span data-i18n="picks">picks</span></span>
        </div>
        <div class="pinned-grid">
            <?php foreach ($epingles as $ep): ?>
            <div class="pinned-card">
                <span class="pin-badge-fo" data-i18n="featuredBadge">Featured</span>
                <div class="pinned-thumb">
                    <?php if (!empty($ep['image'])): ?>
                        <img src="<?= htmlspecialchars(cre8_product_image_url($ep['image']), ENT_QUOTES, 'UTF-8') ?>" alt="">
                    <?php else: ?>📦<?php endif; ?>
                </div>
                <div class="pinned-name"><?= htmlspecialchars($ep['nomProduit']) ?></div>
                <div class="pinned-price"><?= number_format((float)$ep['prix'], 2, '.', ' ') ?> €</div>
                <?php if (!empty($ep['categorie'])): ?>
                    <div class="pinned-cat-fo">📂 <?= htmlspecialchars($ep['categorie']) ?></div>
                <?php endif; ?>
                <button class="btn-see-pinned" data-id="<?= $ep['idProduit'] ?>" data-i18n="seeProduct">See product →</button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- SECTION HEADER -->
    <div class="section-head">
        <div class="section-head-left">
            <h2 data-i18n="allProducts">All Products</h2>
            <span class="count-pill" id="visibleCount"><?= $totalProduits ?> <span data-i18n="productWord"><?= $totalProduits > 1 ? 'products' : 'product' ?></span></span>
        </div>
        <div class="tools-bar">
            <div class="search-wrap">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="searchInput" class="search-input" data-i18n-placeholder="searchPlaceholder" placeholder="Search products…">
            </div>
            <select id="sortSelect" class="sort-select">
                <option value="" data-i18n="sortDefault">Sort by…</option>
                <option value="nom" data-i18n="sortName">Name A→Z</option>
                <option value="prix_asc" data-i18n="sortPriceAsc">Price ↑</option>
                <option value="prix_desc" data-i18n="sortPriceDesc">Price ↓</option>
                <option value="epingle" data-i18n="sortFeatured">Featured first</option>
                <option value="rating_desc" data-i18n="sortRatingDesc">Highest rated</option>
                <option value="rating_asc" data-i18n="sortRatingAsc">Lowest rated</option>
                <option value="reviews_desc" data-i18n="sortMostReviewed">Most reviewed</option>
            </select>
            <div class="view-toggle">
                <button class="view-btn active" id="vbtn-grid" title="Grid">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                </button>
                <button class="view-btn" id="vbtn-list" title="List">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- CATEGORY FILTER -->
    <div class="cat-filter-bar" id="catFilterBar">
        <span class="cat-filter-label" data-i18n="categoryLabel">Category:</span>
        <button class="cat-chip active" data-i18n="catAll">All</button>
        <?php foreach ($categories as $cat): ?>
            <button class="cat-chip"><?= htmlspecialchars($cat) ?></button>
        <?php endforeach; ?>
    </div>

    <!-- PRICE RANGE FILTER -->
    <div class="price-filter-bar">
        <span class="pf-label" data-i18n="priceRange">Price range:</span>
        <div class="pf-range">
            <input type="text" id="priceMin" class="pf-input" data-i18n-placeholder="priceMin" placeholder="Min €">
            <span class="pf-sep">–</span>
            <input type="text" id="priceMax" class="pf-input" data-i18n-placeholder="priceMax" placeholder="Max €">
        </div>
        <?php if ($totalProduits > 0): ?>
        <span class="pf-stat" data-i18n-template="catalogRange" data-min="<?= number_format($prixMin, 0) ?>" data-max="<?= number_format($prixMax, 0) ?>">Catalog: <?= number_format($prixMin, 0) ?> € – <?= number_format($prixMax, 0) ?> €</span>
        <?php endif; ?>
        <button class="pf-reset" data-i18n="resetFilters">Reset filters</button>
    </div>

    <!-- PRODUCT GRID -->
    <?php if (empty($produits)): ?>
    <div class="empty-state">
        <span class="empty-icon">📦</span>
        <h3 data-i18n="emptyTitle">No products available yet</h3>
        <p data-i18n="emptySubtitle">Brands are publishing products for collaboration. Check back soon!</p>
    </div>
    <?php else: ?>
    <div class="products-grid" id="productsGrid">
        <?php $cardOrder = 0; foreach ($produits as $p):
            $productId   = (int) $p['idProduit'];
            $isPinned    = !empty($p['estEpingle']);
            $dispo       = $p['dateDisponibilite'] ?? null;
            $dispoFuture = $dispo && strtotime($dispo) > time();
            $tags        = array_filter(array_map('trim', explode(',', $p['caracteristiques'] ?? '')));
            $brandName   = trim((string) ($p['nomMarque'] ?? ''));
            $stats       = $reviewStats[$productId] ?? ['avgRating' => null, 'reviewCount' => 0];
            $avgRating   = $stats['avgRating'];
            $reviewCount = (int) ($stats['reviewCount'] ?? 0);
            $hasMyReview = isset($myProductReviews[$productId]);
        ?>
        <div class="product-card <?= $isPinned ? 'is-pinned' : '' ?>"
             data-id="<?= $productId ?>"
             data-name="<?= htmlspecialchars(strtolower($p['nomProduit'])) ?>"
             data-desc="<?= htmlspecialchars(strtolower($p['description'] ?? '')) ?>"
             data-prix="<?= (float)$p['prix'] ?>"
             data-cat="<?= htmlspecialchars(strtolower($p['categorie'] ?? '')) ?>"
             data-epingle="<?= (int)$isPinned ?>"
             data-rating="<?= $reviewCount > 0 ? htmlspecialchars((string) $avgRating, ENT_QUOTES, 'UTF-8') : '' ?>"
             data-review-count="<?= $reviewCount ?>"
             data-order="<?= $cardOrder++ ?>"
             data-img="<?= !empty($p['image']) ? htmlspecialchars(cre8_product_image_url($p['image']), ENT_QUOTES, 'UTF-8') : '' ?>"
             data-tags="<?= htmlspecialchars(implode(',', $tags)) ?>"
             data-dispo="<?= htmlspecialchars($dispo ?? '') ?>">

            <div class="pcard-img">
                <?php if ($isPinned): ?>
                    <div class="badge-epingle" data-i18n="featuredBadge">📌 Featured</div>
                <?php endif; ?>

                <?php if ($dispo): ?>
                    <div class="badge-dispo <?= $dispoFuture ? 'future' : 'available' ?>">
                        <?= $dispoFuture
                            ? '⏳ From ' . date('d/m/Y', strtotime($dispo))
                            : '✅ Available' ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($p['image'])): ?>
                    <img src="<?= htmlspecialchars(cre8_product_image_url($p['image']), ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars($p['nomProduit']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="pcard-img-empty">📦</div>
                <?php endif; ?>

                <div class="pcard-price-badge"><?= number_format((float)$p['prix'], 2, '.', ' ') ?> €</div>

                <div class="pcard-quickview">
                    <button class="btn-quickview">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span data-i18n="quickView">Quick view</span>
                    </button>
                </div>
            </div>

            <div class="pcard-body">
                <?php if (!empty($p['categorie'])): ?>
                    <div class="pcard-cat">
                        <span class="cat-dot"></span>
                        <?= htmlspecialchars($p['categorie']) ?>
                    </div>
                <?php endif; ?>
                <?php if ($brandName !== ''): ?>
                    <div class="product-brand-row">
                        <?= cre8_render_avatar((int) ($p['idMarque'] ?? 0), $brandName, 'cre8-avatar-sm') ?>
                        <span><?= htmlspecialchars($brandName) ?></span>
                    </div>
                <?php endif; ?>

                <div class="pcard-name"><?= htmlspecialchars($p['nomProduit']) ?></div>
                <div class="pcard-rating <?= $reviewCount > 0 ? '' : 'is-empty' ?>" data-rating-product="<?= $productId ?>">
                    <?= $reviewCount > 0
                        ? '★ ' . number_format((float) $avgRating, 1) . ' · ' . $reviewCount . ' ' . ($reviewCount === 1 ? 'review' : 'reviews')
                        : '☆ No reviews yet' ?>
                </div>
                <p class="pcard-desc"><?= htmlspecialchars($p['description'] ?? '') ?></p>

                <?php if ($dispo): ?>
                <div class="pcard-dispo-row">
                    <span class="<?= $dispoFuture ? 'dot-future' : 'dot-avail' ?>"></span>
                    <?= $dispoFuture
                        ? 'Available from ' . date('d/m/Y', strtotime($dispo))
                        : 'Available since ' . date('d/m/Y', strtotime($dispo)) ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($tags)): ?>
                <div class="pcard-tags">
                    <?php foreach (array_slice($tags, 0, 3) as $tag): ?>
                        <span class="pcard-tag">🏷️ <?= htmlspecialchars($tag) ?></span>
                    <?php endforeach; ?>
                    <?php if (count($tags) > 3): ?>
                        <span class="pcard-tag" style="background:var(--bg);color:var(--text-sub);">+<?= count($tags) - 3 ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="pcard-actions">
                    <button class="btn-see-detail">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span data-i18n="seeDetails">See details</span>
                    </button>
                    <?php if ($canReviewProducts): ?>
                    <button class="btn-review-product" data-review-button-product="<?= $productId ?>" data-i18n="<?= $hasMyReview ? 'editReview' : 'writeReview' ?>"><?= $hasMyReview ? '★ See / change your review' : '★ Write a review' ?></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ===== ADDED FEATURE: PAGINATION BAR ===== -->
    <div class="pagination-bar" id="paginationBar"></div>
    <!-- ===== END ADDED FEATURE ===== -->

    <div id="noResults" class="no-results" style="display:none;">
        <span class="icon">🔍</span>
        <h3 data-i18n="noMatchTitle">No products match</h3>
        <p data-i18n="noMatchSubtitle">Try adjusting your filters or search terms.</p>
    </div>
    <?php endif; ?>

</div>

<!-- DETAIL MODAL -->
<div class="detail-overlay" id="detailModal">
    <div class="detail-box">
        <div class="detail-img" id="detailImg">📦</div>
        <div class="detail-content">
            <button class="detail-close">✕</button>
            <div class="detail-cat" id="detailCat"></div>
            <div class="detail-name" id="detailName"></div>
            <div class="detail-price" id="detailPrice"></div>
            <div class="detail-rating is-empty" id="detailRating">☆ No reviews yet</div>
            <div class="detail-dispo" id="detailDispo"></div>
            <div class="detail-marque" id="detailMarque" style="display:none;"></div>
            <div class="detail-desc" id="detailDesc"></div>
            <div id="detailTagsWrap">
                <div class="detail-section-label" data-i18n="detailTagsLabel">Characteristics &amp; Tags</div>
                <div class="detail-carac" id="detailCarac"></div>
                <div class="detail-tags" id="detailTags" style="margin-top:8px;"></div>
            </div>
            <div class="reviews-panel">
                <div class="reviews-head">
                    <h4 data-i18n="reviewsTitle">Product reviews</h4>
                </div>
                <div class="review-filter-row" id="detailReviewFilters" aria-label="Review star filters">
                    <button type="button" class="review-filter-chip active" data-rating="" data-i18n="allStars">All stars</button>
                    <button type="button" class="review-filter-chip" data-rating="5">5★</button>
                    <button type="button" class="review-filter-chip" data-rating="4">4★</button>
                    <button type="button" class="review-filter-chip" data-rating="3">3★</button>
                    <button type="button" class="review-filter-chip" data-rating="2">2★</button>
                    <button type="button" class="review-filter-chip" data-rating="1">1★</button>
                </div>
                <div class="review-list" id="detailReviews"></div>
                <div class="review-pager" id="detailReviewPager"></div>
            </div>
            <div class="detail-actions">
                <?php if ($canReviewProducts): ?>
                <button class="btn-primary-detail" id="detailReviewBtn" data-i18n="writeReview">★ Write a review</button>
                <?php endif; ?>
                <button class="btn-close-detail" data-i18n="closeBtn">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- REVIEW MODAL -->
<div class="review-overlay" id="reviewModal" aria-hidden="true">
    <div class="review-box" role="dialog" aria-modal="true" aria-labelledby="reviewModalTitle">
        <div class="review-box-head">
            <div>
                <h3 id="reviewModalTitle" data-i18n="reviewModalTitle">Write a review</h3>
                <div class="review-product-name" id="reviewProductName"></div>
            </div>
            <button type="button" class="review-close" id="reviewCloseBtn" aria-label="Close">×</button>
        </div>
        <form id="reviewForm">
            <input type="hidden" name="action" value="save_review">
            <input type="hidden" name="idProduit" id="reviewProductId" value="">
            <input type="hidden" name="rating" id="reviewRatingValue" value="">
            <div class="review-field">
                <label data-i18n="ratingLabel">Rating</label>
                <div class="star-picker" id="reviewStars" aria-label="Rating">
                    <button type="button" data-rating="1">★</button>
                    <button type="button" data-rating="2">★</button>
                    <button type="button" data-rating="3">★</button>
                    <button type="button" data-rating="4">★</button>
                    <button type="button" data-rating="5">★</button>
                </div>
            </div>
            <div class="review-field">
                <label for="reviewTitle" data-i18n="reviewTitleLabel">Title</label>
                <input type="text" id="reviewTitle" name="title" maxlength="150" data-i18n-placeholder="reviewTitlePlaceholder" placeholder="What stood out?">
            </div>
            <div class="review-field">
                <label for="reviewText" data-i18n="reviewTextLabel">Review</label>
                <textarea id="reviewText" name="reviewText" maxlength="2000" data-i18n-placeholder="reviewTextPlaceholder" placeholder="Share your experience with this product."></textarea>
            </div>
            <div class="review-message" id="reviewMessage"></div>
            <div class="review-actions">
                <button type="button" class="review-cancel" id="reviewCancelBtn" data-i18n="cancelReview">Cancel</button>
                <button type="submit" class="review-submit" id="reviewSubmitBtn" data-i18n="saveReview">Save review</button>
            </div>
        </form>
    </div>
</div>

<script>
function validatePriceInput(el) {
    let v = parseFloat(el.value);
    if (!isNaN(v) && v < 0) { el.value = '0'; }
}
function restrictToPrice(el) {
    let v = el.value.replace(/[^0-9.]/g, '');
    let parts = v.split('.');
    if (parts.length > 2) { v = parts[0] + '.' + parts.slice(1).join(''); }
    el.value = v;
}
document.getElementById('priceMin').addEventListener('input', function() { restrictToPrice(this); validatePriceInput(this); filterAndPaginate(); });
document.getElementById('priceMax').addEventListener('input', function() { restrictToPrice(this); validatePriceInput(this); filterAndPaginate(); });
document.getElementById('searchInput').addEventListener('input', filterAndPaginate);
document.getElementById('sortSelect').addEventListener('change', sortProducts);
document.getElementById('vbtn-grid').addEventListener('click', () => setView('grid'));
document.getElementById('vbtn-list').addEventListener('click', () => setView('list'));
const BASE_URL = <?= json_encode($baseUrl, JSON_UNESCAPED_SLASHES) ?>;

// ── PRODUCT DATA ───────────────────────────────────────────────────
const produitsMap = {};
<?php foreach ($produits as $p): ?>
<?php
    $productId = (int) $p['idProduit'];
    $brandName = trim((string) ($p['nomMarque'] ?? ''));
    $brandHtml = $brandName !== ''
        ? cre8_render_avatar((int) ($p['idMarque'] ?? 0), $brandName, 'cre8-avatar-sm') . '<span>' . htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') . '</span>'
        : '';
    $stats = $reviewStats[$productId] ?? ['avgRating' => null, 'reviewCount' => 0];
?>
produitsMap[<?= $productId ?>] = {
    id:     <?= $productId ?>,
    nom:    <?= json_encode($p['nomProduit']) ?>,
    desc:   <?= json_encode($p['description'] ?? '') ?>,
    carac:  <?= json_encode($p['caracteristiques'] ?? '') ?>,
    prix:   <?= (float)$p['prix'] ?>,
    img:    <?= json_encode(!empty($p['image']) ? cre8_product_image_url($p['image']) : null) ?>,
    cat:    <?= json_encode($p['categorie'] ?? '') ?>,
    dispo:  <?= json_encode($p['dateDisponibilite'] ?? '') ?>,
    marque: <?= json_encode($brandName !== '' ? $brandName : (($p['idMarque'] ?? null) ? 'Brand ID #' . $p['idMarque'] : null)) ?>,
    marqueHtml: <?= json_encode($brandHtml) ?>,
    reviewStats: <?= json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
    reviews: <?= json_encode($productReviews[$productId] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
    myReview: <?= json_encode($myProductReviews[$productId] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
};
<?php endforeach; ?>

const CAN_REVIEW_PRODUCTS = <?= $canReviewProducts ? 'true' : 'false' ?>;
const INITIAL_SORT = <?= json_encode($initialSort) ?>;
const DETAIL_REVIEWS_PER_PAGE = 5;
let currentDetailProductId = null;
let activeReviewProductId = null;
let detailReviewPage = 1;
let detailReviewRating = '';

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[ch]));
}

function formatReviewSummary(stats) {
    const count = parseInt(stats?.reviewCount || 0, 10);
    if (!count) return '☆ No reviews yet';
    const avg = Number(stats.avgRating || 0).toFixed(1);
    return `★ ${avg} · ${count} ${count === 1 ? 'review' : 'reviews'}`;
}

function getReviewButtonLabel(product) {
    const T = translations[currentLang] || translations.en;
    return product && product.myReview
        ? (T.editReview || '★ See / change your review')
        : (T.writeReview || '★ Write a review');
}

function renderStars(rating) {
    const value = Math.max(0, Math.min(5, parseInt(rating || 0, 10)));
    return '★'.repeat(value) + '☆'.repeat(5 - value);
}

function updateProductRatingUI(productId) {
    const p = produitsMap[productId];
    if (!p) return;
    const hasReviews = !!(p.reviewStats && parseInt(p.reviewStats.reviewCount || 0, 10) > 0);
    const rating = hasReviews ? Number(p.reviewStats.avgRating || 0) : 0;
    const count = parseInt(p.reviewStats.reviewCount || 0, 10);
    document.querySelectorAll(`[data-rating-product="${productId}"]`).forEach(el => {
        el.textContent = formatReviewSummary(p.reviewStats || {});
        el.classList.toggle('is-empty', !hasReviews);
    });
    document.querySelectorAll(`.product-card[data-id="${productId}"]`).forEach(card => {
        card.dataset.rating = hasReviews ? String(rating) : '';
        card.dataset.reviewCount = String(count);
    });
    updateReviewButtons(productId);
    if (String(currentDetailProductId) === String(productId)) {
        const detailRating = document.getElementById('detailRating');
        detailRating.textContent = formatReviewSummary(p.reviewStats || {});
        detailRating.classList.toggle('is-empty', !hasReviews);
        fetchDetailReviews(productId, detailReviewPage, detailReviewRating);
    }
}

function updateReviewButtons(productId) {
    if (!CAN_REVIEW_PRODUCTS) return;
    const p = produitsMap[productId];
    if (!p) return;
    const label = getReviewButtonLabel(p);
    const key = p.myReview ? 'editReview' : 'writeReview';
    document.querySelectorAll(`[data-review-button-product="${productId}"]`).forEach(btn => {
        btn.setAttribute('data-i18n', key);
        btn.textContent = label;
    });
    if (String(currentDetailProductId) === String(productId)) {
        const detailBtn = document.getElementById('detailReviewBtn');
        if (detailBtn) {
            detailBtn.setAttribute('data-i18n', key);
            detailBtn.textContent = label;
        }
    }
}

function renderDetailReviews(product, reviewPage = null) {
    const wrap = document.getElementById('detailReviews');
    const pager = document.getElementById('detailReviewPager');
    if (!wrap) return;
    const pageData = reviewPage || {
        reviews: Array.isArray(product.reviews) ? product.reviews.slice(0, DETAIL_REVIEWS_PER_PAGE) : [],
        total: Array.isArray(product.reviews) ? product.reviews.length : 0,
        page: 1,
        pages: Math.max(1, Math.ceil((Array.isArray(product.reviews) ? product.reviews.length : 0) / DETAIL_REVIEWS_PER_PAGE)),
        rating: detailReviewRating || null
    };
    const reviews = Array.isArray(pageData.reviews) ? pageData.reviews : [];
    const T = translations[currentLang] || translations.en;
    if (!reviews.length) {
        wrap.innerHTML = `<div class="review-empty">${escapeHtml(pageData.rating ? (T.noReviewsMatch || 'No reviews match this filter.') : (T.noReviewsYet || 'No reviews yet.'))}</div>`;
        if (pager) pager.innerHTML = '';
        return;
    }

    wrap.innerHTML = reviews.map(review => {
        const date = review.createdAt ? new Date(review.createdAt.replace(' ', 'T')) : null;
        const formattedDate = date && !Number.isNaN(date.getTime())
            ? date.toLocaleDateString(currentLang === 'fr' ? 'fr-FR' : 'en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
            : '';
        return `
            <article class="review-item">
                <div class="review-top">
                    <span class="review-author">${escapeHtml(review.creatorName || 'Creator')}</span>
                    <span class="review-stars">${renderStars(review.rating)}</span>
                </div>
                <h5 class="review-title">${escapeHtml(review.title || '')}</h5>
                <p class="review-text">${escapeHtml(review.reviewText || '')}</p>
                ${formattedDate ? `<div class="review-date">${escapeHtml(formattedDate)}</div>` : ''}
            </article>
        `;
    }).join('');
    renderDetailReviewPager(pageData);
}

function renderDetailReviewPager(pageData) {
    const pager = document.getElementById('detailReviewPager');
    if (!pager) return;
    const pages = Math.max(1, parseInt(pageData.pages || 1, 10));
    const page = Math.max(1, parseInt(pageData.page || 1, 10));
    if (pages <= 1) {
        pager.innerHTML = '';
        return;
    }
    const T = translations[currentLang] || translations.en;
    let html = `<button class="pg-btn" type="button" data-review-page="${page - 1}" ${page <= 1 ? 'disabled' : ''}>${escapeHtml(T.prevPage || '← Prev')}</button>`;
    for (let i = 1; i <= pages; i++) {
        if (i === 1 || i === pages || Math.abs(i - page) <= 1) {
            html += `<button class="pg-btn${i === page ? ' current' : ''}" type="button" data-review-page="${i}">${i}</button>`;
        } else if (i === page - 2 || i === page + 2) {
            html += `<span class="pg-dots">…</span>`;
        }
    }
    html += `<button class="pg-btn" type="button" data-review-page="${page + 1}" ${page >= pages ? 'disabled' : ''}>${escapeHtml(T.nextPage || 'Next →')}</button>`;
    pager.innerHTML = html;
    pager.querySelectorAll('[data-review-page]').forEach(btn => {
        btn.addEventListener('click', () => fetchDetailReviews(currentDetailProductId, parseInt(btn.dataset.reviewPage || '1', 10), detailReviewRating));
    });
}

async function fetchDetailReviews(productId, page = 1, rating = '') {
    if (!productId) return;
    const wrap = document.getElementById('detailReviews');
    const pager = document.getElementById('detailReviewPager');
    const T = translations[currentLang] || translations.en;
    detailReviewPage = Math.max(1, parseInt(page || 1, 10));
    detailReviewRating = rating ? String(rating) : '';
    if (wrap) wrap.innerHTML = `<div class="review-loading">${escapeHtml(T.reviewsLoading || 'Loading reviews...')}</div>`;
    if (pager) pager.innerHTML = '';

    try {
        const url = new URL(window.location.href);
        url.searchParams.set('action', 'product_reviews');
        url.searchParams.set('idProduit', String(productId));
        url.searchParams.set('page', String(detailReviewPage));
        url.searchParams.set('per_page', String(DETAIL_REVIEWS_PER_PAGE));
        if (detailReviewRating) {
            url.searchParams.set('rating', detailReviewRating);
        } else {
            url.searchParams.delete('rating');
        }
        const response = await fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });
        const payload = await response.json();
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Reviews could not be loaded.');
        }
        const p = produitsMap[productId];
        if (p && payload.stats) {
            p.reviewStats = payload.stats;
            const hasReviews = parseInt(p.reviewStats.reviewCount || 0, 10) > 0;
            document.querySelectorAll(`[data-rating-product="${productId}"]`).forEach(el => {
                el.textContent = formatReviewSummary(p.reviewStats || {});
                el.classList.toggle('is-empty', !hasReviews);
            });
            document.querySelectorAll(`.product-card[data-id="${productId}"]`).forEach(card => {
                card.dataset.rating = hasReviews ? String(Number(p.reviewStats.avgRating || 0)) : '';
                card.dataset.reviewCount = String(parseInt(p.reviewStats.reviewCount || 0, 10));
            });
            const detailRating = document.getElementById('detailRating');
            if (detailRating && String(currentDetailProductId) === String(productId)) {
                detailRating.textContent = formatReviewSummary(p.reviewStats || {});
                detailRating.classList.toggle('is-empty', !hasReviews);
            }
        }
        renderDetailReviews(p || {}, payload.reviewPage || null);
    } catch (error) {
        if (wrap) wrap.innerHTML = `<div class="review-empty">${escapeHtml(error.message || 'Reviews could not be loaded.')}</div>`;
    }
}

function setReviewRating(value) {
    const parsed = parseInt(value || 0, 10);
    const rating = parsed > 0 ? Math.max(1, Math.min(5, parsed)) : 0;
    document.getElementById('reviewRatingValue').value = rating ? String(rating) : '';
    document.querySelectorAll('#reviewStars button').forEach(btn => {
        btn.classList.toggle('is-selected', parseInt(btn.dataset.rating || 0, 10) <= rating);
    });
}

function openReviewModal(productId) {
    if (!CAN_REVIEW_PRODUCTS) return;
    const p = produitsMap[productId];
    if (!p) return;

    activeReviewProductId = productId;
    const existing = p.myReview || null;
    const T = translations[currentLang] || translations.en;
    document.getElementById('reviewProductId').value = String(productId);
    document.getElementById('reviewProductName').textContent = p.nom || '';
    document.getElementById('reviewTitle').value = existing?.title || '';
    document.getElementById('reviewText').value = existing?.reviewText || '';
    document.getElementById('reviewMessage').textContent = '';
    document.getElementById('reviewMessage').classList.remove('success');
    document.getElementById('reviewSubmitBtn').textContent = existing ? (T.updateReview || 'Update review') : (T.saveReview || 'Save review');
    setReviewRating(existing?.rating || 0);

    const modal = document.getElementById('reviewModal');
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
    activeReviewProductId = null;
    if (!document.getElementById('detailModal').classList.contains('open')) {
        document.body.style.overflow = '';
    }
}

async function submitReviewForm(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const message = document.getElementById('reviewMessage');
    const submit = document.getElementById('reviewSubmitBtn');
    const T = translations[currentLang] || translations.en;
    message.classList.remove('success');
    message.textContent = '';

    if (!document.getElementById('reviewRatingValue').value) {
        message.textContent = T.ratingRequired || 'Please choose a rating.';
        return;
    }

    submit.disabled = true;
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        });
        const text = await response.text();
        let payload;
        try {
            payload = JSON.parse(text);
        } catch (error) {
            throw new Error('The review server returned an invalid response.');
        }
        if (!response.ok || !payload.success) {
            throw new Error(payload.message || 'Review could not be saved.');
        }

        const productId = document.getElementById('reviewProductId').value;
        const p = produitsMap[productId];
        if (p) {
            p.myReview = payload.review || p.myReview;
            p.reviewStats = payload.stats || p.reviewStats;
            if (payload.reviewPage && Array.isArray(payload.reviewPage.reviews)) {
                p.reviews = payload.reviewPage.reviews;
            }
            detailReviewPage = 1;
            detailReviewRating = '';
            document.querySelectorAll('#detailReviewFilters .review-filter-chip').forEach(chip => {
                chip.classList.toggle('active', chip.dataset.rating === '');
            });
            updateProductRatingUI(productId);
        }
        message.classList.add('success');
        message.textContent = T.reviewSaved || 'Review saved.';
        setTimeout(closeReviewModal, 450);
    } catch (error) {
        message.textContent = error.message || 'Review could not be saved.';
    } finally {
        submit.disabled = false;
    }
}

// ── CATEGORY FILTER ────────────────────────────────────────────────
let activeCategory = '';
function filterByCategory(cat, btn) {
    activeCategory = cat.toLowerCase();
    document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    sortProducts();
}

// ── FILTER + SEARCH (now calls paginator) ─────────────────────────
function filterAndPaginate() {
    currentPage = 1;
    applyPagination();
}

function getFilteredCards() {
    const q    = document.getElementById('searchInput').value.toLowerCase().trim();
    const minP = parseFloat(document.getElementById('priceMin').value) || 0;
    const maxP = parseFloat(document.getElementById('priceMax').value) || Infinity;
    return Array.from(document.querySelectorAll('#productsGrid .product-card')).filter(card => {
        const name = card.dataset.name || '';
        const desc = card.dataset.desc || '';
        const prix = parseFloat(card.dataset.prix) || 0;
        const cat  = card.dataset.cat  || '';
        const tags = (card.dataset.tags || '').toLowerCase();
        const matchText  = !q || name.includes(q) || desc.includes(q) || tags.includes(q);
        const matchPrice = prix >= minP && prix <= maxP;
        const matchCat   = !activeCategory || cat === activeCategory;
        return matchText && matchPrice && matchCat;
    });
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('priceMin').value = '';
    document.getElementById('priceMax').value = '';
    document.getElementById('sortSelect').value = '';
    activeCategory = '';
    document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
    const firstChip = document.querySelector('.cat-chip');
    if (firstChip) firstChip.classList.add('active');
    sortProducts();
}

// ── SORT ───────────────────────────────────────────────────────────
function sortProducts() {
    const mode = document.getElementById('sortSelect').value;
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    const cards = Array.from(grid.querySelectorAll('.product-card'));
    const reviewedFirst = (card) => parseInt(card.dataset.reviewCount || 0, 10) > 0 ? 0 : 1;
    cards.sort((a, b) => {
        if (!mode) return parseInt(a.dataset.order || 0, 10) - parseInt(b.dataset.order || 0, 10);
        if (mode === 'nom')       return (a.dataset.name || '').localeCompare(b.dataset.name || '');
        if (mode === 'prix_asc')  return parseFloat(a.dataset.prix) - parseFloat(b.dataset.prix);
        if (mode === 'prix_desc') return parseFloat(b.dataset.prix) - parseFloat(a.dataset.prix);
        if (mode === 'epingle')   return parseInt(b.dataset.epingle || 0) - parseInt(a.dataset.epingle || 0);
        if (mode === 'rating_desc') {
            return reviewedFirst(a) - reviewedFirst(b)
                || parseFloat(b.dataset.rating || 0) - parseFloat(a.dataset.rating || 0)
                || parseInt(b.dataset.reviewCount || 0, 10) - parseInt(a.dataset.reviewCount || 0, 10);
        }
        if (mode === 'rating_asc') {
            return reviewedFirst(a) - reviewedFirst(b)
                || parseFloat(a.dataset.rating || 0) - parseFloat(b.dataset.rating || 0)
                || parseInt(b.dataset.reviewCount || 0, 10) - parseInt(a.dataset.reviewCount || 0, 10);
        }
        if (mode === 'reviews_desc') {
            return parseInt(b.dataset.reviewCount || 0, 10) - parseInt(a.dataset.reviewCount || 0, 10)
                || parseFloat(b.dataset.rating || 0) - parseFloat(a.dataset.rating || 0);
        }
        return 0;
    });
    cards.forEach(c => grid.appendChild(c));
    applyPagination();
}

// ── VIEW TOGGLE ────────────────────────────────────────────────────
function setView(mode) {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    grid.className = 'products-grid' + (mode === 'list' ? ' view-list' : '');
    document.getElementById('vbtn-grid').classList.toggle('active', mode === 'grid');
    document.getElementById('vbtn-list').classList.toggle('active', mode === 'list');
}

// ── DETAIL MODAL ───────────────────────────────────────────────────
function openDetail(e, id) {
    if (e && e.stopPropagation) e.stopPropagation();
    if (id === undefined) { id = e; e = null; }
    const p = produitsMap[id];
    if (!p) return;
    currentDetailProductId = id;

    const imgEl = document.getElementById('detailImg');
    imgEl.innerHTML = p.img
        ? `<img src="${p.img}" alt="${p.nom}" style="width:100%;height:100%;object-fit:cover;">`
        : '📦';

    const catEl = document.getElementById('detailCat');
    catEl.textContent = p.cat ? '📂 ' + p.cat.charAt(0).toUpperCase() + p.cat.slice(1) : '';

    document.getElementById('detailName').textContent  = p.nom;
    document.getElementById('detailPrice').textContent = p.prix.toFixed(2) + ' €';

    const hasReviews = !!(p.reviewStats && parseInt(p.reviewStats.reviewCount || 0, 10) > 0);
    const detailRating = document.getElementById('detailRating');
    detailRating.textContent = formatReviewSummary(p.reviewStats || {});
    detailRating.classList.toggle('is-empty', !hasReviews);
    const T = translations[currentLang] || translations['en'];
    const dispoEl = document.getElementById('detailDispo');
    if (p.dispo) {
        const d = new Date(p.dispo);
        const isFuture = d > new Date();
        const formatted = d.toLocaleDateString(currentLang === 'fr' ? 'fr-FR' : 'en-GB', {day:'2-digit', month:'long', year:'numeric'});
        dispoEl.textContent = (isFuture ? (T.availableFrom || 'Available from') : (T.availableSince || 'Available since')) + ' ' + formatted;
        dispoEl.className = 'detail-dispo ' + (isFuture ? 'future' : 'available');
    } else {
        dispoEl.textContent = T.availableNow || '✅ Available now';
        dispoEl.className = 'detail-dispo available';
    }

    const marqueEl = document.getElementById('detailMarque');
    if (p.marqueHtml) {
        marqueEl.innerHTML = p.marqueHtml;
        marqueEl.style.display = 'inline-flex';
    } else if (p.marque) {
        marqueEl.textContent = p.marque;
        marqueEl.style.display = 'inline-flex';
    }
    else { marqueEl.style.display = 'none'; }

    document.getElementById('detailDesc').textContent = p.desc || (T.noDescription || 'No description available.');
    document.getElementById('detailCarac').textContent = p.carac || '—';
    const tagsEl = document.getElementById('detailTags');
    const tags = (p.carac || '').split(',').map(t => t.trim()).filter(Boolean);
    tagsEl.innerHTML = tags.map(t => `<span class="detail-tag">🏷️ ${t}</span>`).join('');

    detailReviewPage = 1;
    detailReviewRating = '';
    document.querySelectorAll('#detailReviewFilters .review-filter-chip').forEach(chip => {
        chip.classList.toggle('active', chip.dataset.rating === '');
    });
    const detailReviewBtn = document.getElementById('detailReviewBtn');
    if (detailReviewBtn) {
        detailReviewBtn.setAttribute('data-product-id', id);
        detailReviewBtn.setAttribute('data-i18n', p.myReview ? 'editReview' : 'writeReview');
        detailReviewBtn.textContent = getReviewButtonLabel(p);
    }
    fetchDetailReviews(id, 1, '');
    document.getElementById('detailModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeDetail() {
    document.getElementById('detailModal').classList.remove('open');
    currentDetailProductId = null;
    document.body.style.overflow = '';
}
function closeDetailOutside(e) {
    if (e.target === document.getElementById('detailModal')) closeDetail();
}

<?php if ($produitDetail): ?>
document.addEventListener('DOMContentLoaded', () => openDetail(<?= $produitDetail['idProduit'] ?>));
<?php endif; ?>

document.addEventListener('DOMContentLoaded', () => {
    if (INITIAL_SORT) {
        document.getElementById('sortSelect').value = INITIAL_SORT;
    }
    // Category chips
    document.querySelectorAll('.cat-chip').forEach((chip, index) => {
        const cat = index === 0 ? '' : chip.textContent.trim().toLowerCase();
        chip.addEventListener('click', () => filterByCategory(cat, chip));
    });

    // Reset filters button
    document.querySelector('.pf-reset').addEventListener('click', resetFilters);

    // Pinned product buttons
    document.querySelectorAll('.btn-see-pinned').forEach(btn => {
        const id = btn.dataset.id;
        btn.addEventListener('click', () => openDetail(id));
    });
    document.querySelectorAll('.btn-quickview').forEach(btn => {
        const id = btn.closest('.product-card').dataset.id;
        btn.addEventListener('click', (e) => openDetail(e, id));
    });
    document.querySelectorAll('.btn-see-detail').forEach(btn => {
        const id = btn.closest('.product-card').dataset.id;
        btn.addEventListener('click', (e) => openDetail(e, id));
    });
    document.querySelectorAll('.btn-review-product').forEach(btn => {
        const id = btn.closest('.product-card').dataset.id;
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            openReviewModal(id);
        });
    });

    // Modal close
    document.getElementById('detailModal').addEventListener('click', closeDetailOutside);
    document.querySelector('.detail-close').addEventListener('click', closeDetail);
    document.querySelector('.btn-primary-detail')?.addEventListener('click', () => {
        if (currentDetailProductId) openReviewModal(currentDetailProductId);
    });
    document.querySelector('.btn-close-detail').addEventListener('click', closeDetail);
    document.getElementById('reviewForm')?.addEventListener('submit', submitReviewForm);
    document.getElementById('reviewCloseBtn')?.addEventListener('click', closeReviewModal);
    document.getElementById('reviewCancelBtn')?.addEventListener('click', closeReviewModal);
    document.getElementById('reviewModal')?.addEventListener('click', e => {
        if (e.target === document.getElementById('reviewModal')) closeReviewModal();
    });
    document.querySelectorAll('#reviewStars button').forEach(btn => {
        btn.addEventListener('click', () => setReviewRating(btn.dataset.rating));
    });
    document.querySelectorAll('#detailReviewFilters .review-filter-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            document.querySelectorAll('#detailReviewFilters .review-filter-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            fetchDetailReviews(currentDetailProductId, 1, chip.dataset.rating || '');
        });
    });

    // Init pagination on load
    applyPagination();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        if (document.getElementById('reviewModal')?.classList.contains('open')) closeReviewModal();
        else closeDetail();
    }
});


// ═══════════════════════════════════════════════════════════════════
// ===== ADDED FEATURE: PAGINATION =====
// ═══════════════════════════════════════════════════════════════════
const ITEMS_PER_PAGE = 9; // 9 cards per page fits neatly in a 3-col grid
let currentPage = 1;

function applyPagination() {
    const T = translations[currentLang] || translations['en'];
    const allCards = Array.from(document.querySelectorAll('#productsGrid .product-card'));
    const filtered = getFilteredCards();
    const total    = filtered.length;
    const pages    = Math.max(1, Math.ceil(total / ITEMS_PER_PAGE));
    currentPage    = Math.min(currentPage, pages);

    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    const end   = start + ITEMS_PER_PAGE;

    // Hide all, show only current page of filtered
    allCards.forEach(c => c.style.display = 'none');
    filtered.forEach((c, i) => { c.style.display = (i >= start && i < end) ? '' : 'none'; });

    // Update count pill
    const countEl = document.getElementById('visibleCount');
    if (countEl) {
        const word = total === 1 ? (T.productSingular || 'product') : (T.productPlural || 'products');
        countEl.textContent = total + ' ' + word;
    }

    // No results state
    const noRes = document.getElementById('noResults');
    if (noRes) noRes.style.display = total === 0 ? '' : 'none';

    // Render pagination bar
    renderPaginationBar(pages, T);
}

function renderPaginationBar(pages, T) {
    const bar = document.getElementById('paginationBar');
    if (!bar) return;
    if (pages <= 1) { bar.innerHTML = ''; return; }

    let html = '';

    // Prev
    html += `<button class="pg-btn" onclick="goToPage(${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''}>
        ${T.prevPage || '←'}
    </button>`;

    // Page numbers
    const range = 2;
    for (let i = 1; i <= pages; i++) {
        if (i === 1 || i === pages || (i >= currentPage - range && i <= currentPage + range)) {
            html += `<button class="pg-btn${i === currentPage ? ' current' : ''}" onclick="goToPage(${i})">${i}</button>`;
        } else if (i === currentPage - range - 1 || i === currentPage + range + 1) {
            html += `<span class="pg-dots">…</span>`;
        }
    }

    // Next
    html += `<button class="pg-btn" onclick="goToPage(${currentPage + 1})" ${currentPage >= pages ? 'disabled' : ''}>
        ${T.nextPage || '→'}
    </button>`;

    // Info
    const total = getFilteredCards().length;
    const start = (currentPage - 1) * ITEMS_PER_PAGE + 1;
    const end   = Math.min(currentPage * ITEMS_PER_PAGE, total);
    html += `<span class="pg-info">${T.pageOf || 'Page'} ${currentPage} ${T.of || 'of'} ${pages} · ${start}–${end} ${T.of || 'of'} ${total}</span>`;

    bar.innerHTML = html;
}

function goToPage(page) {
    const pages = Math.max(1, Math.ceil(getFilteredCards().length / ITEMS_PER_PAGE));
    if (page < 1 || page > pages) return;
    currentPage = page;
    applyPagination();
    // Smooth scroll back to top of product grid
    document.getElementById('productsGrid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
// ===== END ADDED FEATURE: PAGINATION =====


// ═══════════════════════════════════════════════════════════════════
// ===== ADDED FEATURE: TRANSLATION SYSTEM =====
// ═══════════════════════════════════════════════════════════════════
const translations = {
    en: {
        // Nav
        navDashboard: 'Dashboard',
        navOffers: 'Offers',
        navProducts: 'Products',
        navEvents: 'Events',
        navForum: 'Forum',
        roleCreator: 'Creator',
        // Theme
        themeDark: 'Dark',
        themeLight: 'Light',
        // Hero
        heroTitle: 'Discover Products\nMade for Creators',
        heroSubtitle: 'Browse the full catalog of products brands have made available for collaboration. Find what fits your audience and apply for campaigns.',
        statProducts: 'Products available',
        statCategories: 'Categories',
        statFeatured: 'Featured picks',
        // Featured
        featuredTitle: 'Featured Products',
        picks: 'picks',
        featuredBadge: '📌 Featured',
        seeProduct: 'See product →',
        // Section
        allProducts: 'All Products',
        productSingular: 'product',
        productPlural: 'products',
        productWord: 'products',
        // Search & sort
        searchPlaceholder: 'Search products…',
        sortDefault: 'Sort by…',
        sortName: 'Name A→Z',
        sortPriceAsc: 'Price ↑',
        sortPriceDesc: 'Price ↓',
        sortFeatured: 'Featured first',
        sortRatingDesc: 'Highest rated',
        sortRatingAsc: 'Lowest rated',
        sortMostReviewed: 'Most reviewed',
        // Filters
        categoryLabel: 'Category:',
        catAll: 'All',
        priceRange: 'Price range:',
        priceMin: 'Min €',
        priceMax: 'Max €',
        catalogRangePrefix: 'Catalog:',
        resetFilters: 'Reset filters',
        // Cards
        quickView: 'Quick view',
        seeDetails: 'See details',
        writeReview: '★ Write a review',
        editReview: '★ See / change your review',
        // Empty & no results
        emptyTitle: 'No products available yet',
        emptySubtitle: 'Brands are publishing products for collaboration. Check back soon!',
        noMatchTitle: 'No products match',
        noMatchSubtitle: 'Try adjusting your filters or search terms.',
        // Detail modal
        detailTagsLabel: 'Characteristics & Tags',
        applyBtn: '★ Write a review',
        reviewsTitle: 'Product reviews',
        allStars: 'All stars',
        noReviewsYet: 'No reviews yet.',
        noReviewsMatch: 'No reviews match this filter.',
        reviewsLoading: 'Loading reviews...',
        reviewModalTitle: 'Write a review',
        ratingLabel: 'Rating',
        reviewTitleLabel: 'Title',
        reviewTitlePlaceholder: 'What stood out?',
        reviewTextLabel: 'Review',
        reviewTextPlaceholder: 'Share your experience with this product.',
        saveReview: 'Save review',
        updateReview: 'Update review',
        cancelReview: 'Cancel',
        ratingRequired: 'Please choose a rating.',
        reviewSaved: 'Review saved.',
        closeBtn: 'Close',
        availableFrom: 'Available from',
        availableSince: 'Available since',
        availableNow: '✅ Available now',
        noDescription: 'No description available.',
        // Pagination
        prevPage: '← Prev',
        nextPage: 'Next →',
        pageOf: 'Page',
        of: 'of',
    },
    fr: {
        // Nav
        navDashboard: 'Tableau de bord',
        navOffers: 'Offres',
        navProducts: 'Produits',
        navEvents: 'Événements',
        navForum: 'Forum',
        roleCreator: 'Créateur',
        // Theme
        themeDark: 'Sombre',
        themeLight: 'Clair',
        // Hero
        heroTitle: 'Découvrez les Produits\nFaits pour les Créateurs',
        heroSubtitle: 'Parcourez le catalogue complet des produits mis à disposition par les marques. Trouvez ce qui correspond à votre audience et postulez aux campagnes.',
        statProducts: 'Produits disponibles',
        statCategories: 'Catégories',
        statFeatured: 'Sélections vedettes',
        // Featured
        featuredTitle: 'Produits en Vedette',
        picks: 'sélections',
        featuredBadge: '📌 Vedette',
        seeProduct: 'Voir le produit →',
        // Section
        allProducts: 'Tous les Produits',
        productSingular: 'produit',
        productPlural: 'produits',
        productWord: 'produits',
        // Search & sort
        searchPlaceholder: 'Rechercher des produits…',
        sortDefault: 'Trier par…',
        sortName: 'Nom A→Z',
        sortPriceAsc: 'Prix ↑',
        sortPriceDesc: 'Prix ↓',
        sortFeatured: 'Vedettes en premier',
        sortRatingDesc: 'Mieux notés',
        sortRatingAsc: 'Moins bien notés',
        sortMostReviewed: 'Plus d’avis',
        // Filters
        categoryLabel: 'Catégorie :',
        catAll: 'Toutes',
        priceRange: 'Gamme de prix :',
        priceMin: 'Min €',
        priceMax: 'Max €',
        catalogRangePrefix: 'Catalogue :',
        resetFilters: 'Réinitialiser',
        // Cards
        quickView: 'Aperçu rapide',
        seeDetails: 'Voir les détails',
        writeReview: '★ Écrire un avis',
        editReview: '★ Voir / modifier votre avis',
        // Empty & no results
        emptyTitle: 'Aucun produit disponible',
        emptySubtitle: 'Les marques publient des produits pour collaborer. Revenez bientôt !',
        noMatchTitle: 'Aucun produit ne correspond',
        noMatchSubtitle: 'Essayez d\'ajuster vos filtres ou votre recherche.',
        // Detail modal
        detailTagsLabel: 'Caractéristiques & Tags',
        applyBtn: '★ Écrire un avis',
        reviewsTitle: 'Avis produit',
        allStars: 'Toutes les notes',
        noReviewsYet: 'Aucun avis pour le moment.',
        noReviewsMatch: 'Aucun avis ne correspond à ce filtre.',
        reviewsLoading: 'Chargement des avis...',
        reviewModalTitle: 'Écrire un avis',
        ratingLabel: 'Note',
        reviewTitleLabel: 'Titre',
        reviewTitlePlaceholder: 'Qu’est-ce qui ressort ?',
        reviewTextLabel: 'Avis',
        reviewTextPlaceholder: 'Partagez votre expérience avec ce produit.',
        saveReview: 'Enregistrer l’avis',
        updateReview: 'Mettre à jour l’avis',
        cancelReview: 'Annuler',
        ratingRequired: 'Veuillez choisir une note.',
        reviewSaved: 'Avis enregistré.',
        closeBtn: 'Fermer',
        availableFrom: 'Disponible à partir du',
        availableSince: 'Disponible depuis le',
        availableNow: '✅ Disponible maintenant',
        noDescription: 'Aucune description disponible.',
        // Pagination
        prevPage: '← Préc.',
        nextPage: 'Suiv. →',
        pageOf: 'Page',
        of: 'sur',
    }
};

let currentLang = 'en';

function setLang(lang) {
    const safe = lang === 'fr' ? 'fr' : 'en';
    currentLang = safe;
    applyTranslations();
    applyPagination();
}

function applyTranslations() {
    const T = translations[currentLang] || translations['en'];

    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (T[key] !== undefined) el.textContent = T[key];
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (T[key] !== undefined) el.setAttribute('placeholder', T[key]);
    });

    document.querySelectorAll('[data-i18n-title]').forEach(el => {
        const key = el.getAttribute('data-i18n-title');
        if (T[key] !== undefined) el.setAttribute('title', T[key]);
    });

    const rangeEl = document.querySelector('[data-i18n-template="catalogRange"]');
    if (rangeEl) {
        const min = rangeEl.dataset.min;
        const max = rangeEl.dataset.max;
        rangeEl.textContent = `${T.catalogRangePrefix || 'Catalog:'} ${min} € – ${max} €`;
    }

    syncThemeBtn();
}
// ===== END ADDED FEATURE: TRANSLATION SYSTEM =====


// ═══════════════════════════════════════════════════════════════════
// ===== ADDED FEATURE: LIGHT/DARK MODE TOGGLE =====
// ===== FrontOffice default = LIGHT. Dark only if user chose it. =====
// ═══════════════════════════════════════════════════════════════════
function syncThemeBtn() {
    // Header theme UI is managed by ../layout/front-header.js.
    // Keep this function for page-level dark-mode compatibility only.
    return;
}

function toggleTheme() {
    if (typeof window.toggleDarkMode === 'function') {
        window.toggleDarkMode();
    }
}

function initTheme() {
    try {
        var legacy = localStorage.getItem('cre8_theme_creator_produit');
        if ((legacy === 'dark' || legacy === 'light') && !localStorage.getItem('cre8_theme')) {
            localStorage.setItem('cre8_theme', legacy);
        }
    } catch (e) {}
    if (typeof window.cre8ApplyFrontTheme === 'function') {
        window.cre8ApplyFrontTheme(document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light', false);
    } else {
        var saved = localStorage.getItem('cre8_theme');
        document.body.classList.toggle('dark-mode', saved === 'dark');
    }
    syncThemeBtn();
}
// ===== END ADDED FEATURE: LIGHT/DARK MODE TOGGLE =====


// ═══════════════════════════════════════════════════════════════════
// INIT — boot all features
// ═══════════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    currentLang = typeof window.cre8RegisterTranslations === 'function'
        ? window.cre8RegisterTranslations(translations)
        : (typeof window.cre8FrontReadLang === 'function' ? window.cre8FrontReadLang() : 'en');
    applyTranslations();
    window.addEventListener('cre8:languagechange', function (event) {
        setLang(event.detail && event.detail.lang ? event.detail.lang : currentLang);
    });

});
</script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
<script src="../layout/front-header.js?v=<?php echo urlencode((string) filemtime(__DIR__ . '/../layout/front-header.js')); ?>"></script>
</body>
</html>
