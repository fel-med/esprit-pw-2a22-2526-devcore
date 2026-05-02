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
            <div class="cre8pilot-composer-actions">
                <span data-cre8pilot-state>Mock mode</span>
                <button type="submit">Send</button>
            </div>
        </form>
    </section>
</div>
<script>
(() => {
    if (window.Cre8PilotV1Ready) {
        return;
    }
    window.Cre8PilotV1Ready = true;

    function appendMessage(messages, text, type = 'assistant', status = 'ok') {
        const item = document.createElement('article');
        item.className = 'cre8pilot-message cre8pilot-message-' + type;
        if (status && status !== 'ok') {
            item.classList.add('is-' + status);
        }
        const paragraph = document.createElement('p');
        paragraph.textContent = text;
        item.appendChild(paragraph);
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

        function setOpen(open) {
            panel.hidden = !open;
            widget.classList.toggle('is-open', open);
            if (open) {
                input.focus();
            }
        }

        toggle.addEventListener('click', () => setOpen(panel.hidden));
        closeButton.addEventListener('click', () => setOpen(false));

        function submitPrompt(rawPrompt, selectedClarificationId = '') {
            const prompt = String(rawPrompt || '').trim();
            if (prompt === '') {
                return;
            }

            appendMessage(messages, prompt, 'user');
            if (input.value.trim() === prompt) {
                input.value = '';
            }
            state.textContent = 'Cre8Pilot is thinking...';

            fetch(endpoint, {
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
                    appendMessage(messages, data.message || 'Cre8Pilot returned an empty response.', 'assistant', status);
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
                })
                .catch(() => {
                    appendMessage(messages, 'Cre8Pilot could not reach the mock endpoint right now.', 'assistant', 'error');
                    state.textContent = 'Mock mode';
                });
        }

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
