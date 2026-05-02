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
<div class="cre8pilot-widget" data-cre8pilot-widget data-cre8pilot-endpoint="<?php echo htmlspecialchars($cre8PilotEndpoint); ?>">
    <button type="button" class="cre8pilot-fab" data-cre8pilot-toggle aria-label="Open Cre8Pilot">
        <span aria-hidden="true">&#10024;</span>
        <strong>Cre8Pilot</strong>
    </button>

    <section class="cre8pilot-panel" data-cre8pilot-panel hidden aria-label="Cre8Pilot chat panel">
        <header class="cre8pilot-header">
            <div>
                <span class="cre8pilot-kicker">&#10024; Cre8Pilot</span>
                <h2>Cre8Pilot</h2>
                <p>Your smart assistant for offers, candidatures, and collaboration decisions.</p>
            </div>
            <button type="button" class="cre8pilot-close" data-cre8pilot-close aria-label="Close Cre8Pilot">Close</button>
        </header>

        <div class="cre8pilot-messages" data-cre8pilot-messages>
            <article class="cre8pilot-message cre8pilot-message-assistant">
                <p>Hi, I'm Cre8Pilot. I can help you summarize, analyze, recommend, or prepare forms depending on this page.</p>
            </article>
        </div>

        <div class="cre8pilot-quick-actions" data-cre8pilot-quick-actions aria-label="Cre8Pilot quick actions"></div>

        <form class="cre8pilot-composer" data-cre8pilot-form>
            <label for="cre8pilotPrompt">Message Cre8Pilot</label>
            <textarea id="cre8pilotPrompt" data-cre8pilot-input rows="3" placeholder="Ask Cre8Pilot to summarize, prepare an offer, or draft a response..."></textarea>
            <div class="cre8pilot-voice-status" data-cre8pilot-voice-status aria-live="polite">Voice ready.</div>
            <div class="cre8pilot-composer-actions">
                <span data-cre8pilot-state>Mock mode</span>
                <div class="cre8pilot-voice-controls" aria-label="Cre8Pilot voice controls">
                    <button type="button" class="cre8pilot-voice-mode-btn" data-cre8pilot-voice-mode-open title="Open Voice Mode" aria-label="Open Voice Mode">Voice Mode</button>
                    <button type="button" class="cre8pilot-voice-btn" data-cre8pilot-mic title="Speak to Cre8Pilot" aria-label="Speak to Cre8Pilot">&#127897;&#65039;</button>
                </div>
                <button type="submit">Send</button>
            </div>
        </form>
    </section>

    <section class="cre8pilot-voice-overlay" data-cre8pilot-voice-overlay data-voice-state="idle" hidden aria-label="Cre8Pilot voice mode">
        <button type="button" class="cre8pilot-voice-close" data-cre8pilot-voice-close aria-label="Exit voice mode">Exit</button>
        <div class="cre8pilot-voice-stage">
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
                return {
                    id,
                    name: name || textOf(card, 80),
                    email,
                    details: textOf(card, 180),
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

    function handleAction(action, messages) {
        if (!action || typeof action !== 'object') {
            return;
        }

        if (action.type === 'show_message') {
            appendMessage(messages, String(action.message || ''), 'assistant');
            return;
        }

        if (action.type === 'fill_form') {
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

    function setVoiceStatus(widget, message) {
        const status = widget.querySelector('[data-cre8pilot-voice-status]');
        if (status) {
            status.textContent = message;
        }
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
            if (typeof options.onStart === 'function') {
                options.onStart();
            }
        };
        utterance.onend = () => {
            stopCre8PilotSpeech();
            if (typeof options.onEnd === 'function') {
                options.onEnd();
            }
        };
        utterance.onerror = () => {
            stopCre8PilotSpeech();
            if (typeof options.onEnd === 'function') {
                options.onEnd();
            }
        };
        window.speechSynthesis.speak(utterance);
    }

    function speakCre8PilotMessage(messageItem, button, text) {
        speakCre8PilotText(text, {
            messageItem,
            button,
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

    function scheduleCre8PilotSilenceTimer(widget, input, state, micButton) {
        clearCre8PilotSilenceTimer(widget);
        widget.cre8PilotSilenceTimer = window.setTimeout(() => {
            if (!widget.cre8PilotListening) {
                return;
            }
            widget.cre8PilotSilenceStop = true;
            stopCre8PilotListening(widget, input, state, micButton, 'Listening stopped after silence.');
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

    function stopCre8PilotListening(widget, input, state, micButton, statusMessage = 'Voice captured. Review and click Send.') {
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
        if (state && statusMessage) {
            state.textContent = statusMessage;
        }
        if (statusMessage) {
            setVoiceStatus(widget, statusMessage);
        }
    }

    function startCre8PilotListening(widget, input, messages, state, micButton) {
        const isEdge = isCre8PilotEdgeBrowser();
        if (!isSpeechRecognitionSupported()) {
            setVoiceStatus(widget, 'Voice input is not supported in this browser. Please use Chrome, or type your message manually.');
            if (micButton) {
                micButton.disabled = true;
            }
            return;
        }

        if (widget.cre8PilotListening) {
            stopCre8PilotListening(widget, input, state, micButton, 'Voice captured. Review and click Send.');
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
            state.textContent = 'Listening... speak now';
            setVoiceStatus(widget, 'Listening... speak now');
            scheduleCre8PilotSilenceTimer(widget, input, state, micButton);
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
                state.textContent = heard;
                setVoiceStatus(widget, heard);
                scheduleCre8PilotSilenceTimer(widget, input, state, micButton);
            }

            if (finalText.trim()) {
                const cleanFinal = finalText.trim();
                if (cleanFinal !== widget.cre8PilotLastFinalSegment) {
                    insertVoiceTextIntoTextarea(input, cleanFinal);
                    widget.cre8PilotLastFinalSegment = cleanFinal;
                }
                state.textContent = 'Voice captured. Review and click Send.';
                setVoiceStatus(widget, 'Voice captured. Review and click Send.');
                scheduleCre8PilotSilenceTimer(widget, input, state, micButton);
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

            state.textContent = message;
            widget.cre8PilotListening = false;
            clearCre8PilotSilenceTimer(widget);
            setCre8PilotMicState(widget, micButton, false);

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
                            stopCre8PilotListening(widget, input, state, micButton, 'Voice recognition stopped. You can try again or type manually.');
                        }
                    }
                }, 400);
                return;
            }

            if (widget.cre8PilotListening) {
                stopCre8PilotListening(
                    widget,
                    input,
                    state,
                    micButton,
                    widget.cre8PilotSilenceStop ? 'Listening stopped after silence.' : 'Voice captured. Review and click Send.'
                );
            } else {
                clearCre8PilotSilenceTimer(widget);
                setCre8PilotMicState(widget, micButton, false);
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
        }
    }

    function initCre8PilotVoice(widget, input, messages, state) {
        const micButton = widget.querySelector('[data-cre8pilot-mic]');

        if (micButton) {
            if (!isSpeechRecognitionSupported()) {
                micButton.disabled = true;
                micButton.title = 'Voice input is not supported in this browser. Please use Chrome, or type your message manually.';
            }
            micButton.addEventListener('click', () => startCre8PilotListening(widget, input, messages, state, micButton));
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
    }

    function initCre8PilotVoiceMode(widget, submitPrompt, setOpen) {
        const elements = getCre8PilotVoiceModeElements(widget);
        if (!elements.overlay) {
            return;
        }

        const openButton = widget.querySelector('[data-cre8pilot-voice-mode-open]');
        const openOverlay = () => {
            setOpen(true);
            elements.overlay.hidden = false;
            setCre8PilotVoiceModeState(widget, 'idle', 'Tap the mic and speak.');
        };

        if (openButton) {
            openButton.addEventListener('click', openOverlay);
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
        const state = widget.querySelector('[data-cre8pilot-state]');
        const quickActions = widget.querySelector('[data-cre8pilot-quick-actions]');
        const endpoint = widget.dataset.cre8pilotEndpoint || '';
        initCre8PilotVoice(widget, input, messages, state);

        function setOpen(open) {
            panel.hidden = !open;
            widget.classList.toggle('is-open', open);
            if (open) {
                input.focus();
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
            state.textContent = 'Cre8Pilot is thinking...';

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
                    if (data.needsUserConfirmation) {
                        appendMessage(messages, 'Please review the prepared content. I will not submit or save anything automatically.', 'assistant', 'action');
                    }
                    if (data.clarification && Array.isArray(data.clarification.options)) {
                        appendClarificationOptions(messages, data.clarification.options, submitPrompt);
                    }
                    if (Array.isArray(data.actions)) {
                        data.actions.forEach((action) => handleAction(action, messages));
                    }
                    state.textContent = 'Mock mode';
                    if (typeof options.onAssistantResponse === 'function') {
                        options.onAssistantResponse(data, assistantText);
                    }
                    return data;
                })
                .catch((error) => {
                    appendMessage(messages, 'Cre8Pilot could not reach the mock endpoint right now.', 'assistant', 'error');
                    state.textContent = 'Mock mode';
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

        initCre8PilotVoiceMode(widget, submitPrompt, setOpen);

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitPrompt(input.value);
        });

        if (quickActions) {
            const page = (window.CRE8PILOT_CONTEXT && window.CRE8PILOT_CONTEXT.page) || 'unknown';
            const mode = (window.CRE8PILOT_CONTEXT && window.CRE8PILOT_CONTEXT.mode) || '';
            quickActionsForContext(page, mode).forEach(([label, id]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'cre8pilot-quick-action-btn';
                button.textContent = label;
                button.addEventListener('click', () => submitPrompt(label, id));
                quickActions.appendChild(button);
            });
        }
    });
})();
</script>
