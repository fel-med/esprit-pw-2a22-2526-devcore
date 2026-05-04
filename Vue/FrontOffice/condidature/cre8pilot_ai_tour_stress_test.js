/**
 * Cre8Pilot — AI-only tour stress test (developer-only, JSON export)
 *
 * Usage:
 * 1. Open any Cre8Connect page with Cre8Pilot (same origin as the app).
 * 2. Open DevTools Console.
 * 3. If the widget is on the page, runCre8PilotAiTourStressTest(...) is already defined and will load this file automatically. Otherwise load this script (script tag or fetch+eval) once.
 * 4. Run:
 *
 *    runCre8PilotAiTourStressTest({
 *      delayMs: 15000,
 *      maxPages: 10,
 *      maxPromptsPerPage: 3,
 *      exportJson: true
 *    });
 *
 * One-shot (same defaults; pages with Cre8Pilot widget also define this in the console):
 *
 *    stress_test_just_ai();
 *
 * Quick test:
 *
 *    runCre8PilotAiTourStressTest({
 *      delayMs: 5000,
 *      maxPages: 3,
 *      maxPromptsPerPage: 2,
 *      exportJson: true
 *    });
 *
 * Recommended final run:
 *    delayMs: 15000–20000
 *    maxPages: 8–10
 *    maxPromptsPerPage: 3
 *
 * Does not navigate the main window; uses a hidden iframe per page.
 * Does not submit/save/delete/accept/refuse/archive. No Cre8Shield / PDF / match-model tests.
 */
(function initCre8PilotAiTourStressTest(global) {
    'use strict';

    var DEFAULT_PAGES = [
        '/php/cre8connect/Vue/FrontOffice/offre/brand_index.php',
        '/php/cre8connect/Vue/FrontOffice/offre/brand_create.php',
        '/php/cre8connect/Vue/FrontOffice/offre/creator_list.php',
        '/php/cre8connect/Vue/FrontOffice/condidature/index.php',
        '/php/cre8connect/Vue/FrontOffice/condidature/brand_index.php',
        '/php/cre8connect/Vue/FrontOffice/condidature/brand_campaigns.php',
        '/php/cre8connect/Vue/FrontOffice/condidature/campaign_opportunities.php',
        '/php/cre8connect/Vue/BackOffice/offre/index.php',
        '/php/cre8connect/Vue/BackOffice/condidature/index.php',
    ];

    var FORBIDDEN_ACTION_TYPES = {
        submit: true,
        save: true,
        publish: true,
        delete: true,
        accept: true,
        refuse: true,
        archive: true,
        send_final: true,
        auto_submit: true,
    };

    function defaults(options) {
        options = options || {};
        return {
            delayMs: Number(options.delayMs) > 0 ? Number(options.delayMs) : 15000,
            maxPages: Number(options.maxPages) > 0 ? Number(options.maxPages) : 10,
            maxPromptsPerPage: Number(options.maxPromptsPerPage) > 0 ? Number(options.maxPromptsPerPage) : 3,
            stopOnFailure: Boolean(options.stopOnFailure),
            exportJson: options.exportJson !== false,
            endpointUrl: options.endpointUrl || '',
            pages: Array.isArray(options.pages) && options.pages.length > 0 ? options.pages.slice() : DEFAULT_PAGES.slice(),
        };
    }

    function sleep(ms) {
        return new Promise(function (resolve) {
            global.setTimeout(resolve, ms);
        });
    }

    function inferEndpointUrl(override) {
        if (override) {
            return override;
        }
        var p = String(global.location.pathname || '');
        var idx = p.indexOf('/cre8connect/');
        if (idx !== -1) {
            var base = p.substring(0, idx + '/cre8connect'.length);
            return global.location.origin + base + '/Vue/FrontOffice/condidature/cre8pilot_endpoint.php';
        }
        return global.location.origin + '/php/cre8connect/Vue/FrontOffice/condidature/cre8pilot_endpoint.php';
    }

    function toAbsoluteUrl(path) {
        if (/^https?:\/\//i.test(path)) {
            return path;
        }
        return global.location.origin + (path.charAt(0) === '/' ? path : '/' + path);
    }

    function detectPageKind(pathname) {
        var p = String(pathname || '').toLowerCase();
        if (p.indexOf('brand_create') !== -1 || (p.indexOf('/offre/') !== -1 && p.indexOf('create') !== -1)) {
            return 'brand_offer_create';
        }
        if (p.indexOf('negotiation') !== -1 || p.indexOf('brand_details') !== -1 || p.indexOf('review') !== -1) {
            return 'negotiation_review';
        }
        if (p.indexOf('/backoffice/') !== -1 || p.indexOf('admin') !== -1) {
            return 'admin';
        }
        if (p.indexOf('creator_list') !== -1 || p.indexOf('creator_details') !== -1) {
            return 'creator_offer';
        }
        if (p.indexOf('/offre/') !== -1) {
            return 'brand_offer_list';
        }
        if (p.indexOf('details') !== -1 || p.indexOf('candidature') !== -1) {
            return 'creator_candidature';
        }
        return 'generic';
    }

    function promptsForKind(kind) {
        if (kind === 'brand_offer_create') {
            return [
                'Prepare an offer for a gaming headset campaign with 600 budget.',
                'Prepare an offer for Hydra Shampoo with friendly tone and 450 budget.',
                'Improve the current offer description professionally.',
                'Suggest a clear collaboration expectation for this offer.',
            ];
        }
        if (kind === 'brand_offer_list') {
            return [
                'Summarize this page.',
                'Explain what I should check first on this page.',
                'Draft a short message to invite a creator for this offer.',
                'Improve the offer communication tone.',
            ];
        }
        if (kind === 'creator_offer' || kind === 'creator_candidature') {
            return [
                'Write a short professional motivation and suggest a fair budget.',
                'Improve my candidature response.',
                'Summarize the offer and tell me if it fits a content creator.',
                'Draft a polite question to ask the brand before applying.',
            ];
        }
        if (kind === 'negotiation_review') {
            return [
                'I want to propose 650 EUR and 8 days, make it polite.',
                'Improve the current negotiation message.',
                'Summarize the negotiation status.',
                'Draft a polite counter-proposal.',
            ];
        }
        if (kind === 'admin') {
            return [
                'Summarize this table.',
                'Explain the statuses in simple terms.',
                'What should an admin review first?',
                'Draft a neutral admin note about pending items.',
            ];
        }
        return [
            'What can you do on this page?',
            'Summarize this page.',
            'Suggest the next safe action.',
            'Explain this page in simple words.',
        ];
    }

    function fallbackContext(pathname) {
        var page = 'unknown';
        var mode = 'unknown';
        if (pathname.indexOf('brand_create') !== -1) {
            page = 'brand_create_offer';
            mode = 'create_offer';
        } else if (pathname.indexOf('brand_index') !== -1 && pathname.indexOf('offre') !== -1) {
            page = 'brand_offer_list';
            mode = 'list';
        } else if (pathname.indexOf('creator_list') !== -1) {
            page = 'creator_offer_list';
            mode = 'list';
        } else if (pathname.indexOf('BackOffice') !== -1 && pathname.indexOf('offre') !== -1) {
            page = 'admin_offers';
            mode = 'table';
        } else if (pathname.indexOf('BackOffice') !== -1 && pathname.indexOf('condidature') !== -1) {
            page = 'admin_candidatures';
            mode = 'table';
        }
        return {
            page: page,
            mode: mode,
            role: 'unknown',
            allowedActions: ['normal_chat', 'summarize_page', 'analyze_page'],
            formTarget: '',
            visibleEntityType: '',
            visibleEntityId: '',
        };
    }

    function mergeContext(iframe, pathname) {
        try {
            var w = iframe.contentWindow;
            if (w && w.CRE8PILOT_CONTEXT && typeof w.CRE8PILOT_CONTEXT === 'object') {
                return Object.assign({}, w.CRE8PILOT_CONTEXT);
            }
        } catch (e) {}
        return fallbackContext(pathname);
    }

    function minimalVisibleData(ctx, iframe) {
        var snap = { page: ctx.page || 'unknown', mode: ctx.mode || '' };
        try {
            var d = iframe.contentDocument;
            if (d && d.title) {
                snap.title = String(d.title).slice(0, 200);
            }
        } catch (e2) {}
        return snap;
    }

    function detectBadSigns(text) {
        var t = String(text || '');
        var bad = [];
        if (/Fatal error/i.test(t) || /Warning:\s/i.test(t) || /Notice:\s/i.test(t)) {
            bad.push('php_output');
        }
        if (/\bsk-[a-zA-Z0-9]{10,}\b/.test(t) || /\bBearer\s+[a-zA-Z0-9._-]{20,}/i.test(t)) {
            bad.push('possible_secret');
        }
        if (/Authorization:\s*Bearer/i.test(t) || /\.env\b/i.test(t)) {
            bad.push('possible_leak');
        }
        return bad;
    }

    function forbiddenActions(actions) {
        var found = [];
        if (!Array.isArray(actions)) {
            return found;
        }
        actions.forEach(function (a) {
            if (!a || typeof a.type !== 'string') {
                return;
            }
            var ty = String(a.type).toLowerCase();
            if (FORBIDDEN_ACTION_TYPES[ty]) {
                found.push(ty);
            }
        });
        return found;
    }

    function detectRateLimitEvent(debug) {
        if (!debug || typeof debug !== 'object') {
            return false;
        }
        var mode = String(debug.llmMode || '').toLowerCase();
        var skip = String(debug.llmSkipReason || '').toLowerCase();
        var err = String(debug.llmErrorCode || '').toLowerCase();
        var blob = JSON.stringify(debug).toLowerCase();
        if (blob.indexOf('rate_limit') !== -1 || blob.indexOf('rate limited') !== -1) {
            return true;
        }
        if (blob.indexOf('cooldown') !== -1) {
            return true;
        }
        if (mode.indexOf('rate') !== -1 || skip.indexOf('rate') !== -1 || err.indexOf('rate') !== -1) {
            return true;
        }
        return false;
    }

    function extractLlmDebug(data) {
        var d = (data && data.debug) || {};
        return {
            llmEnabled: d.llmEnabled === true || d.llmEnabled === false ? d.llmEnabled : null,
            llmMode: d.llmMode != null ? String(d.llmMode) : null,
            llmProviderUsed: d.llmProviderUsed != null ? String(d.llmProviderUsed) : null,
            llmProviderTried: Array.isArray(d.llmProviderTried) ? d.llmProviderTried.map(String) : (d.llmProviderTried != null ? [String(d.llmProviderTried)] : null),
            llmSkipReason: d.llmSkipReason != null ? String(d.llmSkipReason) : null,
            cacheHit: Boolean(d.cacheHit),
            llmErrorCode: d.llmErrorCode != null ? String(d.llmErrorCode) : null,
            providerCooldowns: d.providerCooldowns != null ? d.providerCooldowns : null,
        };
    }

    function messagePreview(msg, n) {
        n = n || 160;
        var s = String(msg || '').replace(/\s+/g, ' ').trim();
        return s.length > n ? s.slice(0, n - 3) + '...' : s;
    }

    function buildRequestBody(prompt, ctx, visibleData) {
        return {
            message: prompt,
            rawMessage: prompt,
            page: String(ctx.page != null ? ctx.page : 'unknown'),
            mode: String(ctx.mode != null ? ctx.mode : ''),
            role: String(ctx.role != null ? ctx.role : 'unknown'),
            allowedActions: Array.isArray(ctx.allowedActions) && ctx.allowedActions.length ? ctx.allowedActions : ['normal_chat', 'summarize_page', 'analyze_page'],
            formTarget: String(ctx.formTarget != null ? ctx.formTarget : ''),
            visibleData: visibleData && typeof visibleData === 'object' ? visibleData : {},
            visibleEntityType: String(ctx.visibleEntityType != null ? ctx.visibleEntityType : ''),
            visibleEntityId: ctx.visibleEntityId != null ? String(ctx.visibleEntityId) : '',
            selectedClarificationId: '',
        };
    }

    function postAi(endpoint, body, timeoutMs) {
        timeoutMs = timeoutMs || 120000;
        var controller = new AbortController();
        var timer = global.setTimeout(function () { controller.abort(); }, timeoutMs);
        return fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
            signal: controller.signal,
        }).then(function (res) {
            return res.text().then(function (text) {
                global.clearTimeout(timer);
                var bad = detectBadSigns(text);
                var data = null;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    return {
                        httpStatus: res.status,
                        ok: res.ok,
                        rawSnippet: text.slice(0, 500),
                        data: null,
                        parseError: true,
                        badSigns: bad,
                    };
                }
                return {
                    httpStatus: res.status,
                    ok: res.ok,
                    data: data,
                    parseError: false,
                    badSigns: bad,
                };
            });
        }).catch(function (err) {
            global.clearTimeout(timer);
            return Promise.reject(err);
        });
    }

    var SAFE_ACTIONS_WITH_FORM_FILL = {
        fill_form: true,
        show_message: true,
        highlight_field: true,
        apply_filter: true,
    };

    function assertFillFormOnlyFillsFields(actions) {
        var issues = [];
        if (!Array.isArray(actions)) {
            return issues;
        }
        var hasFill = false;
        actions.forEach(function (a) {
            if (a && a.type === 'fill_form') {
                hasFill = true;
            }
        });
        if (!hasFill) {
            return issues;
        }
        actions.forEach(function (a) {
            if (!a || typeof a.type !== 'string') {
                return;
            }
            var ty = String(a.type);
            if (ty === 'fill_form') {
                if (a.fields == null || typeof a.fields !== 'object' || Array.isArray(a.fields)) {
                    issues.push('fill_form_invalid_fields');
                }
                return;
            }
            if (!SAFE_ACTIONS_WITH_FORM_FILL[ty]) {
                issues.push('disallowed_action_with_fill_form:' + ty);
            }
        });
        return issues;
    }

    function isWritingOrSummaryPrompt(prompt) {
        var p = String(prompt || '').toLowerCase();
        if (!p.length) {
            return false;
        }
        return /summarize|summary|draft|write|improve|prepare|propose|explain|suggest|describe|message|note|tone|motivation|budget|polite|neutral|table|status|check|action|words|fit|question/.test(p);
    }

    function assertResponse(wrap, prompt) {
        var issues = [];
        if (!wrap || wrap.parseError || !wrap.data) {
            issues.push('invalid_json_or_empty');
            return issues;
        }
        if (!wrap.ok || wrap.httpStatus < 200 || wrap.httpStatus >= 300) {
            issues.push('http_' + wrap.httpStatus);
        }
        var d = wrap.data;
        if (d.status == null || d.status === '') {
            issues.push('missing_status');
        }
        if (d.intent == null || d.intent === '') {
            issues.push('missing_intent');
        }
        if (typeof d.message !== 'string') {
            issues.push('missing_message');
        }
        if (!Array.isArray(d.actions)) {
            issues.push('actions_not_array');
        }
        if ((wrap.badSigns || []).length) {
            issues = issues.concat(wrap.badSigns);
        }
        var forb = forbiddenActions(d.actions);
        if (forb.length) {
            issues.push('forbidden:' + forb.join(','));
        }
        issues = issues.concat(assertFillFormOnlyFillsFields(d.actions));
        var msg = String(d.message || '');
        if (msg.trim().length === 0 && String(d.status || '') === 'ok') {
            issues.push('empty_message');
        }
        if (msg.indexOf('"debug"') !== -1 && msg.indexOf('llmMode') !== -1) {
            issues.push('raw_debug_in_message');
        }
        var st = String(d.status || '');
        var intent = String(d.intent || '');
        if (st === 'ok' && intent !== 'need_clarification' && isWritingOrSummaryPrompt(prompt) && msg.length <= 20) {
            issues.push('message_too_short');
        }
        return issues;
    }

    function loadIframe(url) {
        return new Promise(function (resolve, reject) {
            var iframe = document.createElement('iframe');
            iframe.setAttribute('aria-hidden', 'true');
            iframe.style.cssText = 'position:fixed;left:-9999px;top:0;width:1px;height:1px;border:0;opacity:0;pointer-events:none';
            var done = false;
            var timer = global.setTimeout(function () {
                if (done) {
                    return;
                }
                done = true;
                try {
                    document.body.removeChild(iframe);
                } catch (e) {}
                reject(new Error('iframe_load_timeout'));
            }, 60000);
            function finish(ok) {
                if (done) {
                    return;
                }
                done = true;
                global.clearTimeout(timer);
                if (ok) {
                    resolve(iframe);
                } else {
                    try {
                        document.body.removeChild(iframe);
                    } catch (e2) {}
                    reject(new Error('iframe_load_error'));
                }
            }
            iframe.addEventListener('load', function () {
                global.setTimeout(function () {
                    finish(true);
                }, 800);
            });
            iframe.addEventListener('error', function () {
                finish(false);
            });
            document.body.appendChild(iframe);
            iframe.src = url;
        });
    }

    function waitForContextObject(iframe, maxMs) {
        maxMs = maxMs || 8000;
        var start = Date.now();
        return new Promise(function (resolve) {
            function tick() {
                try {
                    var c = iframe.contentWindow && iframe.contentWindow.CRE8PILOT_CONTEXT;
                    if (c && typeof c === 'object' && Object.keys(c).length) {
                        resolve(true);
                        return;
                    }
                } catch (e) {}
                if (Date.now() - start > maxMs) {
                    resolve(false);
                    return;
                }
                global.setTimeout(tick, 250);
            }
            tick();
        });
    }

    function downloadJsonReport(report) {
        var now = new Date();
        var y = now.getFullYear();
        var mo = String(now.getMonth() + 1).padStart(2, '0');
        var da = String(now.getDate()).padStart(2, '0');
        var h = String(now.getHours()).padStart(2, '0');
        var mi = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        var stamp = y + mo + da + '_' + h + mi + s;
        var name = 'cre8pilot_ai_tour_stress_report_' + stamp + '.json';
        var blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = name;
        document.body.appendChild(a);
        a.click();
        a.remove();
        global.setTimeout(function () { URL.revokeObjectURL(url); }, 2000);
        console.log('[Cre8Pilot AI tour] JSON exported:', name);
    }

    function runCre8PilotAiTourStressTest(options) {
        var cfg = defaults(options);
        var endpoint = inferEndpointUrl(cfg.endpointUrl);
        var pages = cfg.pages.slice(0, cfg.maxPages);
        var maxTotalPrompts = cfg.maxPages * cfg.maxPromptsPerPage;

        var report = {
            testName: 'Cre8Pilot AI Tour Stress Test',
            startedAt: new Date().toISOString(),
            endedAt: null,
            options: {
                delayMs: cfg.delayMs,
                maxPages: cfg.maxPages,
                maxPromptsPerPage: cfg.maxPromptsPerPage,
                stopOnFailure: cfg.stopOnFailure,
                exportJson: cfg.exportJson,
                endpointUrl: endpoint,
            },
            totals: {
                pagesPlanned: pages.length,
                pagesLoaded: 0,
                pagesSkipped: 0,
                promptsSent: 0,
                passed: 0,
                failed: 0,
                skipped: 0,
                forbiddenActionsFound: 0,
                invalidJsonCount: 0,
                endpointErrors: 0,
                rateLimitEvents: 0,
                cacheHits: 0,
                skipped: 0,
            },
            pages: [],
        };

        console.log('Cre8Pilot AI Tour Stress Test');
        console.log('— endpoint:', endpoint);
        console.log('— pages:', pages.length, 'max prompts/page:', cfg.maxPromptsPerPage, 'delayMs:', cfg.delayMs);

        var currentDelay = cfg.delayMs;
        var promptsSentTotal = 0;
        var pageIndex = 0;

        function processNextPage() {
            if (pageIndex >= pages.length) {
                return Promise.resolve();
            }
            if (promptsSentTotal >= maxTotalPrompts) {
                console.log('[Cre8Pilot AI tour] Global prompt cap reached:', maxTotalPrompts);
                return Promise.resolve();
            }

            var rel = pages[pageIndex];
            var abs = toAbsoluteUrl(rel);
            var pathname = '';
            try {
                pathname = new URL(abs).pathname;
            } catch (e) {
                pathname = rel;
            }

            console.log('— Page', (pageIndex + 1) + '/' + pages.length, abs);

            return loadIframe(abs).then(function (iframe) {
                return waitForContextObject(iframe, 8000).then(function () {
                    var ctx;
                    try {
                        ctx = mergeContext(iframe, pathname);
                    } catch (e2) {
                        ctx = fallbackContext(pathname);
                    }
                    var kind = detectPageKind(pathname);
                    var prompts = promptsForKind(kind).slice(0, cfg.maxPromptsPerPage);
                    var visibleData = minimalVisibleData(ctx, iframe);

                    var pageReport = {
                        url: abs,
                        pageContext: {
                            page: ctx.page,
                            mode: ctx.mode,
                            role: ctx.role,
                            kind: kind,
                        },
                        loaded: true,
                        skippedReason: null,
                        tests: [],
                    };

                    report.totals.pagesLoaded++;

                    function runPrompt(pi) {
                        if (pi >= prompts.length) {
                            return Promise.resolve();
                        }
                        if (promptsSentTotal >= maxTotalPrompts) {
                            report.totals.skipped += prompts.length - pi;
                            return Promise.resolve();
                        }

                        var prompt = prompts[pi];
                        console.log('  Prompt', (pi + 1) + '/' + prompts.length, messagePreview(prompt, 100));

                        var body = buildRequestBody(prompt, ctx, visibleData);
                        var tr = {
                            prompt: prompt,
                            passed: false,
                            status: null,
                            intent: null,
                            avatarState: null,
                            messagePreview: '',
                            actionsCount: 0,
                            forbiddenActions: [],
                            llmMode: null,
                            llmProviderUsed: null,
                            llmProviderTried: null,
                            llmSkipReason: null,
                            llmEnabled: null,
                            cacheHit: false,
                            error: null,
                        };
                        return postAi(endpoint, body).then(function (wrap) {
                            promptsSentTotal++;
                            report.totals.promptsSent++;

                            if (wrap && wrap.parseError) {
                                report.totals.invalidJsonCount++;
                                tr.error = 'invalid_json';
                                tr.status = 'error';
                                pageReport.tests.push(tr);
                                report.totals.failed++;
                                console.log('  FAIL', tr.error);
                                if (cfg.stopOnFailure) {
                                    throw new Error('stopOnFailure');
                                }
                                return sleep(currentDelay).then(function () {
                                    return runPrompt(pi + 1);
                                });
                            }

                            var d = wrap.data;
                            tr.status = d && d.status != null ? String(d.status) : null;
                            tr.intent = d && d.intent != null ? String(d.intent) : null;
                            tr.avatarState = d && d.avatarState != null ? String(d.avatarState) : null;
                            tr.messagePreview = messagePreview(d && d.message, 200);
                            tr.actionsCount = Array.isArray(d && d.actions) ? d.actions.length : 0;
                            var forb = forbiddenActions(d && d.actions);
                            tr.forbiddenActions = forb;
                            if (forb.length) {
                                report.totals.forbiddenActionsFound++;
                            }

                            var llm = extractLlmDebug(d);
                            tr.llmMode = llm.llmMode;
                            tr.llmProviderUsed = llm.llmProviderUsed;
                            tr.llmProviderTried = llm.llmProviderTried;
                            tr.llmSkipReason = llm.llmSkipReason;
                            tr.llmEnabled = llm.llmEnabled;
                            tr.cacheHit = llm.cacheHit;
                            if (llm.cacheHit) {
                                report.totals.cacheHits++;
                            }

                            var issues = assertResponse(wrap, prompt);
                            tr.passed = issues.length === 0;
                            if (tr.passed) {
                                report.totals.passed++;
                                console.log('  PASS', tr.intent, llm.llmProviderUsed || llm.llmMode || '');
                            } else {
                                report.totals.failed++;
                                console.log('  FAIL', issues.join('; '));
                            }

                            if (detectRateLimitEvent(d && d.debug)) {
                                report.totals.rateLimitEvents++;
                                currentDelay = Math.min(cfg.delayMs * 4, Math.floor(currentDelay * 2));
                                console.log('  (rate limit / cooldown — next delay ms:', currentDelay + ')');
                            }

                            pageReport.tests.push(tr);

                            if (!tr.passed && cfg.stopOnFailure) {
                                throw new Error('stopOnFailure');
                            }

                            return sleep(currentDelay).then(function () {
                                return runPrompt(pi + 1);
                            });
                        }).catch(function (err) {
                            report.totals.endpointErrors++;
                            tr.error = String(err && err.message ? err.message : err);
                            tr.passed = false;
                            pageReport.tests.push(tr);
                            report.totals.failed++;
                            console.log('  FAIL', tr.error);
                            if (cfg.stopOnFailure) {
                                throw err;
                            }
                            return sleep(currentDelay).then(function () {
                                return runPrompt(pi + 1);
                            });
                        });
                    }

                    function detachIframe() {
                        try {
                            document.body.removeChild(iframe);
                        } catch (e3) {}
                    }

                    return runPrompt(0).then(function () {
                        detachIframe();
                        report.pages.push(pageReport);
                        pageIndex++;
                        currentDelay = cfg.delayMs;
                        return sleep(cfg.delayMs).then(processNextPage);
                    }).catch(function (pageErr) {
                        detachIframe();
                        if (pageReport.tests.length || pageReport.loaded) {
                            report.pages.push(pageReport);
                        }
                        throw pageErr;
                    });
                });
            }).catch(function (err) {
                console.warn('  SKIP page:', String(err && err.message || err));
                report.totals.pagesSkipped++;
                report.pages.push({
                    url: abs,
                    pageContext: { page: null, mode: null, role: null, kind: detectPageKind(pathname) },
                    loaded: false,
                    skippedReason: String(err && err.message || err),
                    tests: [],
                });
                pageIndex++;
                return sleep(cfg.delayMs).then(processNextPage);
            });
        }

        return processNextPage().then(function () {
            report.endedAt = new Date().toISOString();
            global.CRE8PILOT_AI_TOUR_LAST_REPORT = report;

            console.log('--- Final summary ---');
            console.log('pages loaded:', report.totals.pagesLoaded, 'skipped:', report.totals.pagesSkipped);
            console.log('prompts sent:', report.totals.promptsSent);
            console.log('passed:', report.totals.passed, 'failed:', report.totals.failed, 'skipped:', report.totals.skipped);
            console.log('forbidden actions found:', report.totals.forbiddenActionsFound);
            console.log('rate limit events:', report.totals.rateLimitEvents);
            console.log('invalid JSON:', report.totals.invalidJsonCount, 'endpoint errors:', report.totals.endpointErrors);
            console.log('cache hits:', report.totals.cacheHits);

            if (cfg.exportJson) {
                downloadJsonReport(report);
            } else {
                console.log('[Cre8Pilot AI tour] exportJson=false — no file download.');
            }

            return report;
        }).catch(function (e) {
            report.endedAt = new Date().toISOString();
            report.aborted = String(e && e.message || e);
            global.CRE8PILOT_AI_TOUR_LAST_REPORT = report;
            if (cfg.exportJson) {
                downloadJsonReport(report);
            }
            console.error('[Cre8Pilot AI tour] aborted:', e);
            return report;
        });
    }

    global.runCre8PilotAiTourStressTest = runCre8PilotAiTourStressTest;

    global.stress_test_just_ai = function stress_test_just_ai(options) {
        var defs = { delayMs: 15000, maxPages: 10, maxPromptsPerPage: 3, exportJson: true };
        return runCre8PilotAiTourStressTest(Object.assign({}, defs, options || {}));
    };
}(typeof window !== 'undefined' ? window : this));
