<?php
$cre8FrontFooterBase = '';
$cre8FrontFooterPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8FrontFooterMarker = '/Vue/FrontOffice/';
$cre8FrontFooterPos = strpos($cre8FrontFooterPath, $cre8FrontFooterMarker);

if ($cre8FrontFooterPos !== false) {
    $cre8FrontFooterBase = substr($cre8FrontFooterPath, 0, $cre8FrontFooterPos) . '/Vue/FrontOffice';
} elseif (($cre8FrontFooterControllerPos = strpos($cre8FrontFooterPath, '/Controleur/')) !== false) {
    $cre8FrontFooterBase = substr($cre8FrontFooterPath, 0, $cre8FrontFooterControllerPos) . '/Vue/FrontOffice';
}

$cre8FrontFooterCss = $cre8FrontFooterBase !== ''
    ? $cre8FrontFooterBase . '/layout/front-layout.css'
    : '../layout/front-layout.css';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($cre8FrontFooterCss); ?>">
<footer class="cre8-front-footer" aria-label="FrontOffice footer">
  <span>Copyright &copy; cre8connect 2026</span>
</footer>
