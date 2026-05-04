<?php

/**
 * Cre8Pilot V21 — document context store (PDF/TXT). Used by CondidatureC only.
 */
trait Cre8PilotDocumentTrait
{
    private $cre8PilotResolvedDocIds = [];
    private $cre8PilotResolvedDocLabels = [];
    private $cre8PilotDocumentResolutionReason = 'none';
    /** @var array|null */
    private $cre8PilotResolvedDocumentBundle = null;

    private function cre8PilotDocumentsRootDir()
    {
        return $this->cre8PilotStorageDir() . DIRECTORY_SEPARATOR . 'cre8pilot_documents';
    }

    private function getCre8PilotDocumentOwnerKey($userId)
    {
        $userId = (int) $userId;
        if ($userId > 0) {
            return 'user_' . $userId;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $sid = (string) session_id();

            return 'session_' . substr(hash('sha256', $sid !== '' ? $sid : 'nosession'), 0, 16);
        }

        return 'session_' . substr(hash('sha256', 'anon'), 0, 16);
    }

    private function sanitizeCre8PilotDocumentOwnerKey($ownerKey)
    {
        $ownerKey = (string) $ownerKey;

        return preg_match('/^(user_\d+|session_[a-f0-9]{16})$/', $ownerKey) ? $ownerKey : '';
    }

    private function getCre8PilotDocumentFolder($ownerKey)
    {
        $ownerKey = $this->sanitizeCre8PilotDocumentOwnerKey($ownerKey);
        if ($ownerKey === '') {
            return '';
        }

        return $this->cre8PilotDocumentsRootDir() . DIRECTORY_SEPARATOR . $ownerKey;
    }

    private function cre8PilotEnsureDocumentTree($ownerKey)
    {
        $root = $this->cre8PilotDocumentsRootDir();
        if (!$this->cre8PilotEnsureDirectory($root)) {
            return false;
        }
        $folder = $this->getCre8PilotDocumentFolder($ownerKey);

        return $folder !== '' && $this->cre8PilotEnsureDirectory($folder);
    }

    private function cre8PilotDocumentIndexPath($ownerKey)
    {
        $folder = $this->getCre8PilotDocumentFolder($ownerKey);

        return $folder !== '' ? $folder . DIRECTORY_SEPARATOR . 'index.json' : '';
    }

    private function loadCre8PilotDocumentIndex($ownerKey)
    {
        $path = $this->cre8PilotDocumentIndexPath($ownerKey);
        if ($path === '' || !is_file($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function saveCre8PilotDocumentIndex($ownerKey, array $index)
    {
        $path = $this->cre8PilotDocumentIndexPath($ownerKey);
        if ($path === '') {
            return false;
        }
        $json = json_encode(array_values($index), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return @file_put_contents($path, $json !== false ? $json : '[]') !== false;
    }

    private function addDocumentToIndex($ownerKey, array $entry)
    {
        $index = $this->loadCre8PilotDocumentIndex($ownerKey);
        $index[] = [
            'docId' => (string) ($entry['docId'] ?? ''),
            'label' => (string) ($entry['label'] ?? ''),
            'docType' => (string) ($entry['docType'] ?? ''),
            'sourceFileName' => (string) ($entry['sourceFileName'] ?? ''),
            'createdAt' => (string) ($entry['createdAt'] ?? ''),
            'expiresAt' => (string) ($entry['expiresAt'] ?? ''),
        ];
        usort($index, static function ($a, $b) {
            return strcmp((string) ($b['createdAt'] ?? ''), (string) ($a['createdAt'] ?? ''));
        });

        return $this->saveCre8PilotDocumentIndex($ownerKey, $index);
    }

    private function cleanupExpiredCre8PilotDocuments($ownerKey)
    {
        $folder = $this->getCre8PilotDocumentFolder($ownerKey);
        if ($folder === '' || !is_dir($folder)) {
            return;
        }
        $index = $this->loadCre8PilotDocumentIndex($ownerKey);
        if (empty($index)) {
            return;
        }
        $now = time();
        $kept = [];
        foreach ($index as $row) {
            $expires = strtotime((string) ($row['expiresAt'] ?? '')) ?: 0;
            $docId = preg_replace('/[^a-z0-9_]/i', '', (string) ($row['docId'] ?? ''));
            if ($expires > 0 && $expires < $now && $docId !== '') {
                $f = $folder . DIRECTORY_SEPARATOR . $docId . '.json';
                if (is_file($f)) {
                    @unlink($f);
                }
                continue;
            }
            if ($docId === '') {
                continue;
            }
            $kept[] = $row;
        }
        $this->saveCre8PilotDocumentIndex($ownerKey, $kept);
    }

    private function cre8PilotNormalizeExtractedText($text, $maxLen)
    {
        $text = (string) $text;
        $text = str_replace("\0", '', $text);
        $text = strip_tags($text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);
        if (strlen($text) > $maxLen) {
            $text = substr($text, 0, $maxLen);
        }

        return $text;
    }

    private function cre8PilotDetectDocumentTypeFromLabel($label, $textSample)
    {
        $blob = $this->normalizeCre8PilotMessage($label . ' ' . substr($textSample, 0, 800));
        if ($this->messageContainsAny($blob, ['portfolio'])) {
            return 'portfolio';
        }
        if ($this->messageContainsAny($blob, ['product brief', 'product info', 'campaign brief'])) {
            return 'product_brief';
        }
        if ($this->messageContainsAny($blob, ['cv', 'resume', 'curriculum'])) {
            return 'cv';
        }

        return 'general_document';
    }

    private function cre8PilotEmptyStructuredData()
    {
        return [
            'skills' => [],
            'education' => [],
            'experience' => [],
            'projects' => [],
            'products' => [],
            'campaignGoals' => [],
            'strengths' => [],
            'languages' => [],
            'importantDetails' => [],
        ];
    }

    private function cre8PilotHeuristicStructuredData($compactText)
    {
        $out = $this->cre8PilotEmptyStructuredData();
        $lines = preg_split('/\R/u', $compactText) ?: [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            $lower = strtolower($line);
            if (preg_match('/^(skills?|technologies?|stack)\s*[:\-]/i', $line, $m)) {
                $parts = preg_split('/[,;|]/', preg_replace('/^[^:]+:\s*/i', '', $line)) ?: [];
                foreach ($parts as $p) {
                    $p = trim($p);
                    if ($p !== '' && strlen($p) < 80) {
                        $out['skills'][] = $p;
                    }
                }
            } elseif (str_starts_with($lower, 'education') || str_starts_with($lower, 'degree')) {
                $out['education'][] = $this->sanitizeCre8PilotLlmScalar($line, 200);
            } elseif (str_contains($lower, 'experience') || str_contains($lower, 'worked at')) {
                $out['experience'][] = $this->sanitizeCre8PilotLlmScalar($line, 200);
            } else {
                if (count($out['importantDetails']) < 8) {
                    $out['importantDetails'][] = $this->sanitizeCre8PilotLlmScalar($line, 180);
                }
            }
        }
        foreach ($out as $k => $arr) {
            if (is_array($arr)) {
                $out[$k] = array_slice(array_values(array_unique($arr)), 0, 12);
            }
        }

        return $out;
    }

    private function cre8PilotHeuristicSummary($label, $docType, $compactText)
    {
        $snippet = trim(substr($compactText, 0, 500));
        $words = str_word_count(strtolower($compactText), 1, '0123456789');
        $freq = array_count_values(is_array($words) ? $words : []);
        arsort($freq);
        $keywords = array_slice(array_keys(array_filter($freq, static function ($w) {
            return strlen((string) $w) > 3;
        })), 0, 8);
        $kw = implode(', ', $keywords);
        $lbl = $this->sanitizeCre8PilotLlmScalar($label, 80);

        return $this->sanitizeCre8PilotLlmScalar(
            'Document (' . $docType . ($lbl !== '' ? ', ' . $lbl : '') . '). Excerpt: ' . $snippet . ($kw !== '' ? ' Keywords: ' . $kw : ''),
            900
        );
    }

    private function cre8PilotLlmEnrichDocumentMeta($compactText, $docType, $label)
    {
        if (!$this->cre8pilotLlmEnabled()) {
            return null;
        }
        $payload = [
            'docType' => $this->sanitizeCre8PilotLlmScalar($docType, 40),
            'label' => $this->sanitizeCre8PilotLlmScalar($label, 120),
            'text' => $this->sanitizeCre8PilotLlmScalar($compactText, 3500),
        ];
        $system = implode("\n", [
            'You extract structured information from collaboration-related documents (CV, portfolio notes, product briefs).',
            'Return JSON only: {"summary":"max 400 chars, no paths or secrets","structuredData":{"skills":[],"education":[],"experience":[],"projects":[],"products":[],"campaignGoals":[],"strengths":[],"languages":[],"importantDetails":[]}}.',
            'Arrays contain short string items only. No file paths, URLs unless public product links, passwords, or API keys.',
        ]);
        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
        ];
        $llm = $this->callCre8PilotLlm($messages);
        if (empty($llm['ok'])) {
            return null;
        }
        $data = $llm['data'] ?? [];
        if (!is_array($data)) {
            return null;
        }
        $summary = $this->sanitizeCre8PilotLlmScalar($data['summary'] ?? '', 450);
        $structured = $data['structuredData'] ?? null;
        if (!is_array($structured)) {
            return ['summary' => $summary, 'structuredData' => $this->cre8PilotHeuristicStructuredData($compactText)];
        }
        $clean = $this->cre8PilotEmptyStructuredData();
        foreach ($clean as $key => $_) {
            if (!empty($structured[$key]) && is_array($structured[$key])) {
                foreach (array_slice($structured[$key], 0, 15) as $item) {
                    $clean[$key][] = $this->sanitizeCre8PilotLlmScalar((string) $item, 220);
                }
            }
        }

        return ['summary' => $summary, 'structuredData' => $clean];
    }

    private function cre8PilotExtractPdfText($absolutePath)
    {
        $vendor = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!is_file($vendor)) {
            return [
                'ok' => false,
                'error' => 'PDF text extraction is not available yet on this installation. Please install the PDF parser or upload a TXT version.',
            ];
        }
        require_once $vendor;
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            return [
                'ok' => false,
                'error' => 'PDF text extraction is not available yet on this installation. Please install the PDF parser or upload a TXT version.',
            ];
        }
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($absolutePath);
            $text = $pdf->getText();
            $text = $this->cre8PilotNormalizeExtractedText($text, 120000);
            if (trim($text) === '') {
                return [
                    'ok' => false,
                    'error' => 'I could not extract readable text from this PDF. It may be a scanned image PDF.',
                ];
            }

            return ['ok' => true, 'text' => $text];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'error' => 'I could not extract readable text from this PDF. It may be a scanned image PDF.',
            ];
        }
    }

    private function cre8PilotExtractTxtText($absolutePath)
    {
        $raw = @file_get_contents($absolutePath);
        if ($raw === false) {
            return ['ok' => false, 'error' => 'Could not read the text file.'];
        }
        $text = $this->cre8PilotNormalizeExtractedText($raw, 120000);
        if ($text === '') {
            return ['ok' => false, 'error' => 'The text file appears empty.'];
        }

        return ['ok' => true, 'text' => $text];
    }

    private function saveCre8PilotDocumentContext($ownerKey, array $doc)
    {
        $folder = $this->getCre8PilotDocumentFolder($ownerKey);
        if ($folder === '') {
            return false;
        }
        $docId = preg_replace('/[^a-z0-9_]/i', '', (string) ($doc['docId'] ?? ''));
        if ($docId === '') {
            return false;
        }
        $path = $folder . DIRECTORY_SEPARATOR . $docId . '.json';
        $json = json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return $json !== false && @file_put_contents($path, $json) !== false;
    }

    private function loadCre8PilotDocumentById($ownerKey, $docId)
    {
        $docId = preg_replace('/[^a-z0-9_]/i', '', (string) $docId);
        if ($docId === '') {
            return null;
        }
        $folder = $this->getCre8PilotDocumentFolder($ownerKey);
        if ($folder === '') {
            return null;
        }
        $path = $folder . DIRECTORY_SEPARATOR . $docId . '.json';
        if (!is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return null;
        }
        if (($data['ownerKey'] ?? '') !== $ownerKey) {
            return null;
        }
        $exp = strtotime((string) ($data['expiresAt'] ?? '')) ?: 0;
        if ($exp > 0 && $exp < time()) {
            return null;
        }

        return $data;
    }

    private function getLatestCre8PilotDocument($ownerKey)
    {
        $index = $this->loadCre8PilotDocumentIndex($ownerKey);
        foreach ($index as $row) {
            $id = (string) ($row['docId'] ?? '');
            $doc = $this->loadCre8PilotDocumentById($ownerKey, $id);
            if ($doc !== null) {
                return $doc;
            }
        }

        return null;
    }

    private function findCre8PilotDocumentByReference($normalizedMessage, $ownerKey)
    {
        $index = $this->loadCre8PilotDocumentIndex($ownerKey);
        $candidates = [];
        foreach ($index as $row) {
            $id = (string) ($row['docId'] ?? '');
            $doc = $this->loadCre8PilotDocumentById($ownerKey, $id);
            if ($doc !== null) {
                $candidates[] = $doc;
            }
        }
        if (empty($candidates)) {
            return ['reason' => 'none', 'documents' => []];
        }

        $wantLatest = $this->messageContainsAny($normalizedMessage, [
            'last file',
            'latest file',
            'uploaded pdf',
            'the pdf',
            'the file i uploaded',
            'file i uploaded',
            'uploaded file',
            'summarize uploaded file',
            'summarize the last file',
            'summarize my document',
            'this document',
            'the document',
            'based on this document',
            'based on the document',
        ]);

        if ($wantLatest && !$this->messageContainsAny($normalizedMessage, ['cv', 'resume', 'portfolio', 'product brief'])) {
            return ['reason' => 'latest_file', 'documents' => [array_values($candidates)[0]]];
        }

        $cvMatch = $this->messageContainsAny($normalizedMessage, [
            'my cv',
            'my resume',
            'use cv',
            'use resume',
            'from my cv',
            'from my resume',
            'using my cv',
            'with my cv',
            'according to my cv',
            'summarize my uploaded cv',
            'summarize my cv',
            'my uploaded cv',
            'uploaded cv',
        ]);
        $portfolioMatch = $this->messageContainsAny($normalizedMessage, ['portfolio', 'my portfolio']);
        $briefMatch = $this->messageContainsAny($normalizedMessage, ['product brief', 'campaign brief', 'the brief']);

        $filtered = [];
        foreach ($candidates as $doc) {
            $type = (string) ($doc['docType'] ?? '');
            $id = (string) ($doc['docId'] ?? '');
            if ($id === '') {
                continue;
            }
            if ($cvMatch && $type === 'cv') {
                $filtered[$id] = $doc;
            }
            if ($portfolioMatch && $type === 'portfolio') {
                $filtered[$id] = $doc;
            }
            if ($briefMatch && $type === 'product_brief') {
                $filtered[$id] = $doc;
            }
        }
        $filtered = array_values($filtered);
        if (count($filtered) === 1) {
            return ['reason' => 'doc_type_match', 'documents' => $filtered];
        }
        if (count($filtered) > 1) {
            return ['reason' => 'multiple_matches', 'documents' => $filtered];
        }

        // Label match: compare normalized label
        $labelMatches = [];
        foreach ($candidates as $doc) {
            $lbl = $this->normalizeCre8PilotMessage((string) ($doc['label'] ?? ''));
            $id = (string) ($doc['docId'] ?? '');
            if ($lbl !== '' && $id !== '' && strpos($normalizedMessage, $lbl) !== false) {
                $labelMatches[$id] = $doc;
            }
        }
        $labelMatches = array_values($labelMatches);
        if (count($labelMatches) === 1) {
            return ['reason' => 'label_match', 'documents' => $labelMatches];
        }
        if (count($labelMatches) > 1) {
            return ['reason' => 'multiple_matches', 'documents' => $labelMatches];
        }

        if ($wantLatest) {
            return ['reason' => 'latest_file', 'documents' => [array_values($candidates)[0]]];
        }

        return ['reason' => 'none', 'documents' => []];
    }

    private function cre8PilotMessageWantsSavedDocument($normalized)
    {
        return $this->messageContainsAny($normalized, [
            'use my cv',
            'use my resume',
            'use the uploaded pdf',
            'based on the uploaded pdf',
            'use the uploaded file',
            'use the last file',
            'latest file',
            'based on this document',
            'based on the document',
            'use my portfolio',
            'use the product brief',
            'use the file i uploaded',
            'from my cv',
            'from my resume',
            'using my cv',
            'with my cv',
            'according to my cv',
            'using the product brief',
            'from the product brief',
            'use saved document',
            'use the pdf',
            'use uploaded pdf',
            'with the pdf',
            'summarize my uploaded cv',
            'summarize my cv',
            'summarize uploaded file',
            'summarize the last file',
            'summarize my document',
        ]);
    }

    private function cre8PilotCompactDocumentForLlm(array $doc)
    {
        $preview = (string) ($doc['safeTextPreview'] ?? '');
        $summary = (string) ($doc['summary'] ?? '');
        $structured = $doc['structuredData'] ?? [];
        $chunk = $summary . "\n" . $preview;
        if (is_array($structured)) {
            $chunk .= "\n" . json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $chunk = $this->cre8PilotNormalizeExtractedText($chunk, 3800);

        return [
            'docId' => (string) ($doc['docId'] ?? ''),
            'label' => $this->sanitizeCre8PilotLlmScalar((string) ($doc['label'] ?? ''), 120),
            'docType' => $this->sanitizeCre8PilotLlmScalar((string) ($doc['docType'] ?? ''), 40),
            'summary' => $this->sanitizeCre8PilotLlmScalar($summary, 500),
            'safeTextPreview' => $this->sanitizeCre8PilotLlmScalar($preview, 1200),
            'structuredData' => is_array($structured) ? $this->sanitizeCre8PilotLlmVisibleData($structured) : [],
        ];
    }

    private function cre8PilotApplyDocumentHintsToCandidatureFields(array $fields, $docCompact)
    {
        if (!is_array($docCompact)) {
            return $fields;
        }
        $skills = [];
        if (!empty($docCompact['structuredData']['skills']) && is_array($docCompact['structuredData']['skills'])) {
            $skills = array_slice($docCompact['structuredData']['skills'], 0, 8);
        }
        $skillLine = $skills !== [] ? implode(', ', $skills) : '';
        $baseMot = (string) ($fields['messageMotivation'] ?? '');
        $hint = trim((string) ($docCompact['summary'] ?? '') . ' ' . trim((string) ($docCompact['safeTextPreview'] ?? '')));
        $hint = $this->sanitizeCre8PilotLlmScalar($hint, 600);
        if ($hint !== '') {
            $fields['messageMotivation'] = $this->sanitizeCre8PilotLlmScalar(
                'Based on my saved document' . ($skillLine !== '' ? ' (skills: ' . $skillLine . ')' : '') . ': ' . $hint . ($baseMot !== '' ? ' ' . $baseMot : ''),
                1200
            );
        }
        $fields['conditionsCreateur'] = $this->sanitizeCre8PilotLlmScalar(
            (string) ($fields['conditionsCreateur'] ?? '') . ($skillLine !== '' ? ' Relevant strengths: ' . $skillLine . '.' : ''),
            800
        );

        return $fields;
    }

    private function cre8PilotApplyDocumentHintsToOfferFields(array $fields, $docCompact)
    {
        if (!is_array($docCompact)) {
            return $fields;
        }
        $hint = trim((string) ($docCompact['summary'] ?? '') . ' ' . trim((string) ($docCompact['safeTextPreview'] ?? '')));
        $hint = $this->sanitizeCre8PilotLlmScalar($hint, 800);
        if ($hint === '') {
            return $fields;
        }
        $fields['description'] = $this->sanitizeCre8PilotLlmScalar(
            (string) ($fields['description'] ?? '') . "\n\nContext from uploaded brief: " . $hint,
            2000
        );
        $fields['objectif'] = $this->sanitizeCre8PilotLlmScalar(
            (string) ($fields['objectif'] ?? '') !== '' ? (string) ($fields['objectif'] ?? '') : 'Align creator content with brief goals: ' . substr($hint, 0, 200),
            400
        );

        return $fields;
    }

    private function cre8PilotApplyDocumentHintsToNegotiation(array $actionList, $docCompact)
    {
        if (!is_array($docCompact) || empty($actionList)) {
            return $actionList;
        }
        $hint = $this->sanitizeCre8PilotLlmScalar(trim((string) ($docCompact['summary'] ?? '')), 400);
        foreach ($actionList as $i => $action) {
            if (!is_array($action) || ($action['type'] ?? '') !== 'fill_form') {
                continue;
            }
            $f = $action['fields'] ?? [];
            if (!is_array($f)) {
                $f = [];
            }
            $target = (string) ($action['target'] ?? '');
            $msg = (string) ($f['messageNegociation'] ?? $f['messageMotivation'] ?? '');
            if ($hint !== '') {
                $newMsg = $this->sanitizeCre8PilotLlmScalar(
                    $msg !== '' ? $msg . ' — Context: ' . $hint : 'Proposal note (from saved document): ' . $hint,
                    1200
                );
                if ($target === 'candidature_form') {
                    $f['messageMotivation'] = $newMsg;
                } else {
                    $f['messageNegociation'] = $newMsg;
                }
            }
            $actionList[$i]['fields'] = $f;
        }

        return $actionList;
    }

    private function cre8PilotResolveDocumentsForChat($messageLower, $ownerKey)
    {
        $this->cre8PilotResolvedDocIds = [];
        $this->cre8PilotResolvedDocLabels = [];
        $this->cre8PilotDocumentResolutionReason = 'none';
        $this->cre8PilotResolvedDocumentBundle = null;

        if (!$this->cre8PilotMessageWantsSavedDocument($messageLower)) {
            return ['status' => 'skip'];
        }

        $found = $this->findCre8PilotDocumentByReference($messageLower, $ownerKey);

        if (($found['reason'] ?? '') === 'multiple_matches') {
            $docs = $found['documents'] ?? [];
            $options = [];
            foreach ($docs as $d) {
                if (!is_array($d)) {
                    continue;
                }
                $options[] = [
                    'id' => 'doc_pick_' . preg_replace('/[^a-z0-9_]/i', '', (string) ($d['docId'] ?? '')),
                    'label' => $this->sanitizeCre8PilotLlmScalar((string) ($d['label'] ?? 'Document'), 120),
                ];
            }

            return [
                'status' => 'need_clarification',
                'options' => $options,
                'message' => 'I found multiple saved documents that could match. Which one should I use?',
            ];
        }

        $docs = $found['documents'] ?? [];
        if (empty($docs) || !is_array($docs[0])) {
            return [
                'status' => 'not_found',
                'message' => 'I could not find a saved document matching that. Please upload it first using Attach file.',
            ];
        }

        $doc = $docs[0];
        $this->cre8PilotResolvedDocIds = [(string) ($doc['docId'] ?? '')];
        $this->cre8PilotResolvedDocLabels = [$this->sanitizeCre8PilotLlmScalar((string) ($doc['label'] ?? ''), 120)];
        $this->cre8PilotResolvedDocumentBundle = $this->cre8PilotCompactDocumentForLlm($doc);
        $this->cre8PilotDocumentResolutionReason = (string) ($found['reason'] ?? 'none');

        return ['status' => 'ok'];
    }

    private function cre8PilotDocCanAssistOffer($page, $mode, $docType)
    {
        $docType = (string) $docType;
        $isOffer = in_array($page, ['brand_create_offer', 'brand_edit_offer', 'create_offer', 'edit_offer'], true)
            || $this->cre8PilotIsPageMode($page, $mode, 'brand_offer_workspace', ['create_offer', 'edit_offer']);
        if (!$isOffer) {
            return false;
        }

        return in_array($docType, ['product_brief', 'general_document'], true);
    }

    private function cre8PilotDocCanAssistCandidature($page, $mode, $docType)
    {
        $docType = (string) $docType;
        $isForm = in_array($page, ['candidature_form', 'creator_candidature_form'], true)
            || $this->cre8PilotIsPageMode($page, $mode, 'creator_candidature_workspace', ['application_form']);
        if (!$isForm) {
            return false;
        }

        return in_array($docType, ['cv', 'portfolio', 'general_document'], true);
    }

    public function handleCre8PilotDocumentUpload(array $post, array $files, array $sessionUser)
    {
        $userId = isset($sessionUser['id']) ? (int) $sessionUser['id'] : 0;
        $role = strtolower(trim((string) ($sessionUser['role'] ?? '')));
        $debug = [
            'documentUpload' => true,
            'documentExtractedChars' => 0,
            'documentStored' => false,
            'documentContextUsed' => false,
            'documentIdsUsed' => [],
            'documentLabelsUsed' => [],
            'documentResolutionReason' => 'upload',
        ];

        if ($userId <= 0 || $role === '') {
            return [
                'status' => 'error',
                'intent' => 'document_upload',
                'message' => 'Please log in before uploading documents.',
                'actions' => [],
                'needsUserConfirmation' => false,
                'debug' => $debug,
            ];
        }

        $ownerKey = $this->getCre8PilotDocumentOwnerKey($userId);
        if (!$this->cre8PilotEnsureDocumentTree($ownerKey)) {
            return [
                'status' => 'error',
                'intent' => 'document_upload',
                'message' => 'Document storage is not available right now. Please try again later.',
                'actions' => [],
                'needsUserConfirmation' => false,
                'debug' => $debug,
            ];
        }

        $this->cleanupExpiredCre8PilotDocuments($ownerKey);

        if (empty($files['file']) || !is_array($files['file'])) {
            return [
                'status' => 'error',
                'intent' => 'document_upload',
                'message' => 'No file was uploaded.',
                'actions' => [],
                'needsUserConfirmation' => false,
                'debug' => $debug,
            ];
        }

        $err = (int) ($files['file']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            return [
                'status' => 'error',
                'intent' => 'document_upload',
                'message' => 'Upload failed. Please try again with a PDF or TXT file under 5 MB.',
                'actions' => [],
                'needsUserConfirmation' => false,
                'debug' => $debug,
            ];
        }

        $tmp = (string) ($files['file']['tmp_name'] ?? '');
        $origName = (string) ($files['file']['name'] ?? 'upload');
        $size = (int) ($files['file']['size'] ?? 0);
        $max = 5 * 1024 * 1024;

        if ($size <= 0 || $size > $max) {
            return [
                'status' => 'error',
                'intent' => 'document_upload',
                'message' => 'Only PDF and TXT files up to 5 MB are supported.',
                'actions' => [],
                'needsUserConfirmation' => false,
                'debug' => $debug,
            ];
        }

        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return [
                'status' => 'error',
                'intent' => 'document_upload',
                'message' => 'Invalid upload. Please try again.',
                'actions' => [],
                'needsUserConfirmation' => false,
                'debug' => $debug,
            ];
        }

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $blocked = ['php', 'phtml', 'js', 'html', 'htm', 'exe', 'zip', 'docm', 'bat', 'sh'];
        if ($ext === '' || !in_array($ext, ['pdf', 'txt'], true) || in_array($ext, $blocked, true)) {
            return [
                'status' => 'error',
                'intent' => 'document_upload',
                'message' => 'Only PDF and TXT files up to 5 MB are supported.',
                'actions' => [],
                'needsUserConfirmation' => false,
                'debug' => $debug,
            ];
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = (string) @finfo_file($fi, $tmp);
                finfo_close($fi);
            }
        }
        $mimeOk = in_array($mime, ['application/pdf', 'text/plain', 'application/octet-stream', 'text/x-log'], true)
            || ($mime === '' && in_array($ext, ['pdf', 'txt'], true));
        if (!$mimeOk) {
            return [
                'status' => 'error',
                'intent' => 'document_upload',
                'message' => 'Only PDF and TXT files up to 5 MB are supported.',
                'actions' => [],
                'needsUserConfirmation' => false,
                'debug' => $debug,
            ];
        }

        if ($ext === 'txt') {
            $extracted = $this->cre8PilotExtractTxtText($tmp);
        } else {
            $extracted = $this->cre8PilotExtractPdfText($tmp);
        }

        if (empty($extracted['ok'])) {
            return [
                'status' => 'error',
                'intent' => 'document_upload',
                'message' => (string) ($extracted['error'] ?? 'Could not read this file.'),
                'actions' => [],
                'needsUserConfirmation' => false,
                'debug' => $debug,
            ];
        }

        $rawText = (string) ($extracted['text'] ?? '');
        $debug['documentExtractedChars'] = strlen($rawText);
        $compact = $this->cre8PilotNormalizeExtractedText($rawText, 8000);
        $preview = $this->cre8PilotNormalizeExtractedText($rawText, 3000);

        $label = trim((string) ($post['label'] ?? ''));
        $label = $this->sanitizeCre8PilotLlmScalar($label, 200);
        $page = preg_replace('/[^a-z0-9_\\-]/i', '', (string) ($post['page'] ?? 'unknown'));
        $mode = preg_replace('/[^a-z0-9_\\-]/i', '', (string) ($post['mode'] ?? ''));

        $docType = $this->cre8PilotDetectDocumentTypeFromLabel($label, $compact);
        $structured = $this->cre8PilotHeuristicStructuredData($compact);
        $summary = $this->cre8PilotHeuristicSummary($label, $docType, $compact);

        $enriched = $this->cre8PilotLlmEnrichDocumentMeta($compact, $docType, $label);
        if (is_array($enriched)) {
            if (($enriched['summary'] ?? '') !== '') {
                $summary = $this->sanitizeCre8PilotLlmScalar((string) $enriched['summary'], 900);
            }
            if (is_array($enriched['structuredData'] ?? null)) {
                $structured = $enriched['structuredData'];
            }
        }

        $docId = 'doc_' . substr(bin2hex(random_bytes(4)), 0, 8);
        $now = gmdate('c');
        $expires = gmdate('c', time() + 7 * 86400);

        $doc = [
            'docId' => $docId,
            'ownerKey' => $ownerKey,
            'role' => $role,
            'label' => $label !== '' ? $label : ($docType === 'cv' ? 'CV' : 'Document'),
            'docType' => $docType,
            'sourceFileName' => $this->sanitizeCre8PilotLlmScalar($origName, 180),
            'mimeType' => $ext === 'pdf' ? 'application/pdf' : 'text/plain',
            'sizeBytes' => $size,
            'createdAt' => $now,
            'expiresAt' => $expires,
            'pageAtUpload' => $page,
            'modeAtUpload' => $mode,
            'summary' => $summary,
            'structuredData' => $structured,
            'safeTextPreview' => $preview,
            'extractedTextCompact' => $compact,
        ];

        if (!$this->saveCre8PilotDocumentContext($ownerKey, $doc)) {
            return [
                'status' => 'error',
                'intent' => 'document_upload',
                'message' => 'Could not save the document. Please try again.',
                'actions' => [],
                'needsUserConfirmation' => false,
                'debug' => $debug,
            ];
        }

        $this->addDocumentToIndex($ownerKey, $doc);
        $debug['documentStored'] = true;
        $debug['documentIdsUsed'] = [$docId];
        $debug['documentLabelsUsed'] = [$doc['label']];

        $msgLabel = $doc['label'];

        return [
            'status' => 'ok',
            'intent' => 'document_upload',
            'message' => 'I extracted and saved this document as "' . $msgLabel . '". You can ask me to use it later (for example: use my CV, or use the last file).',
            'document' => [
                'docId' => $docId,
                'label' => $msgLabel,
                'docType' => $docType,
                'summary' => $summary,
            ],
            'actions' => [],
            'needsUserConfirmation' => false,
            'avatarState' => 'success',
            'confidence' => 0.9,
            'debug' => $debug,
        ];
    }
}
