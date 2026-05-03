<?php
$cre8PilotContext = isset($cre8PilotContext) && is_array($cre8PilotContext) ? $cre8PilotContext : [];
$cre8PilotPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
$cre8PilotVuePos = strpos($cre8PilotPath, '/Vue/');
$cre8PilotBase = $cre8PilotVuePos !== false ? substr($cre8PilotPath, 0, $cre8PilotVuePos) : '';
$cre8PilotEndpoint = rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/cre8pilot_endpoint.php';
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
<script src="<?php echo htmlspecialchars(rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/cre8pilot_multi_smoke_test.js'); ?>"></script>
<?php $cre8PilotStressTestJs = htmlspecialchars(rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/cre8pilot_full_balanced_stress_test.js'); ?>
<?php $cre8PilotAiTourStressTestJs = htmlspecialchars(rtrim($cre8PilotBase, '/') . '/Vue/FrontOffice/condidature/cre8pilot_ai_tour_stress_test.js'); ?>
<script>
(function () {
    var STRESS_URL = '<?php echo $cre8PilotStressTestJs; ?>';
    var AI_TOUR_STRESS_URL = '<?php echo $cre8PilotAiTourStressTestJs; ?>';
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
        if (typeof window.runCre8PilotAiTourStressTest === 'function') {
            return window.runCre8PilotAiTourStressTest(opts);
        }
        var w2 = document.querySelector('[data-cre8pilot-widget]');
        var ep2 = w2 && w2.dataset && w2.dataset.cre8pilotEndpoint;
        var urlAi = AI_TOUR_STRESS_URL;
        if (ep2 && /cre8pilot_endpoint\.php/i.test(ep2)) {
            urlAi = ep2.replace(/cre8pilot_endpoint\.php/i, 'cre8pilot_ai_tour_stress_test.js');
        }
        return fetch(urlAi, { credentials: 'same-origin' }).then(function (res) {
            if (!res.ok) {
                throw new Error('AI tour stress test HTTP ' + res.status);
            }
            return res.text();
        }).then(function (code) {
            (0, eval)(code);
            if (typeof window.runCre8PilotAiTourStressTest !== 'function') {
                throw new Error('AI tour stress suite did not register runCre8PilotAiTourStressTest.');
            }
            return window.runCre8PilotAiTourStressTest(opts);
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

        recs.forEach((row) => {
            if (!row || typeof row !== 'object') {
                return;
            }
            const name = String(row.creatorName || 'Creator').trim() || 'Creator';
            const score = typeof row.matchScore === 'number' ? row.matchScore : parseInt(row.matchScore, 10) || 0;
            const labelRaw = String(row.label || 'weak').toLowerCase();
            const labelNice = labelRaw === 'strong' ? 'Strong' : labelRaw === 'medium' ? 'Medium' : 'Weak';
            const reasons = Array.isArray(row.reasons) ? row.reasons.slice(0, 3) : [];

            const card = document.createElement('div');
            card.className = 'cre8pilot-match-row';

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
            wrap.appendChild(card);
        });

        messages.appendChild(wrap);
    }

    function appendCre8PilotSecurityCard(messages, security) {
        if (!messages || !security || typeof security !== 'object') {
            return;
        }
        const level = String(security.riskLevel || 'low').toLowerCase();
        const score = typeof security.riskScore === 'number' ? security.riskScore : parseInt(security.riskScore, 10) || 0;
        const categories = Array.isArray(security.riskCategories) ? security.riskCategories : [];
        const findings = Array.isArray(security.findings) ? security.findings : [];
        const recs = Array.isArray(security.safeRecommendations) ? security.safeRecommendations : [];

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
            message: ['message', 'messageNegociation', 'contenu', 'messageMotivation', 'noteDecision'],
            messageNegociation: ['messageNegociation', 'message', 'contenu', 'messageMotivation'],
            contenu: ['contenu', 'message', 'messageNegociation', 'messageMotivation'],
            negotiationMessage: ['messageMotivation', 'messageNegociation', 'message', 'contenu'],
            refusalMessage: ['motifRefus', 'noteDecision', 'messageMotivation'],
            decisionNote: ['noteDecision', 'messageMotivation'],
        };

        return aliases[name] || [name];
    }

    function findField(name) {
        if (!name) {
            return null;
        }

        const dataCandidates = [];
        const nameCandidates = [];
        const idCandidates = [];
        getFieldAliases(name).forEach((fieldName) => {
            dataCandidates.push(document.querySelector('[data-cre8pilot-field="' + fieldName + '"]'));
            document.getElementsByName(fieldName).forEach((field) => nameCandidates.push(field));
            idCandidates.push(document.getElementById(fieldName));
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
            .slice(0, 12)
            .map((item) => textOf(item, 260))
            .filter(Boolean);
    }

    function fillField(name, value) {
        const field = findField(name);
        if (!field) {
            return false;
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = Boolean(value);
        } else {
            field.value = String(value);
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        field.classList.add('cre8pilot-field-highlight');
        window.setTimeout(() => field.classList.remove('cre8pilot-field-highlight'), 2200);
        return true;
    }

    function handleAction(action, messages, widget) {
        if (!action || typeof action !== 'object') {
            return;
        }

        if (action.type === 'show_message') {
            appendMessage(messages, String(action.message || ''), 'assistant');
            return;
        }

        if (action.type === 'fill_form') {
            if (widget) {
                setCre8PilotAvatarState('filling', widget);
            }
            const fields = action.fields && typeof action.fields === 'object' ? action.fields : {};
            let filled = 0;
            Object.keys(fields).forEach((name) => {
                if (fillField(name, fields[name])) {
                    filled++;
                }
            });
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

        if (action.type === 'highlight_field') {
            const fieldName = action.field || action.name;
            const field = findField(fieldName);
            if (field) {
                field.classList.add('cre8pilot-field-highlight');
                field.focus({ preventScroll: false });
                window.setTimeout(() => field.classList.remove('cre8pilot-field-highlight'), 2200);
            }
            return;
        }

        if (action.type === 'apply_filter') {
            const fields = action.fields && typeof action.fields === 'object' ? action.fields : {};
            let touched = 0;
            Object.keys(fields).forEach((name) => {
                if (fillField(name, fields[name])) {
                    touched++;
                }
            });
            appendMessage(messages, touched > 0 ? 'I prepared the filter fields. Please apply them manually.' : 'No matching filter fields were found here.', 'assistant', 'action');
        }
    }

    function collectVisibleData() {
        const heading = document.querySelector('h1');
        const title = heading ? heading.textContent.trim() : document.title;
        const context = window.CRE8PILOT_CONTEXT || {};
        const data = {
            title,
            url: window.location.pathname,
            page: context.page || 'unknown',
            mode: context.mode || '',
            role: context.role || '',
            formTarget: context.formTarget || '',
            visibleEntityType: context.visibleEntityType || '',
            visibleEntityId: context.visibleEntityId || '',
            offerForm: {
                selectedCreator: textOf(document.querySelector('[data-selected-creator-summary], .selected-creator-card, .selected-creator, .creator-option.is-selected'), 260),
                titre: safeFieldValue('titre'),
                objectif: safeFieldValue('objectif'),
                description: safeFieldValue('description'),
                budgetPropose: safeFieldValue('budgetPropose'),
                dateLimite: safeFieldValue('dateLimite'),
                raisonChoix: safeFieldValue('raisonChoix'),
                attenteCollaboration: safeFieldValue('attenteCollaboration'),
                messagePersonnalise: safeFieldValue('messagePersonnalise'),
                category: safeFieldValue('category') || safeFieldValue('categorie'),
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
        };

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
                ['Summarize my offers', 'summarize_page'],
                ['What should I check first?', 'recommend_next_action'],
                ['Show expired offers', 'find_urgent_offers'],
                ['Search offer', 'apply_search'],
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
                ['Summarize my candidatures', 'summarize_page'],
                ['What should I do next?', 'recommend_next_action'],
                ['Applications need action', 'recommend_next_action'],
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
        utterance.onerror = () => {
            const w = options.cre8PilotWidget || (options.messageItem && options.messageItem.closest('[data-cre8pilot-widget]')) || cre8PilotResolveWidget(null);
            stopCre8PilotSpeech();
            if (w) {
                setCre8PilotAvatarState('error', w);
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
    });
})();
</script>
