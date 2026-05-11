<?php
require_once __DIR__ . '/../layout/session_bridge.php';
cre8_front_session_user();

$cre8PilotContext = isset($cre8PilotContext) && is_array($cre8PilotContext) ? $cre8PilotContext : [];
$cre8PilotPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8PilotVuePos = strpos($cre8PilotPath, '/Vue/');
$cre8PilotBase = $cre8PilotVuePos !== false ? substr($cre8PilotPath, 0, $cre8PilotVuePos) : '';
$cre8PilotEndpoint = rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/cre8pilot_endpoint.php';
$cre8PilotBubbleAssetUrl = static function (string $file) use ($cre8PilotBase): string {
    $rel = rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/' . $file;
    $path = __DIR__ . DIRECTORY_SEPARATOR . $file;
    $mtime = @filemtime($path);
    $v = $mtime !== false ? (string) $mtime : '0';

    return htmlspecialchars($rel . '?v=' . rawurlencode($v), ENT_QUOTES, 'UTF-8');
};
$cre8PilotBubbleThinkingUrl = $cre8PilotBubbleAssetUrl('cre8pilot-bubble-thinking.png');
$cre8PilotBubbleSwearsUrl = $cre8PilotBubbleAssetUrl('cre8pilot-bubble-swears.png');
$cre8PilotBubbleConfusedUrl = $cre8PilotBubbleAssetUrl('cre8pilot-bubble-confused.png');
$cre8PilotBubbleSuccessUrl = $cre8PilotBubbleAssetUrl('cre8pilot-bubble-success.png');
$cre8PilotDefaultContext = [
    'page' => 'unknown',
    'mode' => '',
    'role' => (string) ($_SESSION['utilisateur']['role'] ?? 'guest'),
    'allowedActions' => ['normal_chat', 'summarize_page'],
    'formTarget' => null,
    'visibleEntityType' => '',
    'visibleEntityId' => null,
];
$cre8PilotContext = array_replace($cre8PilotDefaultContext, $cre8PilotContext);
?>
<script>
window.CRE8PILOT_CONTEXT = Object.assign(
    {},
    window.CRE8PILOT_CONTEXT || {},
    <?php echo json_encode($cre8PilotContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
);
</script>
<?php
$cre8PilotBubblesCss = htmlspecialchars(rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/cre8pilot-bubbles.css?v=' . urlencode((string) (@filemtime(__DIR__ . '/cre8pilot-bubbles.css') ?: 0)), ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?php echo $cre8PilotBubblesCss; ?>">
<style>
.cre8pilot-field-highlight {
    outline: 3px solid rgba(124, 92, 255, 0.85) !important;
    box-shadow: 0 0 0 6px rgba(124, 92, 255, 0.18) !important;
    transition: box-shadow 0.3s ease, outline 0.3s ease;
}
.cre8pilot-section-highlight {
    outline: 2px dashed rgba(124, 92, 255, 0.55) !important;
    outline-offset: 4px;
    transition: outline 0.3s ease;
}
.cre8pilot-pagescan-banner {
    margin: 0.55rem 0;
    padding: 0.6rem 0.85rem;
    border-radius: 10px;
    background: rgba(217, 119, 6, 0.08);
    color: #92400e;
    border: 1px solid rgba(217, 119, 6, 0.35);
    font-size: 0.86rem;
    line-height: 1.35;
}
.cre8pilot-pagescan-banner.is-high {
    background: rgba(220, 38, 38, 0.08);
    color: #b91c1c;
    border-color: rgba(220, 38, 38, 0.35);
}
.cre8pilot-pagescan-banner ul {
    margin: 0.4rem 0 0;
    padding-left: 1rem;
    color: inherit;
}
</style>
<script src="<?php echo htmlspecialchars(rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/cre8pilot_multi_smoke_test.js'); ?>"></script>
<?php $cre8PilotStressTestJs = htmlspecialchars(rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/cre8pilot_full_balanced_stress_test.js'); ?>
<?php $cre8PilotAiTourStressTestJs = htmlspecialchars(rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/cre8pilot_ai_tour_stress_test.js'); ?>
<?php $cre8PilotFinalMatrixStressTestJs = htmlspecialchars(rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/cre8pilot_final_matrix_stress_test.js'); ?>
<?php $cre8PilotOmegaValidationTestJs = htmlspecialchars(rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/cre8pilot_omega_validation_test.js'); ?>
<script>
(function () {
    var STRESS_URL = '<?php echo $cre8PilotStressTestJs; ?>';
    var AI_TOUR_STRESS_URL = '<?php echo $cre8PilotAiTourStressTestJs; ?>';
    var FINAL_MATRIX_STRESS_URL = '<?php echo $cre8PilotFinalMatrixStressTestJs; ?>';
    var OMEGA_VALIDATION_URL = '<?php echo $cre8PilotOmegaValidationTestJs; ?>';
    var aiTourCore = null;
    var aiTourLoadPromise = null;
    var finalMatrixCore = null;
    var finalMatrixLoadPromise = null;
    var omegaCore = null;
    var omegaLoadPromise = null;
    function ensureAiTourScriptLoaded() {
        if (aiTourCore) {
            return Promise.resolve(aiTourCore);
        }
        if (aiTourLoadPromise) {
            return aiTourLoadPromise;
        }
        var urlAi = AI_TOUR_STRESS_URL;
        var wAi = document.querySelector('[data-cre8pilot-widget]');
        var epAi = wAi && wAi.dataset && wAi.dataset.cre8pilotEndpoint;
        if (epAi && /cre8pilot_endpoint\.php/i.test(epAi)) {
            urlAi = epAi.replace(/cre8pilot_endpoint\.php/i, 'cre8pilot_ai_tour_stress_test.js');
        }
        aiTourLoadPromise = fetch(urlAi, { credentials: 'same-origin' }).then(function (res) {
            if (!res.ok) {
                throw new Error('AI tour stress test HTTP ' + res.status);
            }
            return res.text();
        }).then(function (code) {
            (0, eval)(code);
            if (typeof window.runCre8PilotAiTourStressTest !== 'function') {
                throw new Error('AI tour stress suite did not register runCre8PilotAiTourStressTest.');
            }
            aiTourCore = window.runCre8PilotAiTourStressTest;
            aiTourLoadPromise = null;
            return aiTourCore;
        }).catch(function (err) {
            aiTourLoadPromise = null;
            throw err;
        });
        return aiTourLoadPromise;
    }
    function ensureFinalMatrixScriptLoaded() {
        if (finalMatrixCore) {
            return Promise.resolve(finalMatrixCore);
        }
        if (finalMatrixLoadPromise) {
            return finalMatrixLoadPromise;
        }
        var urlMx = FINAL_MATRIX_STRESS_URL;
        var wMx = document.querySelector('[data-cre8pilot-widget]');
        var epMx = wMx && wMx.dataset && wMx.dataset.cre8pilotEndpoint;
        if (epMx && /cre8pilot_endpoint\.php/i.test(epMx)) {
            urlMx = epMx.replace(/cre8pilot_endpoint\.php/i, 'cre8pilot_final_matrix_stress_test.js');
        }
        if (typeof console !== 'undefined' && console.log) {
            console.log('[Cre8Pilot] loading final matrix stress test:', urlMx);
        }
        finalMatrixLoadPromise = fetch(urlMx, { credentials: 'same-origin' }).then(function (res) {
            if (!res.ok) {
                throw new Error('Final matrix stress test HTTP ' + res.status);
            }
            return res.text();
        }).then(function (code) {
            (0, eval)(code);
            if (typeof window.runCre8PilotFinalMatrixStressTestImpl !== 'function') {
                throw new Error('Final matrix suite did not register runCre8PilotFinalMatrixStressTestImpl.');
            }
            finalMatrixCore = window.runCre8PilotFinalMatrixStressTestImpl;
            finalMatrixLoadPromise = null;
            if (typeof console !== 'undefined' && console.log) {
                console.log('[Cre8Pilot] final matrix stress test script evaluated OK');
            }
            return finalMatrixCore;
        }).catch(function (err) {
            finalMatrixLoadPromise = null;
            throw err;
        });
        return finalMatrixLoadPromise;
    }
    function ensureOmegaValidationScriptLoaded() {
        if (omegaCore) {
            return Promise.resolve(omegaCore);
        }
        if (omegaLoadPromise) {
            return omegaLoadPromise;
        }
        var urlOmega = OMEGA_VALIDATION_URL;
        var wOmega = document.querySelector('[data-cre8pilot-widget]');
        var epOmega = wOmega && wOmega.dataset && wOmega.dataset.cre8pilotEndpoint;
        if (epOmega && /cre8pilot_endpoint\.php/i.test(epOmega)) {
            urlOmega = epOmega.replace(/cre8pilot_endpoint\.php/i, 'cre8pilot_omega_validation_test.js');
        }
        if (typeof console !== 'undefined' && console.log) {
            console.log('[Cre8Pilot] loading omega validation test:', urlOmega);
        }
        omegaLoadPromise = fetch(urlOmega, { credentials: 'same-origin' }).then(function (res) {
            if (!res.ok) {
                throw new Error('Omega validation test HTTP ' + res.status);
            }
            return res.text();
        }).then(function (code) {
            (0, eval)(code);
            if (typeof window.runCre8PilotOmegaValidationTestImpl !== 'function') {
                throw new Error('Omega validation suite did not register runCre8PilotOmegaValidationTestImpl.');
            }
            omegaCore = window.runCre8PilotOmegaValidationTestImpl;
            omegaLoadPromise = null;
            return omegaCore;
        }).catch(function (err) {
            omegaLoadPromise = null;
            throw err;
        });
        return omegaLoadPromise;
    }
    window.runCre8PilotAiTourStressTest = function runCre8PilotAiTourStressTest(options) {
        return ensureAiTourScriptLoaded().then(function (core) {
            return core(options);
        });
    };
    window.runCre8PilotFinalMatrixStressTest = function runCre8PilotFinalMatrixStressTest(options) {
        if (typeof console !== 'undefined' && console.log) {
            console.log('[Cre8Pilot] runCre8PilotFinalMatrixStressTest invoked (will fetch script once, then run)');
        }
        return ensureFinalMatrixScriptLoaded().then(function (core) {
            return core(options);
        }).catch(function (err) {
            console.error('[Cre8Pilot] runCre8PilotFinalMatrixStressTest failed:', err);
            throw err;
        });
    };
    window.runCre8PilotFinalMatrixAutoSuite = function runCre8PilotFinalMatrixAutoSuite(options) {
        if (typeof console !== 'undefined' && console.log) {
            console.log('[Cre8Pilot] runCre8PilotFinalMatrixAutoSuite invoked (will fetch script once, then run)');
        }
        return ensureFinalMatrixScriptLoaded().then(function () {
            if (typeof window.runCre8PilotFinalMatrixAutoSuiteImpl !== 'function') {
                throw new Error('Final matrix suite did not register runCre8PilotFinalMatrixAutoSuiteImpl.');
            }
            return window.runCre8PilotFinalMatrixAutoSuiteImpl(options);
        }).catch(function (err) {
            console.error('[Cre8Pilot] runCre8PilotFinalMatrixAutoSuite failed:', err);
            throw err;
        });
    };
    window.runCre8PilotOmegaValidationTest = function runCre8PilotOmegaValidationTest(options) {
        if (typeof console !== 'undefined' && console.log) {
            console.log('[Cre8Pilot] runCre8PilotOmegaValidationTest invoked (will fetch script once, then run)');
        }
        return ensureOmegaValidationScriptLoaded().then(function (core) {
            return core(options);
        }).catch(function (err) {
            console.error('[Cre8Pilot] runCre8PilotOmegaValidationTest failed:', err);
            throw err;
        });
    };
    window.startStressTest = function startStressTest(options) {
        var defs = { delayMs: 15000, maxAiHeavyTests: 12, stopOnCriticalFailure: false };
        var opts = Object.assign({}, defs, options || {});
        if (typeof window.runCre8PilotFullBalancedStressTest === 'function') {
            return window.runCre8PilotFullBalancedStressTest(opts);
        }
        var w = document.querySelector('[data-cre8pilot-widget]');
        var ep = w && w.dataset && w.dataset.cre8pilotEndpoint;
        var url = STRESS_URL;
        if (ep && /cre8pilot_endpoint\.php/i.test(ep)) {
            url = ep.replace(/cre8pilot_endpoint\.php/i, 'cre8pilot_full_balanced_stress_test.js');
        }
        return fetch(url, { credentials: 'same-origin' }).then(function (res) {
            if (!res.ok) {
                throw new Error('Stress test HTTP ' + res.status);
            }
            return res.text();
        }).then(function (code) {
            (0, eval)(code);
            if (typeof window.runCre8PilotFullBalancedStressTest !== 'function') {
                throw new Error('Stress suite did not register runCre8PilotFullBalancedStressTest.');
            }
            return window.runCre8PilotFullBalancedStressTest(opts);
        });
    };
    window.stress_test_just_ai = function stress_test_just_ai(options) {
        var defs = { delayMs: 15000, maxPages: 10, maxPromptsPerPage: 3, exportJson: true };
        var opts = Object.assign({}, defs, options || {});
        return ensureAiTourScriptLoaded().then(function (core) {
            return core(opts);
        });
    };
})();
</script>
<div class="cre8pilot-widget" data-cre8pilot-widget data-cre8pilot-endpoint="<?php echo htmlspecialchars($cre8PilotEndpoint); ?>">
    <button type="button" class="cre8pilot-fab" data-cre8pilot-toggle aria-label="Open Cre8Pilot">
        <span aria-hidden="true">&#10024;</span>
        <strong>Cre8Pilot</strong>
    </button>

    <section class="cre8pilot-panel" data-cre8pilot-panel hidden aria-label="Cre8Pilot chat panel">
        <header class="cre8pilot-header">
            <div class="cre8pilot-header-main">
                <div class="cre8pilot-header-text">
                    <h2 class="cre8pilot-header-title">Cre8Pilot</h2>
                    <p class="cre8pilot-header-sub">Summaries, drafts, and page-aware help.</p>
                </div>
                <div class="cre8pilot-header-avatar-cluster">
                    <div class="cre8pilot-avatar-bubbles" aria-hidden="true">
                        <div class="cre8pilot-bubble cre8pilot-bubble--thinking" data-cre8pilot-bubble="thinking" hidden>
                            <img src="<?php echo $cre8PilotBubbleThinkingUrl; ?>" width="96" height="72" alt="" loading="lazy" decoding="async">
                        </div>
                        <div class="cre8pilot-bubble cre8pilot-bubble--swears" data-cre8pilot-bubble="swears" hidden>
                            <img src="<?php echo $cre8PilotBubbleSwearsUrl; ?>" width="96" height="72" alt="" loading="lazy" decoding="async">
                        </div>
                        <div class="cre8pilot-bubble cre8pilot-bubble--confused" data-cre8pilot-bubble="confused" hidden>
                            <img src="<?php echo $cre8PilotBubbleConfusedUrl; ?>" width="96" height="72" alt="" loading="lazy" decoding="async">
                        </div>
                        <div class="cre8pilot-bubble cre8pilot-bubble--success" data-cre8pilot-bubble="success" hidden>
                            <img src="<?php echo $cre8PilotBubbleSuccessUrl; ?>" width="96" height="72" alt="" loading="lazy" decoding="async">
                        </div>
                    </div>
                    <div class="cre8pilot-avatar cre8pilot-avatar--small cre8pilot-avatar--idle" data-cre8pilot-avatar data-cre8pilot-avatar-panel aria-hidden="true" title="Cre8Pilot is idle" style="--avatar-voice-level: 0; --voice-level: 0;">
                    <div class="cre8pilot-bot">
                        <div class="cre8pilot-bot-antenna" aria-hidden="true"></div>
                        <div class="cre8pilot-bot-head">
                            <div class="cre8pilot-bot-visor">
                                <span class="cre8pilot-bot-eye cre8pilot-bot-eye--left" aria-hidden="true"></span>
                                <span class="cre8pilot-bot-eye cre8pilot-bot-eye--right" aria-hidden="true"></span>
                            </div>
                        </div>
                        <div class="cre8pilot-bot-arm cre8pilot-bot-arm--left" aria-hidden="true">
                            <span class="cre8pilot-bot-shoulder"></span>
                            <span class="cre8pilot-bot-upper-arm"></span>
                            <span class="cre8pilot-bot-elbow"></span>
                            <span class="cre8pilot-bot-forearm"></span>
                            <span class="cre8pilot-bot-hand"></span>
                        </div>
                        <div class="cre8pilot-bot-arm cre8pilot-bot-arm--right" aria-hidden="true">
                            <span class="cre8pilot-bot-shoulder"></span>
                            <span class="cre8pilot-bot-upper-arm"></span>
                            <span class="cre8pilot-bot-elbow"></span>
                            <span class="cre8pilot-bot-forearm"></span>
                            <span class="cre8pilot-bot-hand"></span>
                        </div>
                        <div class="cre8pilot-bot-body" aria-hidden="true">
                            <div class="cre8pilot-bot-core"></div>
                            <div class="cre8pilot-bot-thruster cre8pilot-bot-thruster--left"></div>
                            <div class="cre8pilot-bot-thruster cre8pilot-bot-thruster--right"></div>
                        </div>
                        <div class="cre8pilot-bot-ring" aria-hidden="true"></div>
                    </div>
                    </div>
                </div>
            </div>
            <button type="button" class="cre8pilot-close" data-cre8pilot-close aria-label="Close Cre8Pilot"><span aria-hidden="true">&times;</span></button>
        </header>

        <div class="cre8pilot-messages" data-cre8pilot-messages>
            <article class="cre8pilot-message cre8pilot-message-assistant">
                <p>Hi, I'm Cre8Pilot. I can help you summarize, analyze, recommend, or prepare forms depending on this page.</p>
            </article>
        </div>

        <div class="cre8pilot-quick-actions" data-cre8pilot-quick-actions aria-label="Cre8Pilot quick actions"></div>

        <div class="cre8pilot-attach-panel" data-cre8pilot-attach-panel hidden>
            <div class="cre8pilot-attach-panel-inner">
                <input type="file" accept=".pdf,.txt,application/pdf,text/plain" data-cre8pilot-file hidden>
                <span class="cre8pilot-attach-filename" data-cre8pilot-attach-filename>No file selected</span>
                <button type="button" class="cre8pilot-attach-pick" data-cre8pilot-attach-pick>Choose file</button>
                <input type="text" class="cre8pilot-attach-label" data-cre8pilot-doc-label maxlength="200" placeholder="Label (optional)" aria-label="Document label">
                <button type="button" class="cre8pilot-attach-upload" data-cre8pilot-attach-upload disabled>Upload</button>
                <button type="button" class="cre8pilot-attach-cancel" data-cre8pilot-attach-cancel>Cancel</button>
            </div>
        </div>
        <div class="cre8pilot-attach-status" data-cre8pilot-attach-status aria-live="polite"></div>

        <form class="cre8pilot-composer" data-cre8pilot-form>
            <label class="cre8pilot-sr-only" for="cre8pilotPrompt">Message Cre8Pilot</label>
            <div class="cre8pilot-composer-input-row">
                <div class="cre8pilot-tools" data-cre8pilot-tools>
                    <button type="button" class="cre8pilot-tools-trigger" data-cre8pilot-tools-toggle aria-label="Open Cre8Pilot tools" title="Open Cre8Pilot tools" aria-expanded="false" aria-haspopup="true">+</button>
                    <div class="cre8pilot-tools-menu" data-cre8pilot-tools-menu hidden role="menu" aria-label="Cre8Pilot tools">
                        <button type="button" class="cre8pilot-tools-menu-item" role="menuitem" data-cre8pilot-tool="attach">Attach file</button>
                        <button type="button" class="cre8pilot-tools-menu-item" role="menuitem" data-cre8pilot-tool="voice">Voice Mode</button>
                        <button type="button" class="cre8pilot-tools-menu-item" role="menuitem" data-cre8pilot-tool="security">Security Check</button>
                        <button type="button" class="cre8pilot-tools-menu-item" role="menuitem" data-cre8pilot-tool="summarize">Summarize page</button>
                        <button type="button" class="cre8pilot-tools-menu-item" role="menuitem" data-cre8pilot-tool="clear">Clear conversation</button>
                        <div class="cre8pilot-tools-menu-divider" data-cre8pilot-tools-overflow-wrap hidden>
                            <span class="cre8pilot-tools-menu-heading">More for this page</span>
                            <div class="cre8pilot-tools-overflow" data-cre8pilot-tools-overflow></div>
                        </div>
                    </div>
                </div>
                <textarea id="cre8pilotPrompt" data-cre8pilot-input rows="2" placeholder="Message Cre8Pilot…"></textarea>
                <button type="button" class="cre8pilot-voice-btn" data-cre8pilot-mic title="Speak to Cre8Pilot" aria-label="Speak to Cre8Pilot">&#127897;&#65039;</button>
                <button type="submit" class="cre8pilot-send-btn">Send</button>
            </div>
            <div class="cre8pilot-voice-status" data-cre8pilot-voice-status aria-live="polite" hidden></div>
            <div class="cre8pilot-composer-meta">
                <span class="cre8pilot-activity" data-cre8pilot-activity hidden></span>
                <span class="cre8pilot-mode-badge" data-cre8pilot-state>Mock mode</span>
            </div>
        </form>
    </section>

    <section class="cre8pilot-voice-overlay" data-cre8pilot-voice-overlay data-voice-state="idle" hidden aria-label="Cre8Pilot voice mode">
        <button type="button" class="cre8pilot-voice-close" data-cre8pilot-voice-close aria-label="Exit voice mode">Exit</button>
        <div class="cre8pilot-voice-stage">
            <div class="cre8pilot-voice-avatar-wrap">
                <div class="cre8pilot-header-avatar-cluster">
                    <div class="cre8pilot-avatar-bubbles" aria-hidden="true">
                        <div class="cre8pilot-bubble cre8pilot-bubble--thinking" data-cre8pilot-bubble="thinking" hidden>
                            <img src="<?php echo $cre8PilotBubbleThinkingUrl; ?>" width="140" height="105" alt="" loading="lazy" decoding="async">
                        </div>
                        <div class="cre8pilot-bubble cre8pilot-bubble--swears" data-cre8pilot-bubble="swears" hidden>
                            <img src="<?php echo $cre8PilotBubbleSwearsUrl; ?>" width="140" height="105" alt="" loading="lazy" decoding="async">
                        </div>
                        <div class="cre8pilot-bubble cre8pilot-bubble--confused" data-cre8pilot-bubble="confused" hidden>
                            <img src="<?php echo $cre8PilotBubbleConfusedUrl; ?>" width="140" height="105" alt="" loading="lazy" decoding="async">
                        </div>
                        <div class="cre8pilot-bubble cre8pilot-bubble--success" data-cre8pilot-bubble="success" hidden>
                            <img src="<?php echo $cre8PilotBubbleSuccessUrl; ?>" width="140" height="105" alt="" loading="lazy" decoding="async">
                        </div>
                    </div>
                    <div class="cre8pilot-avatar cre8pilot-avatar--large cre8pilot-avatar--idle" data-cre8pilot-avatar data-cre8pilot-avatar-voice aria-hidden="true" title="Cre8Pilot is idle" style="--avatar-voice-level: 0; --voice-level: 0;">
                    <div class="cre8pilot-bot">
                        <div class="cre8pilot-bot-antenna" aria-hidden="true"></div>
                        <div class="cre8pilot-bot-head">
                            <div class="cre8pilot-bot-visor">
                                <span class="cre8pilot-bot-eye cre8pilot-bot-eye--left" aria-hidden="true"></span>
                                <span class="cre8pilot-bot-eye cre8pilot-bot-eye--right" aria-hidden="true"></span>
                            </div>
                        </div>
                        <div class="cre8pilot-bot-arm cre8pilot-bot-arm--left" aria-hidden="true">
                            <span class="cre8pilot-bot-shoulder"></span>
                            <span class="cre8pilot-bot-upper-arm"></span>
                            <span class="cre8pilot-bot-elbow"></span>
                            <span class="cre8pilot-bot-forearm"></span>
                            <span class="cre8pilot-bot-hand"></span>
                        </div>
                        <div class="cre8pilot-bot-arm cre8pilot-bot-arm--right" aria-hidden="true">
                            <span class="cre8pilot-bot-shoulder"></span>
                            <span class="cre8pilot-bot-upper-arm"></span>
                            <span class="cre8pilot-bot-elbow"></span>
                            <span class="cre8pilot-bot-forearm"></span>
                            <span class="cre8pilot-bot-hand"></span>
                        </div>
                        <div class="cre8pilot-bot-body" aria-hidden="true">
                            <div class="cre8pilot-bot-core"></div>
                            <div class="cre8pilot-bot-thruster cre8pilot-bot-thruster--left"></div>
                            <div class="cre8pilot-bot-thruster cre8pilot-bot-thruster--right"></div>
                        </div>
                        <div class="cre8pilot-bot-ring" aria-hidden="true"></div>
                    </div>
                    </div>
                </div>
            </div>
            <div class="cre8pilot-voice-orb-wrap">
                <div class="cre8pilot-voice-circle cre8pilot-voice-circle--idle" data-cre8pilot-voice-circle style="--voice-level: 0;"></div>
            </div>
            <div class="cre8pilot-voice-subtitle" data-cre8pilot-voice-subtitle>Tap the mic and speak.</div>
            <div class="cre8pilot-voice-captured" data-cre8pilot-voice-captured hidden></div>
            <div class="cre8pilot-voice-mode-controls">
                <button type="button" data-cre8pilot-voice-toggle>Start voice</button>
                <button type="button" data-cre8pilot-voice-send disabled>Send captured text</button>
                <button type="button" data-cre8pilot-voice-clear disabled>Clear captured text</button>
                <button type="button" data-cre8pilot-voice-stop-speaking>Stop AI speaking</button>
                <button type="button" data-cre8pilot-voice-exit>Exit voice mode</button>
            </div>
        </div>
    </section>
</div>
<script>
(() => {
    if (window.Cre8PilotV1Ready) {
        return;
    }
    window.Cre8PilotV1Ready = true;
    let cre8PilotCurrentSpeech = {
        button: null,
        message: null,
    };

    const CRE8PILOT_AVATAR_STATES = new Set([
        'idle', 'thinking', 'filling', 'success', 'warning', 'confused', 'listening', 'user_speaking', 'ai_speaking', 'speaking', 'error',
    ]);

    function cre8PilotAvatarStateToModifierClass(stateName) {
        return 'cre8pilot-avatar--' + String(stateName || 'idle').replace(/_/g, '-');
    }

    function cre8PilotAvatarClearStateClasses(el) {
        CRE8PILOT_AVATAR_STATES.forEach((s) => {
            el.classList.remove('cre8pilot-avatar--' + s);
            el.classList.remove(cre8PilotAvatarStateToModifierClass(s));
        });
    }

    function cre8PilotResolveWidget(target) {
        if (target && target.closest) {
            const w = target.closest('[data-cre8pilot-widget]');
            if (w) {
                return w;
            }
        }
        if (target && target.dataset && target.dataset.cre8pilotWidget !== undefined) {
            return target;
        }
        return document.querySelector('[data-cre8pilot-widget]');
    }

    function cre8PilotAvatarTitle(state) {
        const titles = {
            idle: 'Cre8Pilot is idle',
            thinking: 'Cre8Pilot is thinking',
            filling: 'Cre8Pilot is preparing fields',
            success: 'Cre8Pilot is done',
            warning: 'Cre8Pilot detected a warning',
            confused: 'Cre8Pilot needs clarification',
            listening: 'Cre8Pilot is listening',
            user_speaking: 'Listening to you',
            ai_speaking: 'Cre8Pilot is speaking',
            speaking: 'Cre8Pilot is speaking',
            error: 'Something went wrong',
        };
        return titles[state] || 'Cre8Pilot';
    }

    const CRE8PILOT_BUBBLE_HIDE_MS = 3000;

    /**
     * Many “transparent” bubble PNGs bake in the gray/white checkerboard as opaque pixels.
     * Flood from the image border through neutral / light pixels (low chroma). Stops at ink and saturated colors.
     * Includes medium gray (#808080) which the first version missed, and re-runs when bubbles are shown (lazy load).
     */
    function cre8PilotIsNeutralCheckerPixel(r, g, b, a, mode) {
        if (a < 18) {
            return false;
        }
        const mx = Math.max(r, g, b);
        const mn = Math.min(r, g, b);
        const chroma = mx - mn;
        const loose = mode === 'loose';
        const maxChroma = loose ? 58 : 44;
        const minMx = loose ? 56 : 72;
        if (chroma > maxChroma || mx < minMx) {
            return false;
        }
        if (mx > 248 && mn > 232) {
            return true;
        }
        if (loose) {
            if (mx >= 82 && mx <= 235 && mn >= 58) {
                return true;
            }
            if (mx > 235 && mn > 145 && chroma <= 50) {
                return true;
            }
            return false;
        }
        if (mx >= 95 && mx <= 215 && mn >= 70 && chroma <= 42) {
            return true;
        }
        if (mx >= 150 && mn >= 115 && chroma <= 36) {
            return true;
        }
        return false;
    }

    function cre8PilotFloodClearCheckerboard(d, w, h, isBg, maxFrac) {
        const backup = new Uint8ClampedArray(d);
        const visited = new Uint8Array(w * h);
        const qx = [];
        const qy = [];
        const idx = (x, y) => y * w + x;
        const trySeed = (x, y) => {
            if (x < 0 || y < 0 || x >= w || y >= h) {
                return;
            }
            const i = idx(x, y);
            if (visited[i]) {
                return;
            }
            const o = i * 4;
            if (!isBg(d[o], d[o + 1], d[o + 2], d[o + 3])) {
                return;
            }
            visited[i] = 1;
            qx.push(x);
            qy.push(y);
        };
        trySeed(0, 0);
        trySeed(w - 1, 0);
        trySeed(0, h - 1);
        trySeed(w - 1, h - 1);
        if (qx.length === 0) {
            for (let x = 0; x < w; x += 1) {
                trySeed(x, 0);
                trySeed(x, h - 1);
            }
            for (let y = 0; y < h; y += 1) {
                trySeed(0, y);
                trySeed(w - 1, y);
            }
        }
        if (qx.length === 0) {
            return { ok: false, cleared: 0, backup };
        }
        const neighbors = [[1, 0], [-1, 0], [0, 1], [0, -1]];
        let head = 0;
        let cleared = 0;
        const maxClear = Math.max(80, Math.floor(w * h * maxFrac));
        while (head < qx.length) {
            const x = qx[head];
            const y = qy[head];
            head += 1;
            const ii = idx(x, y);
            const o = ii * 4;
            d[o + 3] = 0;
            cleared += 1;
            if (cleared > maxClear) {
                d.set(backup);
                return { ok: false, cleared: 0, backup };
            }
            for (let ni = 0; ni < 4; ni += 1) {
                const nx = x + neighbors[ni][0];
                const ny = y + neighbors[ni][1];
                if (nx < 0 || ny < 0 || nx >= w || ny >= h) {
                    continue;
                }
                const ii2 = idx(nx, ny);
                if (visited[ii2]) {
                    continue;
                }
                const o2 = ii2 * 4;
                if (!isBg(d[o2], d[o2 + 1], d[o2 + 2], d[o2 + 3])) {
                    continue;
                }
                visited[ii2] = 1;
                qx.push(nx);
                qy.push(ny);
            }
        }
        if (cleared < 16) {
            d.set(backup);
            return { ok: false, cleared: 0, backup };
        }
        return { ok: true, cleared, backup };
    }

    function cre8PilotStripCheckerboardFromBubbleImg(img, force) {
        if (!img) {
            return;
        }
        if (img.dataset.cre8pilotBubbleStrip === '1') {
            delete img.dataset.cre8pilotBubbleStrip;
        }
        if (img.src && img.src.indexOf('data:image') === 0) {
            img.dataset.cre8pilotBubbleStrip = 'done';
            return;
        }
        if (img.dataset.cre8pilotBubbleStrip === 'done') {
            return;
        }
        if (!force && img.dataset.cre8pilotBubbleStrip === 'skip') {
            return;
        }
        if (force && img.dataset.cre8pilotBubbleStrip === 'skip') {
            delete img.dataset.cre8pilotBubbleStrip;
        }
        // Success bubble is shipped with transparency; do not run checkerboard (would eat white) or canvas re-encode.
        if (img.closest && img.closest('.cre8pilot-bubble--success')) {
            img.dataset.cre8pilotBubbleStrip = 'done';
            return;
        }
        const w = img.naturalWidth;
        const h = img.naturalHeight;
        if (!w || !h || w > 2048 || h > 2048) {
            img.dataset.cre8pilotBubbleStrip = 'skip';
            return;
        }
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            img.dataset.cre8pilotBubbleStrip = 'skip';
            return;
        }
        const tryModes = [['normal', 0.58], ['loose', 0.65]];
        let lastOk = false;
        let imgData;
        let d;
        for (let mi = 0; mi < tryModes.length; mi += 1) {
            ctx.drawImage(img, 0, 0);
            try {
                imgData = ctx.getImageData(0, 0, w, h);
            } catch (e) {
                img.dataset.cre8pilotBubbleStrip = 'skip';
                return;
            }
            d = imgData.data;
            const mode = tryModes[mi][0];
            const frac = tryModes[mi][1];
            const isBg = (r, g, b, a) => cre8PilotIsNeutralCheckerPixel(r, g, b, a, mode);
            const res = cre8PilotFloodClearCheckerboard(d, w, h, isBg, frac);
            if (res.ok && res.cleared >= 16) {
                lastOk = true;
                break;
            }
        }
        if (!lastOk) {
            img.dataset.cre8pilotBubbleStrip = 'skip';
            return;
        }
        ctx.putImageData(imgData, 0, 0);
        let dataUrl;
        try {
            dataUrl = canvas.toDataURL('image/png');
        } catch (e2) {
            img.dataset.cre8pilotBubbleStrip = 'skip';
            return;
        }
        if (dataUrl && dataUrl.indexOf('data:') === 0) {
            img.dataset.cre8pilotBubbleStrip = 'done';
            img.src = dataUrl;
        } else {
            img.dataset.cre8pilotBubbleStrip = 'skip';
        }
    }

    function cre8PilotPrepareBubbleImages(widget, force) {
        if (!widget) {
            return;
        }
        widget.querySelectorAll('.cre8pilot-bubble img').forEach((bubbleImg) => {
            if (bubbleImg.dataset.cre8pilotBubbleStrip === 'done') {
                return;
            }
            if (!force && bubbleImg.dataset.cre8pilotBubbleStrip === 'skip') {
                return;
            }
            const run = () => {
                try {
                    cre8PilotStripCheckerboardFromBubbleImg(bubbleImg, !!force);
                } catch (err) {
                    bubbleImg.dataset.cre8pilotBubbleStrip = 'skip';
                }
            };
            if (bubbleImg.complete && bubbleImg.naturalWidth > 0) {
                requestAnimationFrame(run);
            } else {
                bubbleImg.addEventListener('load', () => requestAnimationFrame(run), { once: true });
            }
        });
    }

    function cre8PilotBubbleKindForAvatarState(stateName) {
        const s = String(stateName || '').toLowerCase();
        if (s === 'thinking' || s === 'filling') {
            return 'thinking';
        }
        if (s === 'warning' || s === 'error') {
            return 'confused';
        }
        if (s === 'confused') {
            return 'confused';
        }
        if (s === 'success') {
            return 'success';
        }
        return null;
    }

    function cre8PilotClearBubbleHideTimer(widget) {
        if (widget && widget.cre8PilotBubbleHideTimer) {
            clearTimeout(widget.cre8PilotBubbleHideTimer);
            widget.cre8PilotBubbleHideTimer = null;
        }
    }

    function cre8PilotHideAvatarBubblesInWidget(widget) {
        if (!widget) {
            return;
        }
        widget.querySelectorAll('[data-cre8pilot-bubble]').forEach((b) => {
            b.hidden = true;
            b.classList.remove('is-visible');
        });
    }

    function cre8PilotShowAvatarBubbleKind(widget, kind) {
        if (!widget || !kind) {
            cre8PilotHideAvatarBubblesInWidget(widget);
            return;
        }
        cre8PilotHideAvatarBubblesInWidget(widget);
        widget.querySelectorAll('[data-cre8pilot-bubble="' + kind + '"]').forEach((b) => {
            b.hidden = false;
            requestAnimationFrame(() => b.classList.add('is-visible'));
        });
        widget.cre8PilotActiveBubbleKind = kind;
        requestAnimationFrame(() => requestAnimationFrame(() => cre8PilotPrepareBubbleImages(widget, true)));
    }

    function cre8PilotScheduleBubbleAutoHide(widget) {
        cre8PilotClearBubbleHideTimer(widget);
        if (!widget || !widget.cre8PilotActiveBubbleKind) {
            return;
        }
        widget.cre8PilotBubbleHideTimer = setTimeout(() => {
            widget.cre8PilotBubbleHideTimer = null;
            if (widget.cre8PilotBubbleHoverHold) {
                return;
            }
            cre8PilotHideAvatarBubblesInWidget(widget);
            widget.cre8PilotActiveBubbleKind = null;
        }, CRE8PILOT_BUBBLE_HIDE_MS);
    }

    function cre8PilotSyncBubblesToAvatarState(widget, stateName) {
        if (!widget) {
            return;
        }
        const kind = cre8PilotBubbleKindForAvatarState(stateName);
        if (!kind) {
            cre8PilotClearBubbleHideTimer(widget);
            cre8PilotHideAvatarBubblesInWidget(widget);
            widget.cre8PilotActiveBubbleKind = null;
            return;
        }
        cre8PilotShowAvatarBubbleKind(widget, kind);
        cre8PilotScheduleBubbleAutoHide(widget);
    }

    function setCre8PilotAvatarState(state, widgetOrNull, options = {}) {
        let stateName = String(state || 'idle').toLowerCase();
        if (stateName === 'speaking') {
            stateName = 'ai_speaking';
        }
        if (!CRE8PILOT_AVATAR_STATES.has(stateName)) {
            stateName = 'idle';
        }
        const widget = widgetOrNull || cre8PilotResolveWidget(null);
        if (!widget) {
            return stateName;
        }
        widget.cre8PilotAvatarState = stateName;
        const els = widget.querySelectorAll('[data-cre8pilot-avatar]');
        const modClass = cre8PilotAvatarStateToModifierClass(stateName);
        els.forEach((el) => {
            cre8PilotAvatarClearStateClasses(el);
            el.classList.add(modClass);
            el.title = options.title || cre8PilotAvatarTitle(stateName);
            if (typeof options.voiceLevel === 'number') {
                const v = String(Math.min(1, Math.max(0, options.voiceLevel)));
                el.style.setProperty('--avatar-voice-level', v);
                el.style.setProperty('--voice-level', v);
            }
        });
        cre8PilotSyncBubblesToAvatarState(widget, stateName);
        return stateName;
    }

    function getCre8PilotAvatarState(widgetOrNull) {
        const widget = widgetOrNull || cre8PilotResolveWidget(null);
        return widget ? (widget.cre8PilotAvatarState || 'idle') : 'idle';
    }

    function updateCre8PilotAvatarFromResponse(data, widgetOrNull) {
        const widget = widgetOrNull || cre8PilotResolveWidget(null);
        if (!widget || !data) {
            return 'idle';
        }
        let state = String(data.avatarState || '').toLowerCase();
        if (state === 'speaking') {
            state = 'ai_speaking';
        }
        if (!state || !CRE8PILOT_AVATAR_STATES.has(state)) {
            const status = String(data.status || 'ok');
            const intent = String(data.intent || '');
            if (status === 'blocked') {
                state = 'warning';
            } else if (status === 'need_clarification') {
                state = 'confused';
            } else if (status === 'error') {
                state = 'error';
            } else if (intent.indexOf('fill_') !== -1 || intent === 'prepare_negotiation_reply' || intent === 'prepare_acceptance_note' || intent === 'prepare_refusal_note') {
                state = 'filling';
            } else if (status === 'ok') {
                state = 'success';
            } else {
                state = 'idle';
            }
        }
        const sec = data.security;
        if (sec && typeof sec === 'object') {
            const lvl = String(sec.riskLevel || '').toLowerCase();
            if (lvl === 'high' || lvl === 'medium') {
                state = 'warning';
            }
        }
        widget.cre8PilotLastResponseAvatarState = state;
        setCre8PilotAvatarState(state, widget);
        return state;
    }

    function setCre8PilotAvatarThinking(widgetOrNull) {
        return setCre8PilotAvatarState('thinking', widgetOrNull || cre8PilotResolveWidget(null));
    }

    function setCre8PilotAvatarIdle(widgetOrNull) {
        return setCre8PilotAvatarState('idle', widgetOrNull || cre8PilotResolveWidget(null));
    }

    function cre8PilotSyncAvatarsFromVoiceMode(widget, voiceStateName) {
        let avatarState = String(voiceStateName || 'idle');
        if (avatarState === 'speaking') {
            avatarState = 'ai_speaking';
        } else if (avatarState === 'captured') {
            avatarState = 'success';
        }
        if (!CRE8PILOT_AVATAR_STATES.has(avatarState)) {
            avatarState = 'idle';
        }
        const titles = {
            idle: 'Cre8Pilot voice mode',
            listening: 'Cre8Pilot is listening',
            user_speaking: 'Listening to you',
            success: 'Voice captured — review and send',
            thinking: 'Cre8Pilot is thinking',
            ai_speaking: 'Cre8Pilot is speaking',
            error: 'Voice error',
        };
        setCre8PilotAvatarState(avatarState, widget, { title: titles[avatarState] || cre8PilotAvatarTitle(avatarState) });
    }

    window.setCre8PilotAvatarState = setCre8PilotAvatarState;
    window.getCre8PilotAvatarState = getCre8PilotAvatarState;
    window.updateCre8PilotAvatarFromResponse = updateCre8PilotAvatarFromResponse;
    window.setCre8PilotAvatarThinking = setCre8PilotAvatarThinking;
    window.setCre8PilotAvatarIdle = setCre8PilotAvatarIdle;

    function appendMessage(messages, text, type = 'assistant', status = 'ok') {
        const item = document.createElement('article');
        item.className = 'cre8pilot-message cre8pilot-message-' + type;
        if (status && status !== 'ok') {
            item.classList.add('is-' + status);
        }
        const isReadableAssistant = type === 'assistant' && !['action', 'error'].includes(String(status || ''));
        if (isReadableAssistant) {
            item.dataset.cre8pilotReadable = '1';
        }
        const paragraph = document.createElement('p');
        paragraph.textContent = text;
        item.appendChild(paragraph);
        if (isReadableAssistant) {
            const speakButton = document.createElement('button');
            speakButton.type = 'button';
            speakButton.className = 'cre8pilot-message-speak';
            speakButton.title = 'Read this response aloud';
            speakButton.setAttribute('aria-label', 'Read this response aloud');
            speakButton.textContent = '\uD83D\uDD0A';
            speakButton.addEventListener('click', () => speakCre8PilotMessage(item, speakButton, paragraph.textContent || ''));
            item.appendChild(speakButton);
        }
        messages.appendChild(item);
        messages.scrollTop = messages.scrollHeight;
        return item;
    }

    function appendClarificationOptions(messages, options, sendPrompt) {
        if (!Array.isArray(options) || options.length === 0) {
            return;
        }

        const item = document.createElement('article');
        item.className = 'cre8pilot-message cre8pilot-message-assistant is-clarification';
        const label = document.createElement('p');
        label.textContent = 'Choose one option:';
        item.appendChild(label);

        const list = document.createElement('div');
        list.className = 'cre8pilot-option-list';
        options.forEach((option) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = option.label || option.id || 'Option';
            button.dataset.cre8pilotClarificationId = option.id || '';
            button.addEventListener('click', () => {
                const optionLabel = option.label || option.id || '';
                if (optionLabel !== '') {
                    sendPrompt(optionLabel, option.id || '');
                }
            });
            list.appendChild(button);
        });

        item.appendChild(list);
        messages.appendChild(item);
        messages.scrollTop = messages.scrollHeight;
    }

    function appendCre8PilotMatchModelCard(messages, matchModel) {
        if (!messages || !matchModel || typeof matchModel !== 'object') {
            return;
        }
        const recs = Array.isArray(matchModel.topRecommendations) ? matchModel.topRecommendations : [];
        if (recs.length === 0) {
            return;
        }

        const picker = document.querySelector('[data-creator-picker]');
        const pickerHasApi = !!(picker && typeof picker.cre8pilotApplyCreatorById === 'function');

        const wrap = document.createElement('article');
        wrap.className = 'cre8pilot-message cre8pilot-message-assistant cre8pilot-match-card';
        wrap.setAttribute('aria-label', 'Creator match recommendations');

        const head = document.createElement('div');
        head.className = 'cre8pilot-match-card-head';
        const title = document.createElement('strong');
        title.className = 'cre8pilot-match-card-title';
        title.textContent = 'Match recommendations';
        head.appendChild(title);
        wrap.appendChild(head);

        function refreshSelectButtons(selectedIdStr) {
            wrap.querySelectorAll('[data-cre8pilot-match-select]').forEach((btn) => {
                const matched = btn.dataset.matchCreatorId === selectedIdStr;
                btn.classList.toggle('is-selected', matched);
                btn.textContent = matched ? 'Selected' : 'Select';
                btn.setAttribute('aria-pressed', matched ? 'true' : 'false');
                btn.disabled = false;
            });
            wrap.querySelectorAll('[data-cre8pilot-match-row]').forEach((rowEl) => {
                const matched = rowEl.dataset.matchCreatorId === selectedIdStr;
                rowEl.classList.toggle('is-selected-row', matched);
            });
        }

        recs.forEach((row, index) => {
            if (!row || typeof row !== 'object') {
                return;
            }
            const name = String(row.creatorName || 'Creator').trim() || 'Creator';
            const score = typeof row.matchScore === 'number' ? row.matchScore : parseInt(row.matchScore, 10) || 0;
            const labelRaw = String(row.label || 'weak').toLowerCase();
            const labelNice = labelRaw === 'strong' ? 'Strong' : labelRaw === 'medium' ? 'Medium' : 'Weak';
            const reasons = Array.isArray(row.reasons) ? row.reasons.slice(0, 3) : [];
            const creatorIdStr = String(row.creatorId == null ? '' : row.creatorId).trim();
            const hasValidId = creatorIdStr !== '' && creatorIdStr !== '0';

            const card = document.createElement('div');
            card.className = 'cre8pilot-match-row';
            card.setAttribute('data-cre8pilot-match-row', '1');
            if (hasValidId) {
                card.dataset.matchCreatorId = creatorIdStr;
            }

            const topRow = document.createElement('div');
            topRow.className = 'cre8pilot-match-row-top';
            const nm = document.createElement('span');
            nm.className = 'cre8pilot-match-name';
            nm.textContent = name;
            const badge = document.createElement('span');
            badge.className = 'cre8pilot-match-score-badge';
            badge.textContent = String(score);
            const lb = document.createElement('span');
            lb.className = 'cre8pilot-match-label cre8pilot-match-label--' + (['strong', 'medium', 'weak'].includes(labelRaw) ? labelRaw : 'weak');
            lb.textContent = labelNice;
            topRow.appendChild(nm);
            topRow.appendChild(badge);
            topRow.appendChild(lb);

            if (pickerHasApi && hasValidId) {
                const selectBtn = document.createElement('button');
                selectBtn.type = 'button';
                selectBtn.className = 'cre8pilot-match-select-btn';
                selectBtn.textContent = 'Select';
                selectBtn.dataset.matchCreatorId = creatorIdStr;
                selectBtn.setAttribute('aria-pressed', 'false');
                selectBtn.setAttribute('data-cre8pilot-match-select', '1');
                selectBtn.title = 'Use this creator in the offer form.';
                selectBtn.addEventListener('click', () => {
                    if (selectBtn.disabled) {
                        return;
                    }
                    selectBtn.disabled = true;
                    selectBtn.textContent = 'Selecting…';
                    Promise.resolve(picker.cre8pilotApplyCreatorById(creatorIdStr, { scrollIntoView: true }))
                        .then((ok) => {
                            if (ok) {
                                refreshSelectButtons(creatorIdStr);
                            } else {
                                selectBtn.disabled = false;
                                selectBtn.textContent = 'Select';
                                appendMessage(messages, 'I could not load that creator from the picker right now. Please pick another one.', 'assistant', 'blocked');
                            }
                        });
                });
                topRow.appendChild(selectBtn);
            }

            card.appendChild(topRow);

            if (reasons.length > 0) {
                const ul = document.createElement('ul');
                ul.className = 'cre8pilot-match-reasons';
                reasons.forEach((r) => {
                    const li = document.createElement('li');
                    li.textContent = String(r);
                    ul.appendChild(li);
                });
                card.appendChild(ul);
            }

            if (index === 0 && pickerHasApi && hasValidId) {
                const autoBadge = document.createElement('span');
                autoBadge.className = 'cre8pilot-match-auto-badge';
                autoBadge.textContent = 'Auto-selected in the form';
                card.appendChild(autoBadge);
            }

            wrap.appendChild(card);
        });

        messages.appendChild(wrap);

        if (pickerHasApi) {
            const topRec = recs.find((r) => {
                if (!r) {
                    return false;
                }
                const idStr = String(r.creatorId == null ? '' : r.creatorId).trim();
                return idStr !== '' && idStr !== '0';
            });
            if (topRec) {
                const topId = String(topRec.creatorId).trim();
                Promise.resolve(picker.cre8pilotApplyCreatorById(topId, { scrollIntoView: true }))
                    .then((ok) => {
                        if (ok) {
                            refreshSelectButtons(topId);
                        }
                    });
            }
        }
    }

    function appendCre8PilotSecurityCard(messages, security) {
        if (!messages || !security || typeof security !== 'object') {
            return;
        }
        const level = String(security.riskLevel || 'low').toLowerCase();
        const hits = Array.isArray(security.hits) ? security.hits : [];
        const hitScores = hits
            .map((hit) => parseInt(hit && hit.risk_score, 10))
            .filter((value) => Number.isFinite(value));
        const scoreFromHits = hitScores.length > 0 ? Math.max.apply(null, hitScores) : 0;
        const rawScore = typeof security.riskScore === 'number'
            ? security.riskScore
            : (parseInt(security.riskScore, 10) || 0);
        const score = Math.max(rawScore, scoreFromHits);

        function uniqueLines(lines, limit) {
            const out = [];
            (Array.isArray(lines) ? lines : []).forEach((line) => {
                const clean = String(line || '').trim();
                if (clean && !out.includes(clean)) {
                    out.push(clean);
                }
            });
            return out.slice(0, limit);
        }

        function flattenHitField(fieldNames) {
            const out = [];
            hits.forEach((hit) => {
                if (!hit || typeof hit !== 'object') {
                    return;
                }
                fieldNames.forEach((field) => {
                    const value = hit[field];
                    if (Array.isArray(value)) {
                        value.forEach((line) => out.push(line));
                    }
                });
            });
            return out;
        }

        let categories = uniqueLines(security.riskCategories, 12);
        let findings = uniqueLines(security.findings, 14);
        let recs = uniqueLines(security.safeRecommendations, 8);

        if (categories.length === 0) {
            categories = uniqueLines(flattenHitField(['categories', 'riskCategories']), 12);
        }
        if (findings.length === 0) {
            findings = uniqueLines(flattenHitField(['findings']), 14);
        }
        if (recs.length === 0) {
            recs = uniqueLines(flattenHitField(['safeRecommendations', 'safe_recommendations']), 8);
        }
        if (findings.length === 0 && hits.length > 0) {
            findings = uniqueLines(hits.map((hit) => {
                const label = String((hit && (hit.source_label || hit.item_type)) || 'Visible item').trim();
                const risk = String((hit && hit.risk_level) || level || 'medium').trim();
                const cats = Array.isArray(hit && hit.categories) ? hit.categories.slice(0, 4).join(', ') : '';
                return label + ': ' + risk + ' risk' + (cats ? ' (' + cats + ')' : '') + '.';
            }), 8);
        }
        if (recs.length === 0 && level !== 'low') {
            recs = [
                'Pause before continuing and verify the request through official Cre8Connect tools.',
                'Do not share login codes, passwords, payment proof, or private account data outside the platform.',
                'Keep payments, contracts, and files inside trusted in-platform workflows whenever possible.',
            ];
        }

        const item = document.createElement('article');
        item.className = 'cre8pilot-message cre8pilot-message-assistant cre8pilot-security-card';
        item.setAttribute('aria-label', 'Cre8Shield security summary');

        const header = document.createElement('div');
        header.className = 'cre8pilot-security-header';
        const title = document.createElement('strong');
        title.className = 'cre8pilot-security-title';
        title.textContent = 'Cre8Shield';
        const badge = document.createElement('span');
        badge.className = 'cre8pilot-security-badge cre8pilot-security-badge--' + (['low', 'medium', 'high'].includes(level) ? level : 'low');
        badge.textContent = 'Risk: ' + level.charAt(0).toUpperCase() + level.slice(1);
        const scoreEl = document.createElement('span');
        scoreEl.className = 'cre8pilot-security-score';
        scoreEl.textContent = String(score) + '/100';
        header.appendChild(title);
        header.appendChild(badge);
        header.appendChild(scoreEl);
        if (security.aiReviewed === true) {
            const aiBadge = document.createElement('span');
            aiBadge.className = 'cre8pilot-security-ai-reviewed';
            aiBadge.textContent = 'AI reviewed';
            header.appendChild(aiBadge);
        }
        if (security.nerReviewed === true) {
            const nerBadge = document.createElement('span');
            nerBadge.className = 'cre8pilot-security-ner-extracted';
            nerBadge.textContent = 'NER extracted';
            header.appendChild(nerBadge);
        }
        item.appendChild(header);

        function addSection(heading, lines) {
            if (!lines || lines.length === 0) {
                return;
            }
            const wrap = document.createElement('div');
            wrap.className = 'cre8pilot-security-section';
            const h = document.createElement('div');
            h.className = 'cre8pilot-security-section-title';
            h.textContent = heading;
            wrap.appendChild(h);
            const ul = document.createElement('ul');
            ul.className = 'cre8pilot-security-list';
            lines.forEach((line) => {
                const li = document.createElement('li');
                li.textContent = String(line);
                ul.appendChild(li);
            });
            wrap.appendChild(ul);
            item.appendChild(wrap);
        }

        if (categories.length > 0) {
            addSection('Categories', categories);
        }
        addSection('Findings', findings);
        addSection('Recommendations', recs);
        if (security.aiReviewed === true) {
            const rationale = String(security.aiRationale || '').trim();
            const decision = String(security.aiDecision || '').trim();
            const lines = [];
            if (decision !== '') {
                lines.push('AI decision: ' + decision.replace(/_/g, ' '));
            }
            if (rationale !== '') {
                lines.push(rationale);
            }
            if (lines.length > 0) {
                addSection('AI insight', lines);
            }
        }

        const ce = security.cyberEntities;
        if (ce && typeof ce === 'object') {
            function joinEntities(arr) {
                if (!Array.isArray(arr) || arr.length === 0) {
                    return '';
                }
                return arr.map((x) => String(x)).filter(Boolean).join(', ');
            }
            const indicatorLine = joinEntities(ce.indicators);
            const malwareLine = joinEntities(ce.malware);
            const vulnLine = joinEntities(ce.vulnerabilities);
            const systemLine = joinEntities(ce.systems);
            const orgLine = joinEntities(ce.organizations);
            const cyberLines = [];
            if (indicatorLine) {
                cyberLines.push('Indicators: ' + indicatorLine);
            }
            if (malwareLine) {
                cyberLines.push('Malware: ' + malwareLine);
            }
            if (vulnLine) {
                cyberLines.push('Vulnerabilities: ' + vulnLine);
            }
            if (systemLine) {
                cyberLines.push('Systems: ' + systemLine);
            }
            if (orgLine) {
                cyberLines.push('Organizations: ' + orgLine);
            }
            if (cyberLines.length > 0) {
                addSection('Cyber entities', cyberLines);
            }
        }

        messages.appendChild(item);
        messages.scrollTop = messages.scrollHeight;
    }

    function isProbablyVisible(field) {
        if (!field) {
            return false;
        }
        const rect = field.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0 && window.getComputedStyle(field).visibility !== 'hidden';
    }

    function uniqueFields(fields) {
        return Array.from(new Set(fields.filter(Boolean)));
    }

    function getFieldAliases(name) {
        const aliases = {
            message: ['message', 'messageNegociation', 'contenu', 'messageMotivation'],
            messageNegociation: ['messageNegociation', 'message', 'contenu', 'messageMotivation'],
            contenu: ['contenu', 'message', 'messageNegociation', 'messageMotivation'],
            negotiationMessage: ['messageMotivation', 'messageNegociation', 'message', 'contenu'],
            refusalMessage: ['motifRefus', 'noteDecision', 'messageMotivation'],
            decisionNote: ['noteDecision', 'messageMotivation'],
            acceptNote: ['acceptNote', 'noteDecision', 'accept_message', 'messageAccept'],
            declineNote: ['declineNote', 'motifRefus', 'noteDecision', 'refuseReason', 'declineReason', 'refusal_message', 'messageRefus'],
            budget: ['budgetPropose', 'budget', 'montant'],
            timeline: ['delaiPropose', 'timeline', 'deadline', 'delai'],
        };

        return aliases[name] || [name];
    }

    function cre8pilotQueryById(root, id) {
        if (!id) {
            return null;
        }
        if (root === document && document.getElementById) {
            return document.getElementById(id);
        }
        if (!root || typeof root.querySelector !== 'function') {
            return null;
        }
        try {
            const esc = typeof CSS !== 'undefined' && typeof CSS.escape === 'function' ? CSS.escape(id) : id.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
            return root.querySelector('#' + esc);
        } catch (e) {
            return null;
        }
    }

    function findField(name, scopeRoot) {
        if (!name) {
            return null;
        }
        const root = scopeRoot && typeof scopeRoot.querySelector === 'function' ? scopeRoot : document;

        const dataCandidates = [];
        const nameCandidates = [];
        const idCandidates = [];
        getFieldAliases(name).forEach((fieldName) => {
            const fn = String(fieldName).replace(/"/g, '');
            dataCandidates.push(root.querySelector('[data-cre8pilot-field="' + fn + '"]'));
            if (root === document) {
                document.getElementsByName(fn).forEach((field) => nameCandidates.push(field));
            } else {
                root.querySelectorAll('[name="' + fn.replace(/"/g, '') + '"]').forEach((field) => nameCandidates.push(field));
            }
            idCandidates.push(cre8pilotQueryById(root, fn));
        });

        const unique = uniqueFields(dataCandidates.concat(nameCandidates, idCandidates));
        return unique.find(isProbablyVisible) || unique[0] || null;
    }

    function textOf(element, limit = 220) {
        if (!element || !isProbablyVisible(element)) {
            return '';
        }
        const text = (element.textContent || '').replace(/\s+/g, ' ').trim();
        return text.length > limit ? text.slice(0, limit - 3).trim() + '...' : text;
    }

    function safeFieldValue(name, limit = 500) {
        const field = findField(name);
        if (!field || !isProbablyVisible(field)) {
            return '';
        }
        const type = String(field.type || '').toLowerCase();
        if (type === 'password' || type === 'hidden' || field.name === '_token' || field.name === 'csrf_token') {
            return '';
        }
        const value = type === 'checkbox' || type === 'radio'
            ? (field.checked ? 'checked' : '')
            : String(field.value || '').trim();
        return value.length > limit ? value.slice(0, limit - 3).trim() + '...' : value;
    }

    function collectCreatorCards() {
        const selectors = [
            '[data-creator-id]',
            '.creator-option',
            '.creator-card',
            '.creator-result-card',
            '.creator-pick-card'
        ];
        const cards = uniqueFields(selectors.flatMap((selector) => Array.from(document.querySelectorAll(selector))));
        return cards
            .filter(isProbablyVisible)
            .slice(0, 8)
            .map((card) => {
                const name = card.dataset.creatorName || card.dataset.name || textOf(card.querySelector('strong, h3, h4, .creator-name'), 80);
                const email = card.dataset.creatorEmail || card.dataset.email || textOf(card.querySelector('[href^="mailto:"], .creator-email, small'), 90);
                const id = card.dataset.creatorId || '';
                const category = card.dataset.creatorCategory || card.dataset.category || '';
                const niche = card.dataset.creatorNiche || card.dataset.niche || '';
                return {
                    id,
                    name: name || textOf(card, 80),
                    email,
                    category,
                    niche,
                    details: textOf(card, 420),
                };
            })
            .filter((creator) => creator.name !== '');
    }

    function collectCardHighlights() {
        const ctx = window.CRE8PILOT_CONTEXT || {};
        if (String(ctx.page || '') === 'creator_candidature_workspace' && String(ctx.mode || '') === 'list') {
            const candidatureCards = uniqueFields(Array.from(document.querySelectorAll('.candidature-card')))
                .filter(isProbablyVisible)
                .slice(0, 12)
                .map((item) => textOf(item, 260))
                .filter(Boolean);
            if (candidatureCards.length > 0) {
                return candidatureCards;
            }
        }
        const brandOfferShell = document.querySelector('[data-offer-tab-shell]');
        const selectors = [
            '.metric-card',
            '.stat-card',
            '.admin-stat-card',
            '.summary-card',
            '.response-context',
            '.review-card',
            '.detail-card',
            '.candidature-card',
            '.negotiation-entry',
            '.source-card',
            '.response-modal-panel',
            '.response-modal-card',
            '.response-modal-title',
            '[data-response-modal-panel]',
            '.chart-card',
            '.chart-legend',
            'tbody tr'
        ];
        const items = uniqueFields(selectors.flatMap((selector) => Array.from(document.querySelectorAll(selector))));
        return items
            .filter(isProbablyVisible)
            .filter((item) => {
                if (!brandOfferShell || !item.closest) {
                    return true;
                }
                if (item.closest('.brand-stats-grid')) {
                    return false;
                }
                return true;
            })
            .slice(0, 12)
            .map((item) => textOf(item, 260))
            .filter(Boolean);
    }

    function collectBrandOfferWorkspaceListSnapshot() {
        const shell = document.querySelector('[data-offer-tab-shell]');
        if (!shell) {
            return null;
        }
        const tabCounts = {};
        shell.querySelectorAll('[data-brand-tab-count]').forEach((el) => {
            const key = el.getAttribute('data-brand-tab-count') || '';
            if (!key) {
                return;
            }
            const raw = String(el.textContent || '').replace(/[^\d-]/g, '');
            const n = parseInt(raw, 10);
            tabCounts[key] = Number.isFinite(n) ? n : 0;
        });
        const activeBtn = document.querySelector('.offer-tab-button.is-active[data-offer-tab]');
        const activeOfferTab = activeBtn ? (activeBtn.getAttribute('data-offer-tab') || '') : '';
        const activePanel = activeOfferTab
            ? Array.from(shell.querySelectorAll('[data-offer-tab-panel]')).find((panel) => panel.getAttribute('data-offer-tab-panel') === activeOfferTab)
            : null;
        const cardScope = activePanel || shell;
        const cards = Array.from(cardScope.querySelectorAll('.offer-card'))
            .filter(isProbablyVisible)
            .slice(0, 40);
        const offers = cards.map((card) => ({
            id: card.getAttribute('data-offer-id') || '',
            title: card.getAttribute('data-offer-title') || '',
            section: card.getAttribute('data-brand-section-key') || '',
            status: card.getAttribute('data-cre8pilot-status') || '',
            budget: card.getAttribute('data-cre8pilot-budget') || '',
            deadline: card.getAttribute('data-cre8pilot-deadline') || '',
            published: card.getAttribute('data-cre8pilot-published') || '',
            publishedDate: card.getAttribute('data-cre8pilot-published-date') || card.getAttribute('data-cre8pilot-published') || '',
            responseCount: card.getAttribute('data-cre8pilot-response-count') || '0',
            targetCreator: card.getAttribute('data-creator-name') || '',
            latestSignal: card.getAttribute('data-cre8pilot-signal') || '',
            objective: card.getAttribute('data-cre8pilot-objective') || '',
            cardText: textOf(card, 700),
        })).filter((o) => o.title !== '');
        return {
            brandOfferList: true,
            tabCounts,
            activeOfferTab,
            visibleOfferScope: activeOfferTab || 'all',
            offers,
            snapshotAt: Date.now(),
        };
    }

    function cre8pilotCleanDraftFieldText(raw) {
        let s = String(raw ?? '');
        const snippets = [
            /\s*[—\-]\s*I\s+cannot\s+send\s+this\s+automatically\.?/giu,
            /\s*[—\-]\s*I\s+cannot\s+accept\s+automatically\s+on\s+your\s+behalf\.?/giu,
            /\s*[—\-]\s*I\s+cannot\s+refuse\s+automatically[^.]*(?:\.|$)/giu,
            /\s*Please\s+review\s+the\s+wording\s+and\s+numbers\s+before\s+sending[^.]*(?:\.|$)/giu,
            /\s*Please\s+review\s+before\s+sending[^.]*(?:\.|$)/giu,
            /\s*I\s+cannot\s+submit\s+this\s+automatically\.?/giu,
            /\s*I\s+cannot\s+save\s+this\s+automatically\.?/giu,
            /\s*I\s+cannot\s+publish\s+this\s+automatically\.?/giu,
            /\s*I\s+will\s+not\s+submit\s+or\s+save\s+anything\s+automatically\.?/giu,
            /\s*Please\s+review\s+the\s+prepared\s+content\.?/giu,
            /\s*Use\s+the\s+page\s+button\s+yourself[^.]*(?:\.|$)/giu,
            /\s*Some\s+fields\s+may\s+still\s+need\s+manual\s+review\.?/giu,
            /\s*I\s+filled\s+the\s+available\s+fields\.?/giu,
            /\s*I\s+cannot\s+send\s+or\s+accept\s+automatically[^.]*(?:\.|$)/giu,
        ];
        snippets.forEach((re) => {
            re.lastIndex = 0;
            s = s.replace(re, '');
        });
        s = s.replace(/\s{2,}/g, ' ').replace(/\s+\./g, '.').replace(/\.{2,}/g, '.').replace(/\s*[—\-]\s*$/u, '').trim();
        return s;
    }

    function cre8pilotResetFilterForm() {
        let form = cre8pilotFindFormForTarget('filter_form');
        if (!form || form.tagName.toUpperCase() !== 'FORM') {
            const cands = cre8pilotFindFilterFormCandidates();
            form = cands[0] || null;
        }
        if (!form || form.tagName.toUpperCase() !== 'FORM') {
            cre8pilotLogSafeUi('safeUiActionBlocked', 'filter_form_not_found');
            return false;
        }
        form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach((el) => {
            const typ = String(el.type || '').toLowerCase();
            const nm = String(el.name || '').toLowerCase();
            if (nm === '_token' || nm === 'csrf_token') {
                return;
            }
            if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
                return;
            }
            if (typ === 'checkbox' || typ === 'radio') {
                el.checked = false;
                return;
            }
            el.value = '';
        });
        try {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (e) {}
        return cre8pilotSubmitFilterForm('filter_form');
    }

    function fillField(name, value, opts) {
        const o = opts && typeof opts === 'object' ? opts : {};
        const scope = o.scopeRoot && typeof o.scopeRoot.querySelector === 'function' ? o.scopeRoot : null;
        const field = findField(name, scope);
        if (!field) {
            return false;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = Boolean(value);
        } else {
            let v = String(value);
            const typ = String(field.type || '').toLowerCase();
            const textLike = field.tagName === 'TEXTAREA'
                || (field.tagName === 'INPUT' && ['text', 'search', 'url', 'email', 'tel'].indexOf(typ) >= 0);
            if (!o.skipDraftClean && textLike) {
                v = cre8pilotCleanDraftFieldText(v);
            }
            field.value = v;
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        cre8pilotEnsureFieldVisible(field);
        if (!o.skipFieldHighlight) {
            cre8pilotHighlight(field, 2800);
        }
        return true;
    }

    function cre8pilotEnsureFieldVisible(field) {
        if (!field) {
            return;
        }
        try {
            const dialog = field.closest('dialog');
            if (dialog && typeof dialog.showModal === 'function' && !dialog.open) {
                try { dialog.showModal(); } catch (e) { try { dialog.show(); } catch (e2) {} }
            }
            const detailsEl = field.closest('details');
            if (detailsEl && !detailsEl.open) {
                detailsEl.open = true;
            }
            const hiddenAttrParent = field.closest('[hidden]');
            if (hiddenAttrParent && hiddenAttrParent !== field) {
                hiddenAttrParent.removeAttribute('hidden');
            }
            const hiddenClassParent = field.closest('.is-hidden, .hidden, .d-none');
            if (hiddenClassParent && hiddenClassParent !== field) {
                hiddenClassParent.classList.remove('is-hidden', 'hidden', 'd-none');
            }
        } catch (e) {}
    }

    function cre8pilotHighlight(el, durationMs) {
        if (!el || !el.classList) {
            return;
        }
        el.classList.add('cre8pilot-field-highlight');
        const t = Math.max(1500, Math.min(4000, durationMs || 2800));
        window.setTimeout(() => el.classList.remove('cre8pilot-field-highlight'), t);
    }

    function cre8pilotFindFormForTarget(target) {
        const t = String(target || '').toLowerCase();
        const map = {
            offer_form: '[data-cre8pilot-form="offer_form"], form#offerForm, form[name="offerForm"], form[data-form="offer"], form.offer-form',
            candidature_form: '[data-cre8pilot-form="candidature_form"], form#candidatureForm, form[name="candidatureForm"], form.candidature-form',
            negotiation_form: '[data-cre8pilot-form="negotiation_form"], form#negotiationForm, form[name="negotiationForm"], form.negotiation-form, [data-response-modal-panel="negotiate"] form, [data-cre8pilot-section="negotiation"] form, form[data-modal-variant="negotiate"]',
            brand_decision_form: '[data-cre8pilot-form="brand_decision_form"], form.brand-decision-form, form#brandDecisionForm, form[name="brandDecisionForm"], [data-response-modal-panel="decision"] form',
            decision_form: '[data-cre8pilot-form="decision_form"], form.decision-form, form#decisionForm',
            refusal_form: '[data-cre8pilot-form="refusal_form"], form.refusal-form',
            creator_decline_form: '[data-creator-response-modal-panel="decline"] form, form[data-modal-variant="refuse"]',
            filter_form: '[data-cre8pilot-form="filter_form"], form.filter-stack, form.filter-form, form.search-form, form[role="search"], form#filterForm, section.filter-card form[method="get"], form[method="get"][action*="brand_index"]',
            search_form: '[data-cre8pilot-form="search_form"], form.search-form, form[role="search"]',
            sort_form: '[data-cre8pilot-form="sort_form"], form.search-form, form.filter-form, form[role="search"], form.filter-stack',
        };
        const selector = map[t] || ('[data-cre8pilot-form="' + t + '"]');
        return document.querySelector(selector);
    }

    function cre8pilotLogSafeUi(blockReason, detail) {
        if (window.console && typeof window.console.warn === 'function') {
            window.console.warn('[Cre8Pilot]', blockReason, detail || '');
        }
    }

    function cre8pilotNormalizeButtonText(el) {
        if (!el) {
            return '';
        }
        const t = (el.innerText || el.textContent || el.value || el.getAttribute('aria-label') || '').replace(/\s+/g, ' ').trim().toLowerCase();
        return t;
    }

    function cre8pilotIsUnsafeButtonText(label) {
        const t = String(label || '').toLowerCase();
        if (t === '') {
            return true;
        }
        const unsafeRes = [
            /\bsave\b/, /\bpublish\b/, /\bdelete\b/, /\bremove\b/, /\brefuse\b/, /\bdecline\b/, /\bsend\b/, /\bsubmit\b/,
            /\baccept\b/, /\bapprove\b/, /\bconfirm\b/, /\breject\b/,
            /\baccepter\b/, /\bconfirmer\b/,
            /submit\s+candidature/, /negotiation\s+reply/, /send\s+negotiation/, /start\s+negotiation/, /update\s+proposal/,
            /\benregistrer\b/, /\bpublier\b/, /\bsupprimer\b/, /\brefuser\b/, /\benvoyer\b/, /\bmettre\s+à\s+jour\b/,
        ];
        for (let i = 0; i < unsafeRes.length; i++) {
            unsafeRes[i].lastIndex = 0;
            if (unsafeRes[i].test(t)) {
                return true;
            }
        }
        return false;
    }

    function cre8pilotIsSafeUiButtonText(label) {
        const t = String(label || '').toLowerCase();
        if (t === '' || cre8pilotIsUnsafeButtonText(t)) {
            return false;
        }
        const safeTokens = [
            'apply', 'filter', 'search', 'show', 'sort', 'reset', 'afficher', 'filtrer', 'rechercher', 'trier',
        ];
        for (let i = 0; i < safeTokens.length; i++) {
            if (t.includes(safeTokens[i])) {
                return true;
            }
        }
        return false;
    }

    function cre8pilotFindFilterFormCandidates() {
        const selectors = [
            '[data-cre8pilot-form="filter_form"]',
            'form.filter-stack',
            'section.filter-card form[method="get"]',
            'form[method="get"][action*="brand_index"]',
            'form[method="get"].filter-stack',
        ];
        const seen = new Set();
        const out = [];
        for (let s = 0; s < selectors.length; s++) {
            document.querySelectorAll(selectors[s]).forEach((form) => {
                if (!form || form.tagName.toUpperCase() !== 'FORM' || seen.has(form)) {
                    return;
                }
                const method = (form.getAttribute('method') || 'get').toLowerCase();
                if (method !== 'get') {
                    return;
                }
                seen.add(form);
                out.push(form);
            });
        }
        return out;
    }

    function cre8pilotOpenBrandResponseModal(panelName, options) {
        const api = window.__cre8connectBrandActionsModal;
        if (api && typeof api.open === 'function') {
            try {
                api.open(panelName, options || {});
                return true;
            } catch (e) {}
        }
        const overlay = document.querySelector('[data-response-modal-overlay]');
        if (!overlay) {
            return false;
        }
        const panel = overlay.querySelector('[data-response-modal-panel="' + String(panelName || '').replace(/"/g, '') + '"]');
        if (!panel) {
            return false;
        }
        overlay.querySelectorAll('[data-response-modal-panel]').forEach((p) => p.setAttribute('hidden', 'hidden'));
        overlay.removeAttribute('hidden');
        overlay.style.display = 'flex';
        overlay.classList.add('is-open');
        panel.removeAttribute('hidden');
        document.documentElement.classList.add('offre-modal-open');
        document.body.classList.add('offre-modal-open');
        if (panelName === 'decision') {
            const st = (options && options.decisionStatus) || overlay.dataset.defaultDecisionStatus || 'acceptee';
            const input = panel.querySelector('[data-decision-status-input]');
            if (input) {
                input.value = st === 'refusee' ? 'refusee' : 'acceptee';
            }
        }
        return true;
    }

    function cre8pilotClickNegotiationOpenTriggers() {
        const triggers = [
            '[data-response-modal-trigger="negotiate"]',
            'button.brand-action-launch-negotiate',
            '[data-cre8pilot-open-negotiation]',
        ];
        for (let i = 0; i < triggers.length; i++) {
            const btn = document.querySelector(triggers[i]);
            const type = btn ? String(btn.getAttribute('type') || '').toLowerCase() : '';
            const isNegotiateTrigger = btn && btn.getAttribute('data-response-modal-trigger') === 'negotiate';
            if (btn && type !== 'submit' && (isNegotiateTrigger || !cre8pilotIsUnsafeButtonText(cre8pilotNormalizeButtonText(btn)))) {
                try {
                    btn.click();
                    return true;
                } catch (e) {}
            }
        }
        return false;
    }

    function cre8pilotResolveExclusiveWindowFromAction(action) {
        if (!action || typeof action !== 'object') {
            return '';
        }
        const ew = String(action.exclusiveWindow || '').trim().toLowerCase();
        if (ew !== '') {
            return ew;
        }
        const t = String(action.type || '');
        const tgt = String(action.target || '');
        if (t === 'fill_negotiation_form' || tgt === 'negotiation_form') {
            return 'negotiation';
        }
        if (t === 'fill_accept_form') {
            return 'accept';
        }
        if (t === 'fill_decline_form') {
            return 'decline';
        }
        return '';
    }

    function cre8pilotOpenExclusiveWindow(targetWindow) {
        const w = String(targetWindow || '').trim().toLowerCase();
        const api = window.__cre8connectBrandActionsModal;
        if (api && typeof api.close === 'function') {
            try {
                api.close();
            } catch (e) {}
        }
        let panel = '';
        const opts = {};
        if (w === 'negotiation' || w === 'negotiate') {
            panel = 'negotiate';
        } else if (w === 'accept') {
            panel = 'decision';
            opts.decisionStatus = 'acceptee';
        } else if (w === 'decline' || w === 'refuse') {
            panel = 'decision';
            opts.decisionStatus = 'refusee';
        } else {
            return null;
        }
        if (!cre8pilotOpenBrandResponseModal(panel, opts) && panel === 'negotiate') {
            cre8pilotClickNegotiationOpenTriggers();
        }
        const overlay = document.querySelector('[data-response-modal-overlay]');
        if (!overlay) {
            return null;
        }
        const safePanel = String(panel).replace(/"/g, '');
        return overlay.querySelector('[data-response-modal-panel="' + safePanel + '"]');
    }

    function cre8pilotPrepareSectionForFill(action) {
        if (!action || typeof action !== 'object') {
            return;
        }
        const fillTarget = String(action.target || '');
        const creatorOverlay = document.querySelector('[data-creator-response-modal-overlay]');
        if (creatorOverlay && fillTarget === 'creator_decline_form') {
            const panel = creatorOverlay.querySelector('[data-creator-response-modal-panel="decline"]');
            if (panel) {
                creatorOverlay.querySelectorAll('[data-creator-response-modal-panel]').forEach((node) => node.setAttribute('hidden', 'hidden'));
                creatorOverlay.removeAttribute('hidden');
                creatorOverlay.style.display = 'flex';
                creatorOverlay.classList.add('is-open');
                panel.removeAttribute('hidden');
                document.documentElement.classList.add('offre-modal-open');
                document.body.classList.add('offre-modal-open');
                action._cre8pilotFieldScope = panel;
            }
            return;
        }
        const openSection = String(action.openSection || '').toLowerCase();
        const hasBrandOverlay = !!document.querySelector('[data-response-modal-overlay]');
        let ew = cre8pilotResolveExclusiveWindowFromAction(action);
        if (ew === '' && hasBrandOverlay) {
            let panel = String(action.openModalPanel || '').trim();
            if (!panel && (openSection === 'negotiation' || fillTarget === 'negotiation_form')) {
                panel = 'negotiate';
            }
            if (!panel && (openSection === 'accept' || action.type === 'fill_accept_form' || (fillTarget === 'brand_decision_form' && action.openModalDecisionStatus === 'acceptee'))) {
                panel = 'decision';
            }
            if (!panel && (openSection === 'decline' || openSection === 'refusal' || action.type === 'fill_decline_form' || (fillTarget === 'brand_decision_form' && action.openModalDecisionStatus === 'refusee'))) {
                panel = 'decision';
            }
            if (!panel && fillTarget === 'brand_decision_form') {
                const fields = action.fields && typeof action.fields === 'object' ? action.fields : {};
                if (fields.motifRefus != null && String(fields.motifRefus).trim() !== '') {
                    panel = 'decision';
                }
            }
            if (panel === 'negotiate') {
                ew = 'negotiation';
            } else if (panel === 'decision') {
                const ds = String(action.openModalDecisionStatus || '').trim();
                const fields = action.fields && typeof action.fields === 'object' ? action.fields : {};
                let status = ds;
                if (status === '' && fields.motifRefus != null && String(fields.motifRefus).trim() !== '') {
                    status = 'refusee';
                }
                if (status === '') {
                    status = 'acceptee';
                }
                ew = status === 'refusee' ? 'decline' : 'accept';
            }
        }
        if (hasBrandOverlay && ew !== '') {
            action._cre8pilotFieldScope = cre8pilotOpenExclusiveWindow(ew);
            return;
        }
        let panel = String(action.openModalPanel || '').trim();
        if (!panel && (openSection === 'negotiation' || fillTarget === 'negotiation_form')) {
            panel = 'negotiate';
        }
        if (!panel && (openSection === 'accept' || action.type === 'fill_accept_form' || (fillTarget === 'brand_decision_form' && action.openModalDecisionStatus === 'acceptee'))) {
            panel = 'decision';
        }
        if (!panel && (openSection === 'decline' || openSection === 'refusal' || action.type === 'fill_decline_form' || (fillTarget === 'brand_decision_form' && action.openModalDecisionStatus === 'refusee'))) {
            panel = 'decision';
        }
        if (!panel && fillTarget === 'brand_decision_form') {
            const fields = action.fields && typeof action.fields === 'object' ? action.fields : {};
            if (fields.motifRefus != null && String(fields.motifRefus).trim() !== '') {
                panel = 'decision';
            }
        }
        if (panel === 'negotiate') {
            if (!cre8pilotOpenBrandResponseModal('negotiate', {})) {
                cre8pilotClickNegotiationOpenTriggers();
            }
            return;
        }
        if (panel === 'decision') {
            const ds = String(action.openModalDecisionStatus || '').trim();
            const fields = action.fields && typeof action.fields === 'object' ? action.fields : {};
            let status = ds;
            if (status === '' && fields.motifRefus != null && String(fields.motifRefus).trim() !== '') {
                status = 'refusee';
            }
            if (status === '') {
                status = 'acceptee';
            }
            cre8pilotOpenBrandResponseModal('decision', { decisionStatus: status === 'refusee' ? 'refusee' : 'acceptee' });
        }
    }

    function cre8pilotFocusChangedFields(action, overrideTargets) {
        if (!action || typeof action !== 'object') {
            return;
        }
        const fields = action.fields && typeof action.fields === 'object' ? action.fields : {};
        const target = String(action.target || '');
        const highlightMs = Math.max(2000, Math.min(6000, parseInt(action.highlightDuration || 3500, 10) || 3500));
        const doFocus = action.focusAfter !== false;
        const doHighlight = action.highlightAfter !== false;
        let namesToFocus = [];
        if (Array.isArray(overrideTargets) && overrideTargets.length > 0) {
            namesToFocus = overrideTargets.map((x) => String(x || '').trim()).filter(Boolean);
        } else if (Array.isArray(action.targets) && action.targets.length > 0) {
            namesToFocus = action.targets.map((x) => String(x || '').trim()).filter(Boolean);
        } else {
            namesToFocus = Object.keys(fields);
        }
        const scopeRoot = action._cre8pilotFieldScope && typeof action._cre8pilotFieldScope.querySelector === 'function' ? action._cre8pilotFieldScope : null;
        const fieldEls = [];
        for (let i = 0; i < namesToFocus.length; i++) {
            const f = findField(namesToFocus[i], scopeRoot);
            if (f) {
                fieldEls.push(f);
            }
        }

        const form = cre8pilotFindFormForTarget(target) || (fieldEls[0] ? fieldEls[0].closest('form, section, dialog') : null);
        if (form) {
            try {
                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } catch (e) {
                form.scrollIntoView();
            }
            if (doHighlight) {
                cre8pilotHighlight(form, Math.min(highlightMs, 2200));
            }
            form.classList.add('cre8pilot-section-highlight');
            window.setTimeout(() => form.classList.remove('cre8pilot-section-highlight'), Math.min(highlightMs, 2200));
        }

        if (fieldEls.length > 0) {
            fieldEls.forEach((el) => cre8pilotEnsureFieldVisible(el));
            const first = fieldEls[0];
            try {
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } catch (e) {
                first.scrollIntoView();
            }
            if (doFocus) {
                let focusEl = first;
                for (let j = 0; j < fieldEls.length; j++) {
                    const tag = (fieldEls[j].tagName || '').toUpperCase();
                    const typ = String(fieldEls[j].type || '').toLowerCase();
                    if ((tag === 'TEXTAREA' || tag === 'SELECT' || (tag === 'INPUT' && typ !== 'hidden' && typ !== 'submit' && typ !== 'button')) && !fieldEls[j].disabled && !fieldEls[j].readOnly) {
                        focusEl = fieldEls[j];
                        break;
                    }
                }
                try {
                    focusEl.focus({ preventScroll: true });
                } catch (e) {}
            }
            if (doHighlight) {
                fieldEls.forEach((el) => cre8pilotHighlight(el, highlightMs));
            }
        }
    }

    function cre8pilotSubmitFilterForm(target) {
        let form = cre8pilotFindFormForTarget(target || 'filter_form');
        if (!form || form.tagName.toUpperCase() !== 'FORM') {
            const cands = cre8pilotFindFilterFormCandidates();
            form = cands[0] || null;
        }
        if (!form || form.tagName.toUpperCase() !== 'FORM') {
            cre8pilotLogSafeUi('safeUiActionBlocked', 'filter_form_not_found');
            return false;
        }
        const method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'get') {
            cre8pilotLogSafeUi('safeUiActionBlocked', 'non_get_filter_form');
            return false;
        }
        const brandAjax = window.__cre8connectBrandIndexApplyFilterAjax;
        if (typeof brandAjax === 'function' && brandAjax(form)) {
            return true;
        }
        try {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (e) {}
        const submits = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
        let chosen = null;
        for (let i = 0; i < submits.length; i++) {
            const lab = cre8pilotNormalizeButtonText(submits[i]);
            if (cre8pilotIsSafeUiButtonText(lab)) {
                chosen = submits[i];
                break;
            }
        }
        if (chosen) {
            try {
                chosen.click();
                return true;
            } catch (e) {}
        }
        if (submits.length === 0) {
            try {
                form.submit();
                return true;
            } catch (e3) {
                return false;
            }
        }
        let anyUnsafe = false;
        let anySafe = false;
        for (let j = 0; j < submits.length; j++) {
            const lab = cre8pilotNormalizeButtonText(submits[j]);
            if (cre8pilotIsUnsafeButtonText(lab)) {
                anyUnsafe = true;
            }
            if (cre8pilotIsSafeUiButtonText(lab)) {
                anySafe = true;
            }
        }
        if (anyUnsafe || !anySafe) {
            cre8pilotLogSafeUi('safeUiActionBlocked', anyUnsafe ? 'unsafe_submit_control' : 'unsafe_button_uncertain');
            return false;
        }
        try {
            form.submit();
            return true;
        } catch (e3) {
            return false;
        }
    }

    function cre8pilotSwitchTab(name) {
        const v = String(name || '').trim();
        if (v === '') {
            return false;
        }
        const candidates = [
            '[data-offer-tab="' + v + '"]',
            '[data-tab="' + v + '"]',
            '[data-tab-target="' + v + '"]',
            '[role="tab"][data-tab-key="' + v + '"]',
            'button.tab-button[data-tab="' + v + '"]',
        ];
        for (let i = 0; i < candidates.length; i++) {
            const btn = document.querySelector(candidates[i]);
            if (btn) {
                try { btn.click(); } catch (e) {}
                cre8pilotHighlight(btn, 1500);
                return true;
            }
        }
        return false;
    }

    function cre8pilotOpenModal(modalSelector) {
        const sel = String(modalSelector || '').trim();
        if (sel === '') {
            return false;
        }
        const node = document.querySelector(sel);
        if (!node) {
            return false;
        }
        if (node.tagName && node.tagName.toUpperCase() === 'DIALOG' && typeof node.showModal === 'function') {
            try { if (!node.open) { node.showModal(); } return true; } catch (e) {}
        }
        if (node.hasAttribute && node.hasAttribute('hidden')) {
            node.removeAttribute('hidden');
            return true;
        }
        if (node.classList && (node.classList.contains('is-hidden') || node.classList.contains('hidden'))) {
            node.classList.remove('is-hidden', 'hidden');
            return true;
        }
        return false;
    }

    function cre8pilotHandleSafeUiAction(action, messages) {
        if (!action || typeof action !== 'object') {
            return;
        }
        const sub = String(action.action || action.subType || '').toLowerCase();
        const target = String(action.target || '');
        if (sub === '') {
            return;
        }

        if (sub === 'apply_filter_submit' || sub === 'apply_search_submit') {
            const ok = cre8pilotSubmitFilterForm(target || 'filter_form');
            if (!ok) {
                appendMessage(messages, 'I prepared the filter, but could not auto-submit on this page. Please click Apply to refresh the list.', 'assistant', 'action');
            }
            return;
        }
        if (sub === 'sort_results') {
            const ok = cre8pilotSubmitFilterForm(target || 'filter_form');
            if (!ok) {
                appendMessage(messages, 'I prepared the sort selection, but could not auto-submit on this page.', 'assistant', 'action');
            }
            return;
        }
        if (sub === 'switch_tab') {
            cre8pilotSwitchTab(action.tab || target);
            return;
        }
        if (sub === 'open_section') {
            const sec = String(action.section || target || '').toLowerCase();
            if (sec === 'negotiation' || sec === 'negotiate') {
                cre8pilotPrepareSectionForFill({ openModalPanel: 'negotiate', target: 'negotiation_form' });
            } else if (sec === 'accept' || sec === 'acceptance' || sec === 'decision_accept') {
                cre8pilotPrepareSectionForFill({ openModalPanel: 'decision', openModalDecisionStatus: 'acceptee', target: 'brand_decision_form' });
            } else if (sec === 'decline' || sec === 'refusal' || sec === 'refuse' || sec === 'decision_refuse') {
                cre8pilotPrepareSectionForFill({ openModalPanel: 'decision', openModalDecisionStatus: 'refusee', target: 'brand_decision_form' });
            }
            return;
        }
        if (sub === 'open_modal') {
            if (action.openModalPanel || action.panel) {
                cre8pilotOpenBrandResponseModal(String(action.openModalPanel || action.panel || '').trim(), {
                    decisionStatus: action.openModalDecisionStatus || action.decisionStatus,
                });
                return;
            }
            cre8pilotOpenModal(action.selector || action.modal || '');
            return;
        }
        if (sub === 'focus_changed_field') {
            cre8pilotFocusChangedFields(action);
            return;
        }
        if (sub === 'scroll_to_form') {
            const form = cre8pilotFindFormForTarget(target);
            if (form) {
                try {
                    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } catch (e) {}
                form.classList.add('cre8pilot-section-highlight');
                window.setTimeout(() => form.classList.remove('cre8pilot-section-highlight'), 1800);
            }
        }
    }

    function cre8pilotMergeFilterPayload(action) {
        const fields = action.fields && typeof action.fields === 'object' ? Object.assign({}, action.fields) : {};
        const filter = action.filter && typeof action.filter === 'object' ? action.filter : {};
        Object.keys(filter).forEach((k) => {
            if (k === '__proto__' || k === 'constructor') {
                return;
            }
            fields[k] = filter[k];
        });
        return fields;
    }

    function handleAction(action, messages, widget) {
        if (!action || typeof action !== 'object') {
            return;
        }

        const type = String(action.type || '');

        if (type === 'show_message') {
            appendMessage(messages, String(action.message || ''), 'assistant');
            return;
        }

        if (type === 'open_section') {
            const sec = String(action.section || action.target || '').toLowerCase();
            if (sec === 'negotiation' || sec === 'negotiate') {
                cre8pilotPrepareSectionForFill({ openModalPanel: 'negotiate', target: 'negotiation_form' });
            } else if (sec === 'accept' || sec === 'acceptance' || sec === 'decision_accept') {
                cre8pilotPrepareSectionForFill({ openModalPanel: 'decision', openModalDecisionStatus: 'acceptee', target: 'brand_decision_form' });
            } else if (sec === 'decline' || sec === 'refusal' || sec === 'refuse' || sec === 'decision_refuse') {
                cre8pilotPrepareSectionForFill({ openModalPanel: 'decision', openModalDecisionStatus: 'refusee', target: 'brand_decision_form' });
            }
            return;
        }

        if (type === 'open_modal') {
            if (action.openModalPanel || action.panel) {
                cre8pilotOpenBrandResponseModal(String(action.openModalPanel || action.panel || '').trim(), {
                    decisionStatus: action.openModalDecisionStatus || action.decisionStatus,
                });
            } else {
                cre8pilotOpenModal(action.selector || action.modal || '');
            }
            return;
        }

        if (type === 'switch_tab') {
            cre8pilotSwitchTab(action.tab || action.target || '');
            return;
        }

        if (type === 'scroll_to_form') {
            const form = cre8pilotFindFormForTarget(String(action.target || ''));
            if (form) {
                try {
                    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } catch (e) {
                    form.scrollIntoView();
                }
                form.classList.add('cre8pilot-section-highlight');
                window.setTimeout(() => form.classList.remove('cre8pilot-section-highlight'), 2200);
            }
            return;
        }

        if (type === 'focus_changed_field') {
            cre8pilotFocusChangedFields(action);
            return;
        }

        if (type === 'apply_filter_submit' || type === 'apply_search_submit') {
            const merged = Object.assign({}, action, {
                type: 'apply_filter',
                fields: cre8pilotMergeFilterPayload(action),
                submit: action.submit !== false && action.submit !== '0',
            });
            handleAction(merged, messages, widget);
            return;
        }

        if (type === 'clear_form') {
            const clearTarget = String(action.target || '');
            let clearFields = [];
            if (Array.isArray(action.fields)) {
                clearFields = action.fields.map((name) => String(name || '').trim()).filter(Boolean);
            } else if (action.fields && typeof action.fields === 'object') {
                clearFields = Object.keys(action.fields).map((name) => String(name || '').trim()).filter(Boolean);
            }
            if (clearFields.length === 0 && Array.isArray(action.targets)) {
                clearFields = action.targets.map((name) => String(name || '').trim()).filter(Boolean);
            }
            clearFields = uniqueFields(clearFields);

            if (widget) {
                setCre8PilotAvatarState('filling', widget);
            }
            cre8pilotPrepareSectionForFill(action);
            const fieldScope = action._cre8pilotFieldScope && typeof action._cre8pilotFieldScope.querySelector === 'function' ? action._cre8pilotFieldScope : null;
            let cleared = 0;
            clearFields.forEach((name) => {
                if (fillField(name, '', { skipFieldHighlight: true, skipDraftClean: true, scopeRoot: fieldScope })) {
                    cleared++;
                }
            });
            if (cleared > 0 && (action.focusAfter !== false || action.highlightAfter !== false)) {
                cre8pilotFocusChangedFields(Object.assign({}, action, { fields: clearFields.reduce((acc, name) => {
                    acc[name] = '';
                    return acc;
                }, {}) }));
            } else if (clearTarget) {
                const form = cre8pilotFindFormForTarget(clearTarget);
                if (form) {
                    try { form.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
                }
            }
            delete action._cre8pilotFieldScope;
            appendMessage(
                messages,
                cleared > 0
                    ? 'I cleared the available fields. Nothing was saved or submitted.'
                    : 'I could not find matching fields to clear on this page.',
                'assistant',
                cleared > 0 ? 'action' : 'blocked'
            );
            if (widget) {
                window.setTimeout(() => setCre8PilotAvatarState('success', widget), 120);
                window.setTimeout(() => setCre8PilotAvatarState('idle', widget), 2200);
            }
            return;
        }

        const fillTypeAliases = {
            fill_offer_form: 'offer_form',
            fill_negotiation_form: 'negotiation_form',
            fill_accept_form: 'brand_decision_form',
            fill_decline_form: 'brand_decision_form',
            fill_candidature_form: 'candidature_form',
        };
        if (fillTypeAliases[type]) {
            const inferred = Object.assign({}, action, {
                type: 'fill_form',
                target: action.target || fillTypeAliases[type],
            });
            if (type === 'fill_negotiation_form' && !inferred.openModalPanel) {
                inferred.openModalPanel = 'negotiate';
                inferred.openSection = inferred.openSection || 'negotiation';
            }
            if (type === 'fill_accept_form' && !inferred.openModalPanel) {
                inferred.openModalPanel = 'decision';
                inferred.openModalDecisionStatus = inferred.openModalDecisionStatus || 'acceptee';
            }
            if (type === 'fill_decline_form' && !inferred.openModalPanel) {
                inferred.openModalPanel = 'decision';
                inferred.openModalDecisionStatus = inferred.openModalDecisionStatus || 'refusee';
            }
            handleAction(inferred, messages, widget);
            return;
        }

        if (type === 'fill_form') {
            const fillTarget = String(action.target || '');
            const fillFields = action.fields && typeof action.fields === 'object' ? action.fields : {};
            if (fillTarget === 'offer_form' && Object.keys(fillFields).length > 0) {
                window.__cre8PilotLastPreparedOffer = Object.assign({}, window.__cre8PilotLastPreparedOffer || {}, fillFields);
            }
            if (widget) {
                setCre8PilotAvatarState('filling', widget);
            }
            cre8pilotPrepareSectionForFill(action);
            const fieldScope = action._cre8pilotFieldScope && typeof action._cre8pilotFieldScope.querySelector === 'function' ? action._cre8pilotFieldScope : null;
            const fillOpts = { skipFieldHighlight: true, scopeRoot: fieldScope };
            let filled = 0;
            Object.keys(fillFields).forEach((name) => {
                if (fillField(name, fillFields[name], fillOpts)) {
                    filled++;
                }
            });
            if (filled > 0 && (action.focusAfter !== false || action.highlightAfter !== false)) {
                cre8pilotFocusChangedFields(action);
            }
            delete action._cre8pilotFieldScope;
            appendMessage(
                messages,
                filled > 0
                    ? 'I filled the available fields. Some fields may still need manual review.'
                    : 'I could not find matching fields on this page.',
                'assistant',
                filled > 0 ? 'action' : 'blocked'
            );
            if (widget) {
                window.setTimeout(() => setCre8PilotAvatarState('success', widget), 120);
                window.setTimeout(() => setCre8PilotAvatarState('idle', widget), 2200);
            }
            return;
        }

        if (type === 'highlight_field') {
            const fieldName = action.field || action.name;
            const field = findField(fieldName);
            if (field) {
                cre8pilotEnsureFieldVisible(field);
                try { field.focus({ preventScroll: false }); } catch (e) {}
                cre8pilotHighlight(field, 3500);
            }
            return;
        }

        if (type === 'reset_filter_submit') {
            const ok = cre8pilotResetFilterForm();
            appendMessage(
                messages,
                ok
                    ? 'I reset the filters. This only changes the visible list; nothing was deleted or modified.'
                    : 'I could not reset filters automatically on this page. Use the Reset control or clear fields manually.',
                'assistant',
                ok ? 'action' : 'blocked'
            );
            return;
        }

        if (type === 'apply_filter') {
            if (action.switchTab) {
                cre8pilotSwitchTab(action.switchTab);
            }
            const fields = cre8pilotMergeFilterPayload(action);
            let touched = 0;
            Object.keys(fields).forEach((name) => {
                if (fillField(name, fields[name], { skipFieldHighlight: true, skipDraftClean: true })) {
                    touched++;
                }
            });
            if (touched > 0) {
                cre8pilotFocusChangedFields(Object.assign({}, action, { fields }));
            }
            const shouldSubmit = action.submit === true || action.autoSubmit === true;
            const suppressSuccessMessage = action.suppressSuccessMessage === true || action.suppressResultMessage === true;
            const listNote = 'This only changes the visible list; nothing was deleted or modified.';
            if (shouldSubmit && touched > 0) {
                window.setTimeout(() => {
                    const ok = cre8pilotSubmitFilterForm(action.target || 'filter_form');
                    if (ok) {
                        if (!suppressSuccessMessage) {
                            appendMessage(messages, 'I applied the filter. ' + listNote, 'assistant', 'action');
                        }
                    } else {
                        appendMessage(messages, 'I updated the filter fields on this page. If the list did not refresh, use the safe Apply / Search control yourself. ' + listNote, 'assistant', 'action');
                    }
                }, 200);
            } else {
                if (suppressSuccessMessage && touched > 0) {
                    return;
                }
                appendMessage(
                    messages,
                    touched > 0
                        ? (shouldSubmit
                            ? 'I prepared the filter values but could not run auto-apply without matching inputs.'
                            : 'I prepared the filter fields. Please apply them manually.')
                        : (action.switchTab
                            ? ('I switched the tab when possible. ' + listNote)
                            : 'No matching filter fields were found here.'),
                    'assistant',
                    'action'
                );
            }
            return;
        }

        if (type === 'safe_ui_action') {
            cre8pilotHandleSafeUiAction(action, messages);
            return;
        }
    }

    function pickOfferFieldFromDomOrSnapshot(name) {
        const fromDom = safeFieldValue(name);
        if (fromDom !== '') {
            return fromDom;
        }
        const snap = window.__cre8PilotLastPreparedOffer;
        if (!snap || typeof snap !== 'object') {
            return '';
        }
        const v = snap[name];
        if (v == null) {
            return '';
        }
        const s = String(v).trim();
        return s.length > 500 ? `${s.slice(0, 497)}...` : s;
    }

    function collectVisibleData() {
        const heading = document.querySelector('h1');
        const title = heading ? heading.textContent.trim() : document.title;
        const context = window.CRE8PILOT_CONTEXT || {};
        const snap = (typeof window.__cre8PilotLastPreparedOffer === 'object' && window.__cre8PilotLastPreparedOffer) ? window.__cre8PilotLastPreparedOffer : null;
        const brandListSnap = collectBrandOfferWorkspaceListSnapshot();
        const data = {
            title,
            url: window.location.pathname,
            page: context.page || 'unknown',
            mode: context.mode || '',
            role: context.role || '',
            formTarget: context.formTarget || '',
            visibleEntityType: context.visibleEntityType || '',
            visibleEntityId: context.visibleEntityId || '',
            lastPreparedOffer: snap || undefined,
            offerForm: {
                selectedCreator: textOf(document.querySelector('[data-selected-creator-summary], .selected-creator-card, .selected-creator, .creator-option.is-selected'), 260),
                titre: pickOfferFieldFromDomOrSnapshot('titre'),
                objectif: pickOfferFieldFromDomOrSnapshot('objectif'),
                description: pickOfferFieldFromDomOrSnapshot('description'),
                budgetPropose: pickOfferFieldFromDomOrSnapshot('budgetPropose'),
                dateLimite: pickOfferFieldFromDomOrSnapshot('dateLimite'),
                raisonChoix: pickOfferFieldFromDomOrSnapshot('raisonChoix'),
                attenteCollaboration: pickOfferFieldFromDomOrSnapshot('attenteCollaboration'),
                messagePersonnalise: pickOfferFieldFromDomOrSnapshot('messagePersonnalise'),
                category: pickOfferFieldFromDomOrSnapshot('category') || pickOfferFieldFromDomOrSnapshot('categorie'),
            },
            candidatureForm: {
                messageMotivation: safeFieldValue('messageMotivation'),
                conditionsCreateur: safeFieldValue('conditionsCreateur'),
                budgetPropose: safeFieldValue('budgetPropose'),
                delaiPropose: safeFieldValue('delaiPropose'),
                dateDisponibilite: safeFieldValue('dateDisponibilite'),
                portfolioUrl: safeFieldValue('portfolioUrl'),
                motifRefus: safeFieldValue('motifRefus'),
            },
            decisionForm: {
                noteDecision: safeFieldValue('noteDecision'),
                motifRefus: safeFieldValue('motifRefus'),
                messageNegociation: safeFieldValue('messageNegociation'),
                budgetPropose: safeFieldValue('budgetPropose'),
                delaiPropose: safeFieldValue('delaiPropose'),
            },
            creators: collectCreatorCards(),
            highlights: collectCardHighlights(),
            visibleItems: cre8pilotCollectVisibleItems(),
        };

        if (brandListSnap && typeof brandListSnap === 'object') {
            Object.assign(data, brandListSnap);
        }

        return data;
    }

    function quickActionsForContext(page, mode) {
        const key = String(page || 'unknown') + ':' + String(mode || '');
        const actions = {
            'brand_offer_workspace:create_offer': [
                ['Prepare an offer draft', 'fill_offer_form'],
                ['Recommend a creator', 'recommend_creator'],
                ['Suggest a budget', 'suggest_budget'],
                ['Improve current offer text', 'improve_offer_text'],
                ['Summarize this form', 'summarize_page'],
            ],
            'brand_offer_workspace:edit_offer': [
                ['Improve current offer text', 'improve_offer_text'],
                ['Suggest a budget', 'suggest_budget'],
                ['Summarize this form', 'summarize_page'],
            ],
            'brand_offer_workspace:list': [
                ['Show expired offers', 'apply_filters'],
                ['Show outdated offers', 'apply_filters'],
                ['Show published offers', 'apply_filters'],
                ['Show draft offers', 'apply_filters'],
                ['Summarize my offers', 'summarize_page'],
                ['What should I check first?', 'recommend_next_action'],
                ['Search offers', 'apply_search'],
                ['Explain statuses', 'explain_statuses'],
            ],
            'brand_offer_workspace:details': [
                ['Summarize this offer', 'summarize_page'],
                ['Check quality', 'analyze_page'],
                ['Suggest better budget', 'suggest_budget'],
                ['What can I improve?', 'analyze_page'],
            ],
            'brand_candidature_workspace:list': [
                ['Summarize candidatures', 'summarize_page'],
                ['What should I review first?', 'recommend_next_action'],
                ['Show negotiations', 'apply_filters'],
                ['Explain statuses', 'explain_statuses'],
                ['Search creator', 'apply_search'],
            ],
            'brand_candidature_workspace:review_details': [
                ['Summarize this candidature', 'summarize_candidature'],
                ['Prepare acceptance note', 'prepare_acceptance_note'],
                ['Prepare refusal note', 'prepare_refusal_note'],
                ['Prepare negotiation reply', 'prepare_negotiation_reply'],
                ['Check risk', 'security_check'],
            ],
            'brand_candidature_workspace:negotiation_reply': [
                ['Send a counter-proposal', 'prepare_negotiation_reply'],
                ['Improve current message', 'improve_negotiation_message'],
                ['Suggest budget', 'suggest_budget_delay'],
                ['Summarize negotiation', 'summarize_negotiation'],
                ['Check risk', 'security_check'],
            ],
            'creator_offer_workspace:list': [
                ['Summarize invitations', 'summarize_page'],
                ['Which invitation first?', 'recommend_next_action'],
                ['Best offer for me', 'recommend_next_action'],
                ['Sort by budget', 'sort_results'],
                ['Find beauty offers', 'apply_search'],
            ],
            'creator_offer_workspace:details': [
                ['Summarize this offer', 'summarize_page'],
                ['Explain budget', 'summarize_page'],
                ['What should I answer?', 'summarize_page'],
                ['Refuse politely', 'normal_chat'],
            ],
            'creator_candidature_workspace:application_form': [
                ['Prepare candidature response', 'fill_candidature_form'],
                ['Improve motivation', 'improve_motivation_message'],
                ['Suggest budget and delay', 'suggest_budget_delay'],
                ['Check candidature quality', 'summarize_page'],
                ['Summarize source', 'summarize_page'],
            ],
            'creator_candidature_workspace:negotiation_reply': [
                ['Send a counter-proposal', 'prepare_negotiation_reply'],
                ['Improve current message', 'improve_negotiation_message'],
                ['Suggest budget', 'suggest_budget_delay'],
                ['Summarize negotiation', 'summarize_negotiation'],
                ['Check risk', 'security_check'],
            ],
            'creator_candidature_workspace:list': [
                ['Summarize invitations', 'summarize_page'],
                ['Which invitation first?', 'recommend_next_action'],
                ['Best offer for me', 'recommend_next_action'],
                ['Summarize my candidatures', 'summarize_page'],
                ['What should I do next?', 'recommend_next_action'],
                ['Check risk', 'security_check'],
            ],
            'admin_offer_workspace:table': [
                ['Summarize offers table', 'summarize_page'],
                ['Which offers expired?', 'detect_risky_items'],
                ['Filter expired offers', 'apply_filters'],
                ['Sort by budget', 'sort_results'],
                ['Search offer', 'apply_search'],
            ],
            'admin_candidature_workspace:table': [
                ['Summarize candidatures table', 'summarize_page'],
                ['Show pending reviews', 'apply_filters'],
                ['Explain origins', 'explain_statuses'],
                ['Detect risky items', 'detect_risky_items'],
                ['Search creator', 'apply_search'],
            ],
            brand_create_offer: [
                ['Prepare an offer draft', 'fill_offer_form'],
                ['Recommend a creator', 'recommend_creator'],
                ['Suggest a budget', 'suggest_budget'],
                ['Improve current offer text', 'improve_offer_text'],
                ['Summarize this form', 'summarize_page'],
            ],
            brand_edit_offer: [
                ['Improve current offer text', 'improve_offer_text'],
                ['Suggest a budget', 'suggest_budget'],
                ['Summarize this form', 'summarize_page'],
            ],
            brand_candidature_review: [
                ['Summarize this candidature', 'summarize_candidature'],
                ['Prepare acceptance note', 'prepare_acceptance_note'],
                ['Prepare refusal note', 'prepare_refusal_note'],
                ['Prepare negotiation reply', 'prepare_negotiation_reply'],
                ['Check risk', 'security_check'],
            ],
            negotiation_page: [
                ['Send a counter-proposal', 'prepare_negotiation_reply'],
                ['Improve current message', 'improve_negotiation_message'],
                ['Suggest budget', 'suggest_budget_delay'],
                ['Summarize negotiation', 'summarize_negotiation'],
                ['Check risk', 'security_check'],
            ],
            creator_candidature_form: [
                ['Prepare candidature response', 'fill_candidature_form'],
                ['Improve motivation', 'improve_motivation_message'],
                ['Suggest budget and delay', 'suggest_budget_delay'],
                ['Prepare negotiation response', 'prepare_negotiation_reply'],
                ['Summarize offer', 'summarize_page'],
            ],
            admin_dashboard: [
                ['Explain statistics', 'explain_statistics'],
                ['Summarize activity', 'summarize_page'],
                ['Detect risky items', 'detect_risky_items'],
                ['Recommend admin actions', 'recommend_admin_actions'],
            ],
            admin_offers: [
                ['Explain statistics', 'explain_statistics'],
                ['Summarize activity', 'summarize_page'],
                ['Detect risky items', 'detect_risky_items'],
                ['Recommend admin actions', 'recommend_admin_actions'],
            ],
            admin_candidatures: [
                ['Explain statistics', 'explain_statistics'],
                ['Summarize activity', 'summarize_page'],
                ['Detect risky items', 'detect_risky_items'],
                ['Recommend admin actions', 'recommend_admin_actions'],
            ],
        };

        return actions[key] || actions[page] || [
            ['Summarize this page', 'summarize_page'],
            ['What can you do?', 'normal_chat'],
        ];
    }

    function isSpeechRecognitionSupported() {
        return Boolean(window.SpeechRecognition || window.webkitSpeechRecognition);
    }

    function isCre8PilotEdgeBrowser() {
        return navigator.userAgent.includes('Edg/');
    }

    const CRE8PILOT_WELCOME_TEXT = 'Hi, I\'m Cre8Pilot. I can help you summarize, analyze, recommend, or prepare forms depending on this page.';

    function setVoiceStatus(widget, message) {
        const status = widget.querySelector('[data-cre8pilot-voice-status]');
        if (!status) {
            return;
        }
        const text = String(message || '').trim();
        if (text === '') {
            status.textContent = '';
            status.hidden = true;
            return;
        }
        status.textContent = text;
        status.hidden = false;
    }

    function setComposerActivity(widget, message) {
        const el = widget.querySelector('[data-cre8pilot-activity]');
        if (!el) {
            return;
        }
        const text = String(message || '').trim();
        if (text === '') {
            el.textContent = '';
            el.hidden = true;
            return;
        }
        el.textContent = text;
        el.hidden = false;
    }

    function insertVoiceTextIntoTextarea(input, text) {
        const cleanText = String(text || '').replace(/\s+/g, ' ').trim();
        if (!cleanText || !input) {
            return;
        }

        const currentValue = String(input.value || '').trim();
        input.value = currentValue ? currentValue + ' ' + cleanText : cleanText;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
    }

    function stopCre8PilotSpeech() {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
        if (cre8PilotCurrentSpeech.message) {
            const w = cre8PilotCurrentSpeech.message.closest('[data-cre8pilot-widget]');
            if (w) {
                setCre8PilotAvatarState('idle', w);
            }
            cre8PilotCurrentSpeech.message.classList.remove('cre8pilot-speaking');
        }
        if (cre8PilotCurrentSpeech.button) {
            cre8PilotCurrentSpeech.button.textContent = '\uD83D\uDD0A';
            cre8PilotCurrentSpeech.button.title = 'Read this response aloud';
            cre8PilotCurrentSpeech.button.setAttribute('aria-label', 'Read this response aloud');
        }
        cre8PilotCurrentSpeech = {
            button: null,
            message: null,
        };
    }

    function speakCre8PilotText(text, options = {}) {
        if (!('speechSynthesis' in window)) {
            if (typeof options.onUnsupported === 'function') {
                options.onUnsupported();
            }
            return;
        }

        const cleanText = String(text || '').replace(/\s+/g, ' ').trim();
        if (!cleanText) {
            return;
        }

        if (options.button && cre8PilotCurrentSpeech.button === options.button && window.speechSynthesis.speaking) {
            stopCre8PilotSpeech();
            return;
        }

        stopCre8PilotSpeech();
        const utterance = new SpeechSynthesisUtterance(cleanText);
        utterance.lang = 'en-US';
        utterance.rate = 1;
        utterance.pitch = 1;

        const voices = window.speechSynthesis.getVoices();
        const englishVoice = voices.find((voice) => /^en[-_]?us/i.test(voice.lang)) || voices.find((voice) => /^en/i.test(voice.lang));
        if (englishVoice) {
            utterance.voice = englishVoice;
        }

        cre8PilotCurrentSpeech = {
            button: options.button || null,
            message: options.messageItem || null,
        };
        if (options.messageItem) {
            options.messageItem.classList.add('cre8pilot-speaking');
        }
        if (options.button) {
            options.button.textContent = '\u25A0';
            options.button.title = 'Stop reading';
            options.button.setAttribute('aria-label', 'Stop reading');
        }
        utterance.onstart = () => {
            const w = options.cre8PilotWidget || (options.messageItem && options.messageItem.closest('[data-cre8pilot-widget]')) || cre8PilotResolveWidget(null);
            if (w) {
                setCre8PilotAvatarState('ai_speaking', w);
            }
            if (typeof options.onStart === 'function') {
                options.onStart();
            }
        };
        utterance.onend = () => {
            const w = options.cre8PilotWidget || (options.messageItem && options.messageItem.closest('[data-cre8pilot-widget]')) || cre8PilotResolveWidget(null);
            stopCre8PilotSpeech();
            if (w) {
                const fallback = w.cre8PilotLastResponseAvatarState && w.cre8PilotLastResponseAvatarState !== 'ai_speaking'
                    ? w.cre8PilotLastResponseAvatarState
                    : 'idle';
                setCre8PilotAvatarState(fallback, w);
            }
            if (typeof options.onEnd === 'function') {
                options.onEnd();
            }
        };
        utterance.onerror = (event) => {
            const w = options.cre8PilotWidget || (options.messageItem && options.messageItem.closest('[data-cre8pilot-widget]')) || cre8PilotResolveWidget(null);
            const code = String(event && event.error ? event.error : '').toLowerCase();
            const fallback = w && w.cre8PilotLastResponseAvatarState && w.cre8PilotLastResponseAvatarState !== 'ai_speaking'
                ? w.cre8PilotLastResponseAvatarState
                : 'idle';
            stopCre8PilotSpeech();
            if (w) {
                setCre8PilotAvatarState(code === 'canceled' || code === 'interrupted' ? fallback : 'confused', w);
            }
            if (typeof options.onEnd === 'function') {
                options.onEnd();
            }
        };
        window.speechSynthesis.speak(utterance);
    }

    function speakCre8PilotMessage(messageItem, button, text) {
        const w = messageItem ? messageItem.closest('[data-cre8pilot-widget]') : null;
        speakCre8PilotText(text, {
            messageItem,
            button,
            cre8PilotWidget: w,
            onUnsupported: () => {
                button.title = 'Voice output is not supported in this browser.';
            },
        });
    }

    function clearCre8PilotSilenceTimer(widget) {
        if (widget.cre8PilotSilenceTimer) {
            window.clearTimeout(widget.cre8PilotSilenceTimer);
            widget.cre8PilotSilenceTimer = null;
        }
    }

    function scheduleCre8PilotSilenceTimer(widget, input, voiceStatusEl, micButton) {
        clearCre8PilotSilenceTimer(widget);
        widget.cre8PilotSilenceTimer = window.setTimeout(() => {
            if (!widget.cre8PilotListening) {
                return;
            }
            widget.cre8PilotSilenceStop = true;
            stopCre8PilotListening(widget, input, voiceStatusEl, micButton, 'Listening stopped after silence.');
        }, 20000);
    }

    function setCre8PilotMicState(widget, micButton, listening) {
        widget.classList.toggle('is-listening', listening);
        if (!micButton) {
            return;
        }
        micButton.classList.toggle('is-listening', listening);
        micButton.setAttribute('aria-pressed', listening ? 'true' : 'false');
        micButton.title = listening ? 'Stop listening' : 'Speak to Cre8Pilot';
        micButton.setAttribute('aria-label', listening ? 'Stop listening' : 'Speak to Cre8Pilot');
        micButton.textContent = listening ? '\u25A0' : '\uD83C\uDFA4';
    }

    function stopCre8PilotListening(widget, input, voiceStatusEl, micButton, statusMessage = 'Voice captured. Review and click Send.') {
        widget.cre8PilotListening = false;
        clearCre8PilotSilenceTimer(widget);
        setCre8PilotMicState(widget, micButton || widget.querySelector('[data-cre8pilot-mic]'), false);
        if (widget.cre8PilotRecognition) {
            try {
                widget.cre8PilotRecognition.stop();
            } catch (error) {
                // Recognition may already be stopped by the browser.
            }
        }
        if (input) {
            input.focus();
        }
        if (statusMessage) {
            setVoiceStatus(widget, statusMessage);
        } else {
            setVoiceStatus(widget, '');
        }
        setCre8PilotAvatarState('idle', widget);
    }

    function startCre8PilotListening(widget, input, messages, voiceStatusEl, micButton) {
        const isEdge = isCre8PilotEdgeBrowser();
        if (!isSpeechRecognitionSupported()) {
            setVoiceStatus(widget, 'Voice input is not supported in this browser. Please use Chrome, or type your message manually.');
            if (micButton) {
                micButton.disabled = true;
            }
            return;
        }

        if (widget.cre8PilotListening) {
            stopCre8PilotListening(widget, input, voiceStatusEl, micButton, 'Voice captured. Review and click Send.');
            return;
        }

        const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new Recognition();
        widget.cre8PilotRecognition = recognition;
        widget.cre8PilotListening = true;
        widget.cre8PilotAutoRestarts = 0;
        widget.cre8PilotLastFinalSegment = '';
        widget.cre8PilotSilenceStop = false;
        recognition.lang = 'en-US';
        recognition.interimResults = true;
        recognition.continuous = true;
        widget.cre8PilotVoiceErrorHandled = false;

        recognition.onstart = () => {
            setCre8PilotMicState(widget, micButton, true);
            setVoiceStatus(widget, 'Listening… speak now');
            setCre8PilotAvatarState('listening', widget);
            scheduleCre8PilotSilenceTimer(widget, input, voiceStatusEl, micButton);
        };

        recognition.onresult = (event) => {
            let finalText = '';
            let interimText = '';
            for (let index = event.resultIndex; index < event.results.length; index++) {
                const transcript = event.results[index][0] ? event.results[index][0].transcript : '';
                if (event.results[index].isFinal) {
                    finalText += transcript;
                } else {
                    interimText += transcript;
                }
            }

            if (interimText.trim()) {
                const heard = 'Heard: "' + interimText.trim() + '"';
                setVoiceStatus(widget, heard);
                setCre8PilotAvatarState('user_speaking', widget);
                scheduleCre8PilotSilenceTimer(widget, input, voiceStatusEl, micButton);
            }

            if (finalText.trim()) {
                const cleanFinal = finalText.trim();
                if (cleanFinal !== widget.cre8PilotLastFinalSegment) {
                    insertVoiceTextIntoTextarea(input, cleanFinal);
                    widget.cre8PilotLastFinalSegment = cleanFinal;
                }
                setVoiceStatus(widget, 'Voice captured. Review and click Send.');
                setCre8PilotAvatarState('success', widget);
                scheduleCre8PilotSilenceTimer(widget, input, voiceStatusEl, micButton);
                // Future option: auto-send after voice recognition, disabled by design for safety.
            }
        };

        recognition.onerror = (event) => {
            const code = String(event.error || '');
            if (!widget.cre8PilotListening && code === 'aborted') {
                return;
            }
            const message = code === 'network' && isEdge
                ? 'Voice recognition is not available in Edge right now. You can use Chrome for voice input, or type your message manually.'
                : (code === 'network'
                    ? 'Voice recognition service is not available right now. You can still type your message manually.'
                    : (code === 'no-speech'
                ? 'I did not hear anything. Please try again.'
                : (code === 'not-allowed'
                    ? 'Microphone permission was blocked.'
                    : 'Voice recognition failed. Please try again.')));

            if (!widget.cre8PilotVoiceErrorHandled) {
                setVoiceStatus(widget, message);
                widget.cre8PilotVoiceErrorHandled = true;
            }

            if (isEdge && ['network', 'not-allowed', 'audio-capture'].includes(code) && !widget.cre8PilotEdgeVoiceHintShown) {
                setVoiceStatus(widget, message + ' Edge checklist: allow microphone permission for localhost, check Windows microphone privacy settings, and make sure Edge speech recognition is enabled by your browser policy.');
                widget.cre8PilotEdgeVoiceHintShown = true;
            }

            widget.cre8PilotListening = false;
            clearCre8PilotSilenceTimer(widget);
            setCre8PilotMicState(widget, micButton, false);
            setCre8PilotAvatarState('error', widget);

            if (isEdge && code === 'network' && micButton) {
                try {
                    recognition.stop();
                } catch (error) {
                    // Edge may already have stopped recognition after the network error.
                }
                micButton.disabled = true;
                window.setTimeout(() => {
                    micButton.disabled = false;
                }, 5000);
            }
        };

        recognition.onend = () => {
            if (widget.cre8PilotListening && !widget.cre8PilotSilenceStop && widget.cre8PilotAutoRestarts < 2) {
                widget.cre8PilotAutoRestarts += 1;
                window.setTimeout(() => {
                    if (widget.cre8PilotListening) {
                        try {
                            recognition.start();
                        } catch (error) {
                            stopCre8PilotListening(widget, input, voiceStatusEl, micButton, 'Voice recognition stopped. You can try again or type manually.');
                        }
                    }
                }, 400);
                return;
            }

            if (widget.cre8PilotListening) {
                stopCre8PilotListening(
                    widget,
                    input,
                    voiceStatusEl,
                    micButton,
                    widget.cre8PilotSilenceStop ? 'Listening stopped after silence.' : 'Voice captured. Review and click Send.'
                );
            } else {
                clearCre8PilotSilenceTimer(widget);
                setCre8PilotMicState(widget, micButton, false);
                setCre8PilotAvatarState('idle', widget);
                input.focus();
            }
        };

        try {
            recognition.start();
        } catch (error) {
            widget.cre8PilotListening = false;
            clearCre8PilotSilenceTimer(widget);
            setCre8PilotMicState(widget, micButton, false);
            setVoiceStatus(widget, 'Voice recognition failed. Please try again.');
            setCre8PilotAvatarState('error', widget);
        }
    }

    function initCre8PilotVoice(widget, input, messages, voiceStatusEl) {
        const micButton = widget.querySelector('[data-cre8pilot-mic]');

        if (micButton) {
            if (!isSpeechRecognitionSupported()) {
                micButton.disabled = true;
                micButton.title = 'Voice input is not supported in this browser. Please use Chrome, or type your message manually.';
            }
            micButton.addEventListener('click', () => startCre8PilotListening(widget, input, messages, voiceStatusEl, micButton));
        }
    }

    function getCre8PilotVoiceModeElements(widget) {
        return {
            overlay: widget.querySelector('[data-cre8pilot-voice-overlay]'),
            circle: widget.querySelector('[data-cre8pilot-voice-circle]'),
            subtitle: widget.querySelector('[data-cre8pilot-voice-subtitle]'),
            captured: widget.querySelector('[data-cre8pilot-voice-captured]'),
            toggle: widget.querySelector('[data-cre8pilot-voice-toggle]'),
            send: widget.querySelector('[data-cre8pilot-voice-send]'),
            clear: widget.querySelector('[data-cre8pilot-voice-clear]'),
            stopSpeaking: widget.querySelector('[data-cre8pilot-voice-stop-speaking]'),
            close: widget.querySelector('[data-cre8pilot-voice-close]'),
            exit: widget.querySelector('[data-cre8pilot-voice-exit]'),
        };
    }

    function setCre8PilotVoiceModeState(widget, stateName, subtitle = '') {
        const elements = getCre8PilotVoiceModeElements(widget);
        const stateClass = 'cre8pilot-voice-circle--' + (stateName === 'ai_speaking' ? 'speaking' : stateName);
        const allStates = ['idle', 'listening', 'user_speaking', 'captured', 'thinking', 'speaking', 'error'];
        widget.cre8PilotVoiceModeState = stateName;

        if (elements.overlay) {
            elements.overlay.dataset.voiceState = stateName;
        }
        if (elements.circle) {
            elements.circle.className = 'cre8pilot-voice-circle ' + stateClass;
            allStates.forEach((name) => elements.circle.classList.remove('cre8pilot-voice-circle--' + name));
            elements.circle.classList.add(stateClass);
        }
        if (elements.subtitle && subtitle) {
            elements.subtitle.textContent = subtitle;
        }
        if (elements.toggle) {
            elements.toggle.textContent = widget.cre8PilotVoiceModeListening ? 'Stop voice' : 'Start voice';
        }
        cre8PilotSyncAvatarsFromVoiceMode(widget, stateName);
    }

    function updateCre8PilotVoiceCaptured(widget, text) {
        const elements = getCre8PilotVoiceModeElements(widget);
        const cleanText = String(text || '').replace(/\s+/g, ' ').trim();
        widget.cre8PilotVoiceModeCapturedText = cleanText;
        if (elements.captured) {
            elements.captured.hidden = cleanText === '';
            elements.captured.textContent = cleanText ? 'Captured: "' + cleanText + '"' : '';
        }
        if (elements.send) {
            elements.send.disabled = cleanText === '';
        }
        if (elements.clear) {
            elements.clear.disabled = cleanText === '';
        }
    }

    function appendCre8PilotVoiceCaptured(widget, text) {
        const cleanText = String(text || '').replace(/\s+/g, ' ').trim();
        if (!cleanText || cleanText === widget.cre8PilotVoiceModeLastFinal) {
            return;
        }
        const current = String(widget.cre8PilotVoiceModeCapturedText || '').trim();
        widget.cre8PilotVoiceModeLastFinal = cleanText;
        updateCre8PilotVoiceCaptured(widget, current ? current + ' ' + cleanText : cleanText);
    }

    function stopCre8PilotVoiceAnalyser(widget) {
        if (widget.cre8PilotVoiceAnalyserFrame) {
            window.cancelAnimationFrame(widget.cre8PilotVoiceAnalyserFrame);
            widget.cre8PilotVoiceAnalyserFrame = null;
        }
        if (widget.cre8PilotVoiceStream) {
            widget.cre8PilotVoiceStream.getTracks().forEach((track) => track.stop());
            widget.cre8PilotVoiceStream = null;
        }
        if (widget.cre8PilotVoiceAudioContext) {
            widget.cre8PilotVoiceAudioContext.close().catch(() => {});
            widget.cre8PilotVoiceAudioContext = null;
        }
        const elements = getCre8PilotVoiceModeElements(widget);
        if (elements.circle) {
            elements.circle.style.setProperty('--voice-level', '0');
            elements.circle.style.setProperty('--voice-scale', '1');
            elements.circle.style.setProperty('--voice-glow', '34px');
        }
        const voiceAvatar = widget.querySelector('[data-cre8pilot-avatar-voice]');
        if (voiceAvatar) {
            voiceAvatar.style.setProperty('--avatar-voice-level', '0');
            voiceAvatar.style.setProperty('--voice-level', '0');
        }
    }

    async function startCre8PilotVoiceAnalyser(widget) {
        const elements = getCre8PilotVoiceModeElements(widget);
        if (!elements.circle || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return;
        }

        try {
            stopCre8PilotVoiceAnalyser(widget);
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextClass) {
                stream.getTracks().forEach((track) => track.stop());
                return;
            }
            const audioContext = new AudioContextClass();
            const analyser = audioContext.createAnalyser();
            analyser.fftSize = 256;
            const source = audioContext.createMediaStreamSource(stream);
            source.connect(analyser);
            const data = new Uint8Array(analyser.frequencyBinCount);

            widget.cre8PilotVoiceStream = stream;
            widget.cre8PilotVoiceAudioContext = audioContext;
            widget.cre8PilotVoiceAnalyser = analyser;

            const tick = () => {
                if (!widget.cre8PilotVoiceModeListening || !widget.cre8PilotVoiceAnalyser) {
                    return;
                }
                analyser.getByteTimeDomainData(data);
                let sum = 0;
                data.forEach((value) => {
                    const normalized = (value - 128) / 128;
                    sum += normalized * normalized;
                });
                const rms = Math.sqrt(sum / data.length);
                const level = Math.min(1, rms * 5).toFixed(3);
                elements.circle.style.setProperty('--voice-level', level);
                elements.circle.style.setProperty('--voice-scale', (1 + Math.min(0.18, rms * 0.9)).toFixed(3));
                elements.circle.style.setProperty('--voice-glow', Math.round(34 + Math.min(90, rms * 360)) + 'px');
                const voiceAvatar = widget.querySelector('[data-cre8pilot-avatar-voice]');
                if (voiceAvatar && (widget.cre8PilotVoiceModeState === 'listening' || widget.cre8PilotVoiceModeState === 'user_speaking')) {
                    voiceAvatar.style.setProperty('--avatar-voice-level', level);
                    voiceAvatar.style.setProperty('--voice-level', level);
                }
                widget.cre8PilotVoiceAnalyserFrame = window.requestAnimationFrame(tick);
            };
            tick();
        } catch (error) {
            if (elements.circle) {
                elements.circle.style.setProperty('--voice-level', '0');
            }
        }
    }

    function clearCre8PilotVoiceModeSilenceTimer(widget) {
        if (widget.cre8PilotVoiceModeSilenceTimer) {
            window.clearTimeout(widget.cre8PilotVoiceModeSilenceTimer);
            widget.cre8PilotVoiceModeSilenceTimer = null;
        }
    }

    function scheduleCre8PilotVoiceModeSilenceTimer(widget) {
        clearCre8PilotVoiceModeSilenceTimer(widget);
        widget.cre8PilotVoiceModeSilenceTimer = window.setTimeout(() => {
            if (!widget.cre8PilotVoiceModeListening) {
                return;
            }
            widget.cre8PilotVoiceModeSilenceStop = true;
            stopCre8PilotVoiceModeListening(widget, 'Listening stopped after silence.');
        }, 20000);
    }

    function stopCre8PilotVoiceModeListening(widget, message = 'Voice captured. Review and click Send.') {
        widget.cre8PilotVoiceModeListening = false;
        clearCre8PilotVoiceModeSilenceTimer(widget);
        stopCre8PilotVoiceAnalyser(widget);
        const elements = getCre8PilotVoiceModeElements(widget);
        if (widget.cre8PilotVoiceModeRecognition) {
            try {
                widget.cre8PilotVoiceModeRecognition.stop();
            } catch (error) {
                // Recognition may already be stopped by the browser.
            }
        }
        setCre8PilotVoiceModeState(widget, widget.cre8PilotVoiceModeCapturedText ? 'captured' : 'idle', message);
        if (elements.toggle) {
            elements.toggle.textContent = 'Start voice';
        }
    }

    function startCre8PilotVoiceModeListening(widget) {
        const elements = getCre8PilotVoiceModeElements(widget);
        const isEdge = isCre8PilotEdgeBrowser();
        if (!isSpeechRecognitionSupported()) {
            setCre8PilotVoiceModeState(widget, 'error', 'Voice input is not supported in this browser. Please use Chrome, or type your message manually.');
            return;
        }

        if (widget.cre8PilotVoiceModeListening) {
            stopCre8PilotVoiceModeListening(widget);
            return;
        }

        const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new Recognition();
        widget.cre8PilotVoiceModeRecognition = recognition;
        widget.cre8PilotVoiceModeListening = true;
        widget.cre8PilotVoiceModeAutoRestarts = 0;
        widget.cre8PilotVoiceModeSilenceStop = false;
        widget.cre8PilotVoiceModeLastFinal = '';
        widget.cre8PilotVoiceModeErrorHandled = false;
        recognition.lang = 'en-US';
        recognition.interimResults = true;
        recognition.continuous = true;

        recognition.onstart = () => {
            setCre8PilotVoiceModeState(widget, 'listening', 'Listening... speak now');
            scheduleCre8PilotVoiceModeSilenceTimer(widget);
            startCre8PilotVoiceAnalyser(widget);
        };

        recognition.onresult = (event) => {
            let finalText = '';
            let interimText = '';
            for (let index = event.resultIndex; index < event.results.length; index++) {
                const transcript = event.results[index][0] ? event.results[index][0].transcript : '';
                if (event.results[index].isFinal) {
                    finalText += transcript;
                } else {
                    interimText += transcript;
                }
            }

            if (interimText.trim()) {
                setCre8PilotVoiceModeState(widget, 'user_speaking', 'You: "' + interimText.trim() + '"');
                scheduleCre8PilotVoiceModeSilenceTimer(widget);
            }

            if (finalText.trim()) {
                appendCre8PilotVoiceCaptured(widget, finalText);
                setCre8PilotVoiceModeState(widget, 'captured', 'Captured: "' + widget.cre8PilotVoiceModeCapturedText + '"');
                scheduleCre8PilotVoiceModeSilenceTimer(widget);
                // Future option: auto-send after confirmation, disabled by design for safety.
            }
        };

        recognition.onerror = (event) => {
            const code = String(event.error || '');
            if (!widget.cre8PilotVoiceModeListening && code === 'aborted') {
                return;
            }
            const message = code === 'network' && isEdge
                ? 'Voice recognition is not available in Edge right now. You can use Chrome for voice input, or type your message manually.'
                : (code === 'network'
                    ? 'Voice recognition service is not available right now. You can still type your message manually.'
                    : (code === 'no-speech'
                        ? 'I did not hear anything. Please try again.'
                        : (code === 'not-allowed'
                            ? 'Microphone permission was blocked.'
                            : 'Voice recognition failed. Please try again.')));
            const edgeHint = isEdge && ['network', 'not-allowed', 'audio-capture'].includes(code) && !widget.cre8PilotVoiceModeEdgeHintShown
                ? ' Edge checklist: allow microphone permission for localhost, check Windows microphone privacy settings, and make sure Edge speech recognition is enabled by your browser policy.'
                : '';
            widget.cre8PilotVoiceModeEdgeHintShown = widget.cre8PilotVoiceModeEdgeHintShown || edgeHint !== '';
            widget.cre8PilotVoiceModeListening = false;
            clearCre8PilotVoiceModeSilenceTimer(widget);
            stopCre8PilotVoiceAnalyser(widget);
            setCre8PilotVoiceModeState(widget, 'error', message + edgeHint);
            if (isEdge && code === 'network' && elements.toggle) {
                elements.toggle.disabled = true;
                window.setTimeout(() => {
                    elements.toggle.disabled = false;
                }, 5000);
            }
        };

        recognition.onend = () => {
            if (widget.cre8PilotVoiceModeListening && !widget.cre8PilotVoiceModeSilenceStop && widget.cre8PilotVoiceModeAutoRestarts < 2) {
                widget.cre8PilotVoiceModeAutoRestarts += 1;
                window.setTimeout(() => {
                    if (widget.cre8PilotVoiceModeListening) {
                        try {
                            recognition.start();
                        } catch (error) {
                            stopCre8PilotVoiceModeListening(widget, 'Voice recognition stopped. You can try again or type manually.');
                        }
                    }
                }, 400);
                return;
            }

            if (widget.cre8PilotVoiceModeListening) {
                stopCre8PilotVoiceModeListening(
                    widget,
                    widget.cre8PilotVoiceModeSilenceStop ? 'Listening stopped after silence.' : 'Voice captured. Review and click Send.'
                );
            } else {
                clearCre8PilotVoiceModeSilenceTimer(widget);
                stopCre8PilotVoiceAnalyser(widget);
                if (elements.toggle) {
                    elements.toggle.textContent = 'Start voice';
                }
            }
        };

        try {
            recognition.start();
        } catch (error) {
            widget.cre8PilotVoiceModeListening = false;
            clearCre8PilotVoiceModeSilenceTimer(widget);
            stopCre8PilotVoiceAnalyser(widget);
            setCre8PilotVoiceModeState(widget, 'error', 'Voice recognition failed. Please try again.');
        }
    }

    function closeCre8PilotVoiceMode(widget) {
        const elements = getCre8PilotVoiceModeElements(widget);
        stopCre8PilotVoiceModeListening(widget, '');
        stopCre8PilotSpeech();
        stopCre8PilotVoiceAnalyser(widget);
        if (elements.overlay) {
            elements.overlay.hidden = true;
        }
        setCre8PilotAvatarState('idle', widget);
    }

    function initCre8PilotVoiceMode(widget, submitPrompt) {
        const elements = getCre8PilotVoiceModeElements(widget);
        if (!elements.overlay) {
            return;
        }

        [elements.close, elements.exit].forEach((button) => {
            if (button) {
                button.addEventListener('click', () => closeCre8PilotVoiceMode(widget));
            }
        });
        if (elements.toggle) {
            elements.toggle.addEventListener('click', () => startCre8PilotVoiceModeListening(widget));
        }
        if (elements.clear) {
            elements.clear.addEventListener('click', () => {
                updateCre8PilotVoiceCaptured(widget, '');
                setCre8PilotVoiceModeState(widget, 'idle', 'Tap the mic and speak.');
            });
        }
        if (elements.stopSpeaking) {
            elements.stopSpeaking.addEventListener('click', () => {
                stopCre8PilotSpeech();
                setCre8PilotVoiceModeState(widget, widget.cre8PilotVoiceModeCapturedText ? 'captured' : 'idle', widget.cre8PilotVoiceModeCapturedText ? 'Captured: "' + widget.cre8PilotVoiceModeCapturedText + '"' : 'Tap the mic and speak.');
            });
        }
        if (elements.send) {
            elements.send.addEventListener('click', () => {
                const prompt = String(widget.cre8PilotVoiceModeCapturedText || '').trim();
                if (!prompt) {
                    return;
                }
                stopCre8PilotVoiceModeListening(widget, '');
                updateCre8PilotVoiceCaptured(widget, '');
                setCre8PilotVoiceModeState(widget, 'thinking', 'Cre8Pilot is thinking...');
                submitPrompt(prompt, '', {
                    onAssistantResponse: (data, assistantText) => {
                        const responseText = String(assistantText || data.message || '').trim();
                        if (!responseText) {
                            setCre8PilotVoiceModeState(widget, 'idle', 'Tap the mic and speak.');
                            return;
                        }
                        setCre8PilotVoiceModeState(widget, 'ai_speaking', responseText);
                        speakCre8PilotText(responseText, {
                            cre8PilotWidget: widget,
                            onStart: () => setCre8PilotVoiceModeState(widget, 'ai_speaking', responseText),
                            onEnd: () => setCre8PilotVoiceModeState(widget, 'idle', 'Tap the mic and speak.'),
                            onUnsupported: () => setCre8PilotVoiceModeState(widget, 'captured', responseText),
                        });
                    },
                    onError: () => setCre8PilotVoiceModeState(widget, 'error', 'Cre8Pilot could not respond right now. You can try again or type manually.'),
                });
            });
        }
    }

    document.querySelectorAll('[data-cre8pilot-widget]').forEach((widget) => {
        if (widget.dataset.cre8pilotReady === '1') {
            return;
        }
        widget.dataset.cre8pilotReady = '1';
        widget.cre8PilotBubbleHoverHold = false;
        cre8PilotPrepareBubbleImages(widget);
        widget.querySelectorAll('[data-cre8pilot-avatar]').forEach((avatarEl) => {
            avatarEl.addEventListener('mouseenter', () => {
                widget.cre8PilotBubbleHoverHold = true;
                cre8PilotClearBubbleHideTimer(widget);
                const st = widget.cre8PilotAvatarState || 'idle';
                const kind = cre8PilotBubbleKindForAvatarState(st);
                if (kind) {
                    cre8PilotShowAvatarBubbleKind(widget, kind);
                }
            });
            avatarEl.addEventListener('mouseleave', () => {
                widget.cre8PilotBubbleHoverHold = false;
                const kind = cre8PilotBubbleKindForAvatarState(widget.cre8PilotAvatarState || 'idle');
                if (kind) {
                    cre8PilotScheduleBubbleAutoHide(widget);
                } else {
                    cre8PilotHideAvatarBubblesInWidget(widget);
                    widget.cre8PilotActiveBubbleKind = null;
                }
            });
        });

        const toggle = widget.querySelector('[data-cre8pilot-toggle]');
        const panel = widget.querySelector('[data-cre8pilot-panel]');
        const closeButton = widget.querySelector('[data-cre8pilot-close]');
        const form = widget.querySelector('[data-cre8pilot-form]');
        const input = widget.querySelector('[data-cre8pilot-input]');
        const messages = widget.querySelector('[data-cre8pilot-messages]');
        const modeBadge = widget.querySelector('[data-cre8pilot-state]');
        const voiceStatus = widget.querySelector('[data-cre8pilot-voice-status]');
        const quickActions = widget.querySelector('[data-cre8pilot-quick-actions]');
        const endpoint = widget.dataset.cre8pilotEndpoint || '';
        const attachPanel = widget.querySelector('[data-cre8pilot-attach-panel]');
        const fileInput = widget.querySelector('[data-cre8pilot-file]');
        const attachPick = widget.querySelector('[data-cre8pilot-attach-pick]');
        const attachUpload = widget.querySelector('[data-cre8pilot-attach-upload]');
        const attachCancel = widget.querySelector('[data-cre8pilot-attach-cancel]');
        const attachFilename = widget.querySelector('[data-cre8pilot-attach-filename]');
        const docLabelInput = widget.querySelector('[data-cre8pilot-doc-label]');
        const attachStatus = widget.querySelector('[data-cre8pilot-attach-status]');
        const toolsRoot = widget.querySelector('[data-cre8pilot-tools]');
        const toolsToggle = widget.querySelector('[data-cre8pilot-tools-toggle]');
        const toolsMenu = widget.querySelector('[data-cre8pilot-tools-menu]');
        const toolsOverflowWrap = widget.querySelector('[data-cre8pilot-tools-overflow-wrap]');
        const toolsOverflow = widget.querySelector('[data-cre8pilot-tools-overflow]');
        initCre8PilotVoice(widget, input, messages, voiceStatus);

        function closeCre8PilotToolsMenu() {
            if (!toolsMenu || !toolsToggle) {
                return;
            }
            toolsMenu.hidden = true;
            toolsToggle.setAttribute('aria-expanded', 'false');
            widget.cre8PilotToolsOpen = false;
        }

        function openCre8PilotToolsMenu() {
            if (!toolsMenu || !toolsToggle) {
                return;
            }
            toolsMenu.hidden = false;
            toolsToggle.setAttribute('aria-expanded', 'true');
            widget.cre8PilotToolsOpen = true;
        }

        function toggleCre8PilotToolsMenu() {
            if (widget.cre8PilotToolsOpen) {
                closeCre8PilotToolsMenu();
            } else {
                openCre8PilotToolsMenu();
            }
        }

        function hideCre8PilotAttachPanel() {
            widget.cre8PilotPendingFile = null;
            if (fileInput) {
                fileInput.value = '';
            }
            if (attachUpload) {
                attachUpload.disabled = true;
            }
            if (attachFilename) {
                attachFilename.textContent = 'No file selected';
            }
            if (attachPanel) {
                attachPanel.hidden = true;
            }
        }

        function showCre8PilotAttachPanel() {
            if (attachPanel) {
                attachPanel.hidden = false;
            }
        }

        function uploadCre8PilotDocument(file) {
            if (!file || !endpoint) {
                return;
            }
            setCre8PilotAvatarState('thinking', widget);
            if (attachStatus) {
                attachStatus.textContent = 'Extracting document...';
            }
            const fd = new FormData();
            fd.append('action', 'document_upload');
            fd.append('file', file);
            fd.append('label', docLabelInput ? String(docLabelInput.value || '').trim() : '');
            const ctx = window.CRE8PILOT_CONTEXT || {};
            fd.append('page', ctx.page || 'unknown');
            fd.append('mode', ctx.mode || '');
            fd.append('role', ctx.role || '');
            fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (attachStatus) {
                        attachStatus.textContent = '';
                    }
                    if (data && data.debug && window.console && typeof window.console.log === 'function') {
                        window.console.log('[Cre8Pilot document]', data.debug);
                    }
                    const assistantText = (data && data.message) ? data.message : 'Document upload finished.';
                    const st = (data && data.status) ? data.status : 'ok';
                    appendMessage(messages, assistantText, 'assistant', st === 'error' ? 'error' : 'ok');
                    if (data && data.needsUserConfirmation) {
                        appendMessage(messages, 'Please review the extracted content. I will not submit or save anything automatically.', 'assistant', 'action');
                    }
                    if (data) {
                        updateCre8PilotAvatarFromResponse(data, widget);
                    }
                    if (modeBadge) {
                        modeBadge.textContent = 'Mock mode';
                    }
                    hideCre8PilotAttachPanel();
                })
                .catch(() => {
                    if (attachStatus) {
                        attachStatus.textContent = '';
                    }
                    appendMessage(messages, 'Document upload could not reach the server.', 'assistant', 'error');
                    if (modeBadge) {
                        modeBadge.textContent = 'Mock mode';
                    }
                    setCre8PilotAvatarState('error', widget);
                });
        }

        if (attachPick && fileInput) {
            attachPick.addEventListener('click', () => {
                fileInput.click();
            });
        }
        if (fileInput) {
            fileInput.addEventListener('change', () => {
                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                widget.cre8PilotPendingFile = file || null;
                if (attachFilename) {
                    attachFilename.textContent = file ? file.name : 'No file selected';
                }
                if (attachUpload) {
                    attachUpload.disabled = !file;
                }
            });
        }
        if (attachUpload) {
            attachUpload.addEventListener('click', () => {
                const file = widget.cre8PilotPendingFile || (fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null);
                if (file) {
                    uploadCre8PilotDocument(file);
                }
            });
        }
        if (attachCancel) {
            attachCancel.addEventListener('click', () => {
                hideCre8PilotAttachPanel();
            });
        }

        if (toolsToggle && toolsMenu) {
            toolsToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                toggleCre8PilotToolsMenu();
            });
            toolsMenu.addEventListener('click', (event) => {
                event.stopPropagation();
            });
            document.addEventListener('click', () => {
                if (widget.cre8PilotToolsOpen) {
                    closeCre8PilotToolsMenu();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && widget.cre8PilotToolsOpen) {
                    closeCre8PilotToolsMenu();
                }
            });
        }

        function clearCre8PilotPanelHistory() {
            if (!messages) {
                return;
            }
            messages.innerHTML = '';
            appendMessage(messages, CRE8PILOT_WELCOME_TEXT, 'assistant');
            messages.scrollTop = 0;
        }

        function bindToolMenuActions() {
            if (!toolsMenu) {
                return;
            }
            toolsMenu.querySelectorAll('[data-cre8pilot-tool]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const tool = btn.getAttribute('data-cre8pilot-tool') || '';
                    closeCre8PilotToolsMenu();
                    if (tool === 'attach') {
                        showCre8PilotAttachPanel();
                        return;
                    }
                    if (tool === 'voice') {
                        setOpen(true);
                        const elements = getCre8PilotVoiceModeElements(widget);
                        if (elements.overlay) {
                            elements.overlay.hidden = false;
                            setCre8PilotVoiceModeState(widget, 'idle', 'Tap the mic and speak.');
                        }
                        return;
                    }
                    if (tool === 'security') {
                        submitPrompt('Check security', 'security_check');
                        return;
                    }
                    if (tool === 'summarize') {
                        submitPrompt('Summarize this page', 'summarize_page');
                        return;
                    }
                    if (tool === 'clear') {
                        clearCre8PilotPanelHistory();
                    }
                });
            });
        }

        function setOpen(open) {
            panel.hidden = !open;
            widget.classList.toggle('is-open', open);
            if (open) {
                input.focus();
                if (typeof widget.cre8PilotResizeInput === 'function') {
                    widget.cre8PilotResizeInput();
                }
            }
        }

        toggle.addEventListener('click', () => setOpen(panel.hidden));
        closeButton.addEventListener('click', () => setOpen(false));

        function submitPrompt(rawPrompt, selectedClarificationId = '', options = {}) {
            const prompt = String(rawPrompt || '').trim();
            if (prompt === '') {
                return Promise.resolve(null);
            }

            appendMessage(messages, prompt, 'user');
            if (input.value.trim() === prompt) {
                input.value = '';
            }
            if (typeof widget.cre8PilotResizeInput === 'function') {
                widget.cre8PilotResizeInput();
            }
            setComposerActivity(widget, 'Thinking…');
            setCre8PilotAvatarThinking(widget);

            // UX-only minimum visible response time. Avoids the "instant" feel
            // when the backend answers in <100ms. Backend logic itself is never
            // slowed; we just hold the thinking animation for a moment.
            // High-risk security blocks bypass most of this delay so safety
            // feedback still feels snappy.
            const cre8pilotMinMs = (typeof window.CRE8PILOT_MIN_RESPONSE_MS === 'number'
                && window.CRE8PILOT_MIN_RESPONSE_MS >= 0
                && window.CRE8PILOT_MIN_RESPONSE_MS <= 5000)
                ? window.CRE8PILOT_MIN_RESPONSE_MS
                : 1200;
            const cre8pilotStartedAt = (typeof performance !== 'undefined' && performance.now)
                ? performance.now()
                : Date.now();

            function cre8pilotEnforceMinDelay(data) {
                const nowMs = (typeof performance !== 'undefined' && performance.now)
                    ? performance.now()
                    : Date.now();
                const elapsed = nowMs - cre8pilotStartedAt;
                const lvl = data && data.security && data.security.riskLevel
                    ? String(data.security.riskLevel).toLowerCase()
                    : '';
                const status = data && data.status ? String(data.status).toLowerCase() : '';
                const isUrgentSecurity = lvl === 'high' || status === 'blocked';
                const target = isUrgentSecurity ? Math.min(cre8pilotMinMs, 350) : cre8pilotMinMs;
                const wait = Math.max(0, target - elapsed);
                if (wait <= 0) {
                    return Promise.resolve(data);
                }
                return new Promise((resolve) => {
                    window.setTimeout(() => resolve(data), wait);
                });
            }

            return fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(Object.assign({}, window.CRE8PILOT_CONTEXT || {}, {
                    message: prompt,
                    selectedClarificationId,
                    visibleData: collectVisibleData(),
                })),
            })
                .then((response) => response.json())
                .then((data) => cre8pilotEnforceMinDelay(data))
                .then((data) => {
                    if (data && data.debug && window.console && typeof window.console.log === 'function') {
                        window.console.log('[Cre8Pilot mock]', data.debug);
                    }
                    const status = data.status || 'ok';
                    const assistantText = data.message || 'Cre8Pilot returned an empty response.';
                    appendMessage(messages, assistantText, 'assistant', status);
                    if (data.security && typeof data.security === 'object') {
                        appendCre8PilotSecurityCard(messages, data.security);
                    }
                    if (data.matchModel && typeof data.matchModel === 'object' && Array.isArray(data.matchModel.topRecommendations) && data.matchModel.topRecommendations.length > 0) {
                        appendCre8PilotMatchModelCard(messages, data.matchModel);
                    }
                    if (data.needsUserConfirmation) {
                        appendMessage(messages, 'Please review the prepared content. I will not submit or save anything automatically.', 'assistant', 'action');
                    }
                    if (data.clarification && Array.isArray(data.clarification.options)) {
                        appendClarificationOptions(messages, data.clarification.options, submitPrompt);
                    }
                    updateCre8PilotAvatarFromResponse(data, widget);
                    if (Array.isArray(data.actions)) {
                        data.actions.forEach((action) => handleAction(action, messages, widget));
                    }
                    if (!data.actions || data.actions.length === 0) {
                        window.setTimeout(() => {
                            if (widget && widget.cre8PilotAvatarState === 'success') {
                                setCre8PilotAvatarState('idle', widget);
                            }
                        }, 3200);
                    }
                    setComposerActivity(widget, '');
                    if (modeBadge) {
                        modeBadge.textContent = 'Mock mode';
                    }
                    if (typeof options.onAssistantResponse === 'function') {
                        options.onAssistantResponse(data, assistantText);
                    }
                    return data;
                })
                .catch((error) => {
                    return cre8pilotEnforceMinDelay({ status: 'error' }).then(() => {
                        appendMessage(messages, 'Cre8Pilot could not reach the mock endpoint right now.', 'assistant', 'error');
                        setComposerActivity(widget, '');
                        if (modeBadge) {
                            modeBadge.textContent = 'Mock mode';
                        }
                        setCre8PilotAvatarState('error', widget);
                        if (typeof options.onError === 'function') {
                            options.onError(error);
                        }
                        return {
                            status: 'error',
                            intent: 'request_error',
                            message: 'Cre8Pilot could not reach the mock endpoint right now.',
                        };
                    });
                });
        }

        initCre8PilotVoiceMode(widget, submitPrompt);

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitPrompt(input.value);
        });

        if (quickActions) {
            const page = (window.CRE8PILOT_CONTEXT && window.CRE8PILOT_CONTEXT.page) || 'unknown';
            const mode = (window.CRE8PILOT_CONTEXT && window.CRE8PILOT_CONTEXT.mode) || '';
            const allQuick = quickActionsForContext(page, mode);
            const visibleQuick = allQuick.slice(0, 3);
            const overflowQuick = allQuick.slice(3);
            visibleQuick.forEach(([label, id]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'cre8pilot-quick-action-btn';
                button.textContent = label;
                button.addEventListener('click', () => submitPrompt(label, id));
                quickActions.appendChild(button);
            });
            if (toolsOverflow && toolsOverflowWrap && overflowQuick.length > 0) {
                toolsOverflowWrap.hidden = false;
                overflowQuick.forEach(([label, id]) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'cre8pilot-tools-menu-item';
                    button.setAttribute('role', 'menuitem');
                    button.textContent = label;
                    button.addEventListener('click', () => {
                        closeCre8PilotToolsMenu();
                        submitPrompt(label, id);
                    });
                    toolsOverflow.appendChild(button);
                });
            }
        }

        bindToolMenuActions();

        function cre8PilotAutosizeTextarea() {
            if (!input) {
                return;
            }
            input.style.height = 'auto';
            const maxPx = 160;
            const next = Math.min(input.scrollHeight, maxPx);
            input.style.height = next + 'px';
            input.style.overflowY = input.scrollHeight > maxPx ? 'auto' : 'hidden';
        }
        widget.cre8PilotResizeInput = cre8PilotAutosizeTextarea;
        input.addEventListener('input', cre8PilotAutosizeTextarea);
        cre8PilotAutosizeTextarea();

        setCre8PilotAvatarIdle(widget);

        cre8pilotMaybeRunPageScan(widget, messages);
    });

    function cre8pilotCollectVisibleItems() {
        const items = [];
        const seenKeys = new Set();
        const pushItem = (kind, idAttr, labelAttr, textLimit, node, classification) => {
            if (!node || !isProbablyVisible(node)) {
                return;
            }
            const id = idAttr ? String(node.getAttribute(idAttr) || '').trim() : '';
            let label = labelAttr ? String(node.getAttribute(labelAttr) || '').trim() : '';
            if (label === '') {
                const t = node.querySelector('h1, h2, h3, h4, strong, .title, [data-title]');
                if (t) {
                    label = String(t.textContent || '').trim();
                }
            }
            const txt = textOf(node, textLimit || 800);
            if (txt === '' && label === '') {
                return;
            }
            const key = (classification || kind) + '|' + (id || label || txt.slice(0, 60));
            if (seenKeys.has(key)) {
                return;
            }
            seenKeys.add(key);
            items.push({
                item_type: kind,
                source_id: id,
                source_label: label,
                visible_text: txt,
            });
        };

        document.querySelectorAll('[data-offer-id], .offer-card').forEach((node) => {
            pushItem('offer', 'data-offer-id', 'data-offer-title', 600, node);
        });
        document.querySelectorAll('[data-candidature-id], .candidature-card, .candidature-row, .candidature-detail-pill').forEach((node) => {
            pushItem('candidature', 'data-candidature-id', 'data-candidature-title', 700, node);
        });
        document.querySelectorAll('[data-negotiation-id], .negotiation-entry, .negotiation-card').forEach((node) => {
            pushItem('negotiation', 'data-negotiation-id', 'data-negotiation-title', 700, node);
        });
        document.querySelectorAll('[data-message-id], .negotiation-message, .chat-bubble, .message-card').forEach((node) => {
            pushItem('message', 'data-message-id', 'data-message-title', 600, node);
        });
        if (items.length === 0) {
            const main = document.querySelector('main, .admin-shell, .cre8pilot-page-main, body');
            if (main) {
                pushItem('page_text', '', '', 1200, main);
            }
        }
        return items.slice(0, 25);
    }

    function cre8pilotMaybeRunPageScan(widget, messages) {
        if (!widget) {
            return;
        }
        try {
            const ctx = window.CRE8PILOT_CONTEXT || {};
            const role = String(ctx.role || '').toLowerCase();
            if (role === 'guest' || role === '') {
                return;
            }
            const items = cre8pilotCollectVisibleItems();
            if (items.length === 0) {
                return;
            }
            const fingerprint = items.map((it) => (it.item_type || '') + ':' + (it.source_id || '') + ':' + ((it.visible_text || '').length)).join('|');
            const storageKey = 'cre8pilotPageScan:' + window.location.pathname;
            let prev = '';
            try { prev = window.sessionStorage.getItem(storageKey) || ''; } catch (e) { prev = ''; }
            if (prev === fingerprint) {
                return;
            }
            try { window.sessionStorage.setItem(storageKey, fingerprint); } catch (e) {}

            const endpointAttr = widget.getAttribute('data-cre8pilot-endpoint');
            if (!endpointAttr) {
                return;
            }
            const visibleData = collectVisibleData();
            visibleData.visibleItems = items;

            fetch(endpointAttr, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(Object.assign({}, ctx, {
                    intent: 'page_scan',
                    message: '[page_scan]',
                    visibleData: visibleData,
                })),
            })
                .then((r) => r.json())
                .then((data) => {
                    if (!data || !data.security || data.security.pageScan !== true) {
                        return;
                    }
                    const hits = Array.isArray(data.security.hits) ? data.security.hits : [];
                    if (hits.length === 0) {
                        return;
                    }
                    cre8pilotShowPageScanBanner(messages, data.security);
                })
                .catch(() => {});
        } catch (e) {}
    }

    function cre8pilotShowPageScanBanner(messages, security) {
        if (!messages || !security) {
            return;
        }
        const level = String(security.riskLevel || '').toLowerCase();
        const banner = document.createElement('div');
        banner.className = 'cre8pilot-pagescan-banner' + (level === 'high' ? ' is-high' : '');
        const title = document.createElement('strong');
        title.textContent = 'Cre8Shield found suspicious content on this page.';
        banner.appendChild(title);
        const list = document.createElement('ul');
        const hits = Array.isArray(security.hits) ? security.hits.slice(0, 4) : [];
        hits.forEach((hit) => {
            const li = document.createElement('li');
            const cats = Array.isArray(hit.categories) ? hit.categories.slice(0, 4).join(', ') : '';
            const lbl = String(hit.source_label || hit.item_type || 'item');
            li.textContent = lbl + ' — ' + (hit.risk_level || 'medium') + (cats ? ' (' + cats + ')' : '');
            list.appendChild(li);
        });
        banner.appendChild(list);
        messages.appendChild(banner);
        messages.scrollTop = messages.scrollHeight;
    }

    window.cre8pilotOpenExclusiveWindow = cre8pilotOpenExclusiveWindow;
})();
</script>
