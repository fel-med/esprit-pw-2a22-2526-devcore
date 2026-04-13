document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-creator-picker]').forEach(function (picker) {
        initCreatorPicker(picker);
    });
});

function initCreatorPicker(picker) {
    const endpoint = picker.dataset.endpoint || '';
    const pageSize = clampPickerNumber(picker.dataset.pageSize, 6, 1, 24);
    const searchInput = picker.querySelector('[data-creator-search]');
    const grid = picker.querySelector('[data-creator-grid]');
    const emptyState = picker.querySelector('[data-creator-empty]');
    const emptyTitle = picker.querySelector('[data-creator-empty-title]');
    const emptyCopy = picker.querySelector('[data-creator-empty-copy]');
    const footer = picker.querySelector('[data-creator-footer]');
    const loadMoreButton = picker.querySelector('[data-creator-load-more]');
    const statusNote = picker.querySelector('[data-creator-status]');
    const resultsNote = picker.querySelector('[data-creator-results]');
    const hiddenInput = picker.querySelector('input[name="idCreateurCible"]');
    const summary = picker.querySelector('[data-selected-summary]');
    const summaryName = picker.querySelector('[data-selected-name]');
    const summaryEmail = picker.querySelector('[data-selected-email]');
    const summaryMeta = picker.querySelector('[data-selected-meta]');
    const clearSelectionButton = picker.querySelector('[data-clear-selection]');
    const initialCount = clampPickerNumber(picker.dataset.initialCount, grid ? grid.children.length : 0, 0, 999);
    const defaultStatusMessage = statusNote ? statusNote.textContent.trim() : '';
    const defaultEmptyTitle = emptyTitle ? emptyTitle.textContent.trim() : '';
    const defaultEmptyCopy = emptyCopy ? emptyCopy.textContent.trim() : '';
    let loadedCount = initialCount;
    let hasMore = picker.dataset.hasMore === '1';
    let currentKeyword = '';
    let debounceTimer = null;
    let activeRequest = null;
    let requestNonce = 0;
    let selectedCreator = readSelectedCreatorFromDataset(picker);

    function syncPickerDataset() {
        if (selectedCreator && selectedCreator.id) {
            picker.dataset.selectedId = selectedCreator.id;
            picker.dataset.selectedName = selectedCreator.name;
            picker.dataset.selectedEmail = selectedCreator.email;
            picker.dataset.selectedStatus = selectedCreator.statusLabel;
            picker.dataset.selectedStatusClass = selectedCreator.statusClass;
            picker.dataset.selectedTargeted = String(selectedCreator.targetedOffers);
            picker.dataset.selectedLive = String(selectedCreator.liveOffers);
        } else {
            delete picker.dataset.selectedId;
            delete picker.dataset.selectedName;
            delete picker.dataset.selectedEmail;
            delete picker.dataset.selectedStatus;
            delete picker.dataset.selectedStatusClass;
            delete picker.dataset.selectedTargeted;
            delete picker.dataset.selectedLive;
        }
    }

    function dispatchPickerEvent(name) {
        picker.dispatchEvent(new CustomEvent(name, {
            bubbles: true,
            detail: selectedCreator ? Object.assign({}, selectedCreator) : null
        }));
    }

    function updateSummary() {
        if (!summary) {
            return;
        }

        if (!selectedCreator || !selectedCreator.id) {
            summary.classList.add('is-hidden');
            return;
        }

        summary.classList.remove('is-hidden');

        if (summaryName) {
            summaryName.textContent = selectedCreator.name || 'Unknown creator';
        }

        if (summaryEmail) {
            summaryEmail.textContent = selectedCreator.email || '';
        }

        if (summaryMeta) {
            summaryMeta.innerHTML = '';
            summaryMeta.appendChild(buildPill(selectedCreator.statusLabel || 'Selected', selectedCreator.statusClass || ''));
            summaryMeta.appendChild(buildPill(selectedCreator.targetedOffers + ' targeted offers', ''));
            summaryMeta.appendChild(buildPill(selectedCreator.liveOffers + ' live', ''));
        }
    }

    function syncHiddenInput() {
        if (!hiddenInput) {
            return;
        }

        hiddenInput.value = selectedCreator && selectedCreator.id ? selectedCreator.id : '';
        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function refreshSelectionStyling() {
        if (!grid) {
            return;
        }

        grid.querySelectorAll('.creator-option').forEach(function (card) {
            const isSelected = !!selectedCreator && selectedCreator.id && card.dataset.creatorId === selectedCreator.id;
            card.classList.toggle('is-selected', isSelected);
        });
    }

    function applySelection(creator) {
        selectedCreator = creator && creator.id ? creator : null;
        syncPickerDataset();
        syncHiddenInput();
        updateSummary();
        refreshSelectionStyling();
        dispatchPickerEvent('creatorpicker:change');
    }

    function clearSelection() {
        selectedCreator = null;
        syncPickerDataset();
        syncHiddenInput();
        updateSummary();
        refreshSelectionStyling();
        dispatchPickerEvent('creatorpicker:clear');
    }

    function setLoadingState(isLoading) {
        if (grid) {
            grid.classList.toggle('is-loading', isLoading);
        }

        if (loadMoreButton) {
            loadMoreButton.disabled = isLoading;
            loadMoreButton.textContent = isLoading ? 'Loading creators...' : 'Load more creators';
        }
    }

    function updateNotes() {
        if (statusNote) {
            if (currentKeyword) {
                statusNote.textContent = 'Searching the creator base for "' + currentKeyword + '".';
            } else {
                statusNote.textContent = defaultStatusMessage;
            }
        }

        if (resultsNote) {
            if (loadedCount === 0) {
                resultsNote.textContent = currentKeyword
                    ? 'No creators match this search yet.'
                    : 'No creators are available right now.';
            } else if (currentKeyword) {
                resultsNote.textContent = 'Showing ' + loadedCount + ' matching creators' + (hasMore ? '. Load more to expand the search results.' : '.');
            } else {
                resultsNote.textContent = 'Showing ' + loadedCount + ' creators from the current shortlist' + (hasMore ? '. Load more if you want a wider pool.' : '.');
            }
        }

        if (emptyTitle) {
            emptyTitle.textContent = currentKeyword ? 'No creators found' : defaultEmptyTitle;
        }

        if (emptyCopy) {
            emptyCopy.textContent = currentKeyword
                ? 'Try another name, email, or ID, or clear the search to return to the shortlist.'
                : defaultEmptyCopy;
        }
    }

    function renderCreators(items, replaceExisting) {
        if (!grid) {
            return;
        }

        if (replaceExisting) {
            grid.innerHTML = '';
            loadedCount = 0;
        }

        items.forEach(function (item) {
            grid.appendChild(buildCreatorCard(normalizeCreator(item)));
            loadedCount += 1;
        });

        const hasItems = loadedCount > 0;
        grid.classList.toggle('is-hidden', !hasItems);

        if (emptyState) {
            emptyState.classList.toggle('is-hidden', hasItems);
        }

        if (footer) {
            footer.classList.toggle('is-hidden', !hasItems);
        }

        if (loadMoreButton) {
            loadMoreButton.hidden = !hasMore || !hasItems;
        }

        refreshSelectionStyling();
        updateNotes();
        dispatchPickerEvent('creatorpicker:render');
    }

    function fetchCreators(options) {
        if (!endpoint) {
            return;
        }

        const isReset = !!options.reset;
        const offset = isReset ? 0 : loadedCount;
        const keyword = typeof options.keyword === 'string' ? options.keyword.trim() : currentKeyword;
        const params = new URLSearchParams();
        params.set('keyword', keyword);
        params.set('limit', String(pageSize));
        params.set('offset', String(offset));

        currentKeyword = keyword;
        const requestId = ++requestNonce;

        if (activeRequest && typeof activeRequest.abort === 'function') {
            activeRequest.abort();
        }

        activeRequest = typeof AbortController !== 'undefined' ? new AbortController() : null;
        setLoadingState(true);

        fetch(endpoint + '&' + params.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: activeRequest ? activeRequest.signal : undefined
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Unable to load creators.');
                }

                return response.json();
            })
            .then(function (payload) {
                if (requestId !== requestNonce) {
                    return;
                }

                const items = Array.isArray(payload.items) ? payload.items : [];
                hasMore = !!payload.hasMore;
                renderCreators(items, isReset);
                dispatchPickerEvent('creatorpicker:results');
            })
            .catch(function (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }

                if (requestId !== requestNonce) {
                    return;
                }

                if (statusNote) {
                    statusNote.textContent = 'Unable to load more creators right now. Please try again.';
                }
            })
            .finally(function () {
                if (requestId !== requestNonce) {
                    return;
                }

                setLoadingState(false);
            });
    }

    if (grid) {
        grid.addEventListener('click', function (event) {
            const card = event.target.closest('.creator-option');
            if (!card || !grid.contains(card)) {
                return;
            }

            applySelection(normalizeCreator({
                id: card.dataset.creatorId,
                nom: card.dataset.name,
                email: card.dataset.email,
                statusLabel: card.dataset.statusLabel,
                statusClass: card.dataset.statusClass,
                targetedOffers: card.dataset.targeted,
                liveOffers: card.dataset.live
            }));
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const keyword = searchInput.value.trim();
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(function () {
                fetchCreators({ keyword: keyword, reset: true });
            }, 260);
        });
    }

    if (loadMoreButton) {
        loadMoreButton.addEventListener('click', function () {
            fetchCreators({ keyword: currentKeyword, reset: false });
        });
    }

    if (clearSelectionButton) {
        clearSelectionButton.addEventListener('click', clearSelection);
    }

    syncPickerDataset();
    syncHiddenInput();
    updateSummary();
    refreshSelectionStyling();
    updateNotes();
    dispatchPickerEvent('creatorpicker:ready');
}

function buildCreatorCard(creator) {
    const card = document.createElement('button');
    card.type = 'button';
    card.className = 'creator-option creator-option-button';
    card.dataset.creatorId = creator.id;
    card.dataset.name = creator.name;
    card.dataset.email = creator.email;
    card.dataset.statusLabel = creator.statusLabel;
    card.dataset.statusClass = creator.statusClass;
    card.dataset.targeted = String(creator.targetedOffers);
    card.dataset.live = String(creator.liveOffers);
    card.innerHTML = [
        '<span class="creator-option-body">',
        '  <span class="creator-top">',
        '    <span>',
        '      <strong>' + escapeHtml(creator.name) + '</strong>',
        '      <span>' + escapeHtml(creator.email) + '</span>',
        '    </span>',
        '    <span class="creator-pill ' + escapeHtml(creator.statusClass) + '">' + escapeHtml(creator.statusLabel) + '</span>',
        '  </span>',
        '  <span class="creator-meta">',
        '    <span class="creator-pill">ID #' + escapeHtml(creator.id) + '</span>',
        '    <span class="creator-pill">' + escapeHtml(String(creator.targetedOffers)) + ' targeted offers</span>',
        '    <span class="creator-pill">' + escapeHtml(String(creator.liveOffers)) + ' live</span>',
        '  </span>',
        '</span>'
    ].join('');

    return card;
}

function readSelectedCreatorFromDataset(picker) {
    const id = picker.dataset.selectedId || '';
    if (!id || id === '0') {
        return null;
    }

    return {
        id: String(id),
        name: picker.dataset.selectedName || '',
        email: picker.dataset.selectedEmail || '',
        statusLabel: picker.dataset.selectedStatus || '',
        statusClass: picker.dataset.selectedStatusClass || '',
        targetedOffers: clampPickerNumber(picker.dataset.selectedTargeted, 0, 0, 999999),
        liveOffers: clampPickerNumber(picker.dataset.selectedLive, 0, 0, 999999)
    };
}

function normalizeCreator(item) {
    return {
        id: String(item.id || ''),
        name: item.nom || item.name || '',
        email: item.email || '',
        statusLabel: item.statusLabel || normalizeCreatorStatusLabel(item.statut || ''),
        statusClass: item.statusClass || normalizeCreatorStatusClass(item.statut || ''),
        targetedOffers: clampPickerNumber(item.targetedOffers, 0, 0, 999999),
        liveOffers: clampPickerNumber(item.liveOffers, 0, 0, 999999)
    };
}

function normalizeCreatorStatusLabel(status) {
    switch (status) {
        case 'actif':
            return 'Ready';
        case 'en_attente':
            return 'Pending review';
        case 'suspendu':
            return 'Limited';
        default:
            return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Ready';
    }
}

function normalizeCreatorStatusClass(status) {
    switch (status) {
        case 'actif':
            return 'active';
        case 'en_attente':
            return 'pending';
        default:
            return '';
    }
}

function buildPill(label, extraClass) {
    const pill = document.createElement('span');
    pill.className = 'creator-pill' + (extraClass ? ' ' + extraClass : '');
    pill.textContent = label;
    return pill;
}

function clampPickerNumber(value, fallback, minValue, maxValue) {
    const parsed = Number(value);
    if (!Number.isFinite(parsed)) {
        return fallback;
    }

    return Math.min(maxValue, Math.max(minValue, Math.trunc(parsed)));
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
