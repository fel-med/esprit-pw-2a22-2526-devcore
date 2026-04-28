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
    const openModalButton = picker.querySelector('[data-open-creator-modal]');
    const modal = picker.querySelector('[data-creator-modal]');
    const modalGrid = picker.querySelector('[data-creator-modal-grid]');
    const modalEmpty = picker.querySelector('[data-creator-modal-empty]');
    const modalLoadMoreButton = picker.querySelector('[data-creator-modal-load-more]');
    const modalResultsNote = picker.querySelector('[data-creator-modal-results]');
    const modalEndNote = picker.querySelector('[data-creator-modal-end]');
    const modalCloseButtons = picker.querySelectorAll('[data-close-creator-modal]');
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
    let modalLoadedCount = initialCount;
    let modalHasMore = hasMore;
    let currentKeyword = '';
    let debounceTimer = null;
    let activeRequest = null;
    let requestNonce = 0;
    let selectedCreator = readSelectedCreatorFromDataset(picker);

    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }

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
            card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
        });

        if (modalGrid) {
            modalGrid.querySelectorAll('.creator-option').forEach(function (card) {
                const isSelected = !!selectedCreator && selectedCreator.id && card.dataset.creatorId === selectedCreator.id;
                card.classList.toggle('is-selected', isSelected);
                card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
            });
        }
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

    function setLoadingState(isLoading, target) {
        if (grid && target !== 'modal') {
            grid.classList.toggle('is-loading', isLoading);
        }

        if (loadMoreButton) {
            loadMoreButton.disabled = isLoading;
            loadMoreButton.textContent = isLoading ? 'Loading creators...' : 'Load more creators';
        }

        if (modalLoadMoreButton) {
            modalLoadMoreButton.disabled = isLoading;
            modalLoadMoreButton.textContent = isLoading ? 'Loading creators...' : 'Load more';
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
                resultsNote.textContent = 'Showing ' + loadedCount + ' matching creators' + (hasMore ? '. Open the browser to view more.' : '.');
            } else {
                resultsNote.textContent = 'Showing ' + loadedCount + ' creators from the current shortlist' + (hasMore ? '. Open the browser to view more.' : '.');
            }
        }

        if (modalResultsNote) {
            const label = currentKeyword ? 'matching creators' : 'creators';
            modalResultsNote.textContent = modalLoadedCount === 0
                ? 'No creators are currently loaded.'
                : 'Showing ' + modalLoadedCount + ' ' + label + '.';
        }

        if (modalLoadMoreButton) {
            modalLoadMoreButton.hidden = !modalHasMore || modalLoadedCount === 0;
        }

        if (modalEndNote) {
            modalEndNote.hidden = modalHasMore || modalLoadedCount === 0;
            modalEndNote.textContent = currentKeyword
                ? 'There are no more matching creators to load.'
                : 'There are no more creators to load.';
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

        if (openModalButton) {
            openModalButton.hidden = !hasItems;
        }

        refreshSelectionStyling();
        renderModalFromInlineGrid();
        updateNotes();
        dispatchPickerEvent('creatorpicker:render');
    }

    function renderModalCreators(items, replaceExisting) {
        if (!modalGrid) {
            return;
        }

        if (replaceExisting) {
            modalGrid.innerHTML = '';
            modalLoadedCount = 0;
        }

        items.forEach(function (item) {
            modalGrid.appendChild(buildCreatorCard(normalizeCreator(item)));
            modalLoadedCount += 1;
        });

        const hasItems = modalLoadedCount > 0;
        if (modalEmpty) {
            modalEmpty.classList.toggle('is-hidden', hasItems);
        }

        refreshSelectionStyling();
        updateNotes();
    }

    function readCreatorFromCard(card) {
        return normalizeCreator({
            id: card.dataset.creatorId,
            nom: card.dataset.name,
            email: card.dataset.email,
            statusLabel: card.dataset.statusLabel,
            statusClass: card.dataset.statusClass,
            targetedOffers: card.dataset.targeted,
            liveOffers: card.dataset.live
        });
    }

    function renderModalFromInlineGrid() {
        if (!modalGrid || !grid) {
            return;
        }

        modalGrid.innerHTML = '';
        grid.querySelectorAll('.creator-option').forEach(function (card) {
            modalGrid.appendChild(buildCreatorCard(readCreatorFromCard(card)));
        });
        modalLoadedCount = modalGrid.children.length;
        modalHasMore = hasMore;

        const hasItems = modalGrid.children.length > 0;
        if (modalEmpty) {
            modalEmpty.classList.toggle('is-hidden', hasItems);
        }

        refreshSelectionStyling();
    }

    function setModalOpen(isOpen) {
        if (!modal) {
            return;
        }

        if (isOpen) {
            renderModalFromInlineGrid();
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            modal.setAttribute('tabindex', '-1');
            document.documentElement.classList.add('creator-modal-open');
            document.body.classList.add('creator-modal-open');
            const modalBody = modal.querySelector('.creator-modal-body');
            if (modalBody) {
                modalBody.scrollTop = 0;
            }
            const focusTarget = modal.querySelector('[data-close-creator-modal]') || modalLoadMoreButton;
            if (focusTarget) {
                window.setTimeout(function () {
                    focusTarget.focus({ preventScroll: true });
                }, 20);
            } else if (typeof modal.focus === 'function') {
                modal.focus({ preventScroll: true });
            }
        } else {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.documentElement.classList.remove('creator-modal-open');
            document.body.classList.remove('creator-modal-open');
            if (openModalButton) {
                openModalButton.focus({ preventScroll: true });
            }
        }
    }

    function fetchCreators(options) {
        if (!endpoint) {
            return;
        }

        const isReset = !!options.reset;
        const target = options.target === 'modal' ? 'modal' : 'inline';
        const offset = isReset ? 0 : (target === 'modal' ? modalLoadedCount : loadedCount);
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
        setLoadingState(true, target);

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
                if (target === 'modal') {
                    modalHasMore = !!payload.hasMore;
                    renderModalCreators(items, isReset);
                } else {
                    hasMore = !!payload.hasMore;
                    renderCreators(items, isReset);
                }
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

                setLoadingState(false, target);
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
        loadMoreButton.addEventListener('click', function (event) {
            event.preventDefault();
            fetchCreators({ keyword: currentKeyword, reset: false });
        });
    }

    if (openModalButton) {
        openModalButton.addEventListener('click', function (event) {
            event.preventDefault();
            setModalOpen(true);
        });
    }

    if (modalLoadMoreButton) {
        modalLoadMoreButton.addEventListener('click', function (event) {
            event.preventDefault();
            fetchCreators({ keyword: currentKeyword, reset: false, target: 'modal' });
        });
    }

    if (modalGrid) {
        modalGrid.addEventListener('click', function (event) {
            const card = event.target.closest('.creator-option');
            if (!card || !modalGrid.contains(card)) {
                return;
            }

            applySelection(readCreatorFromCard(card));
            setModalOpen(false);
        });
    }

    modalCloseButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setModalOpen(false);
        });
    });

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                setModalOpen(false);
            }
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && !modal.hidden) {
            setModalOpen(false);
        }
    });

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
    card.setAttribute('aria-pressed', 'false');
    card.innerHTML = [
        '<span class="creator-option-body">',
        '  <span class="creator-selected-badge" aria-hidden="true">Selected</span>',
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
