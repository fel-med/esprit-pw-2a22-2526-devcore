<?php

class Condidature
{
    private const DECISION_META_PATTERN = '/\s*<!--cre8connect-condidature-meta:(.*?)-->\s*$/s';
    private const MESSAGE_META_PATTERN = '/\s*<!--cre8connect-condidature-form-meta:(.*?)-->\s*$/s';

    private $idCandidature;
    private $idCreateur;
    private $origineCandidature;
    private $idSource;
    private $dateCandidature;
    private $statutCandidature;
    private $messageMotivation;
    private $budgetPropose;
    private $delaiPropose;
    private $noteDecision;
    private $dateDerniereModification;
    private $dateDecision;
    private $responseMode;
    private $dateDisponibilite;
    private $conditionsCreateur;
    private $cvPath;
    private $portfolioUrl;
    private $motifRefus;

    public function __construct(
        $idCandidature = null,
        $idCreateur = null,
        $origineCandidature = null,
        $idSource = null,
        $dateCandidature = null,
        $statutCandidature = null,
        $messageMotivation = null,
        $budgetPropose = null,
        $delaiPropose = null,
        $noteDecision = null,
        $dateDerniereModification = null,
        $dateDecision = null,
        $responseMode = null,
        $dateDisponibilite = null,
        $conditionsCreateur = null,
        $cvPath = null,
        $portfolioUrl = null,
        $motifRefus = null
    ) {
        $this->idCandidature = $idCandidature;
        $this->idCreateur = $idCreateur;
        $this->origineCandidature = $origineCandidature ?: 'par_offre';
        $this->idSource = $idSource;
        $this->dateCandidature = $dateCandidature;
        $this->statutCandidature = 'brouillon';
        $this->messageMotivation = '';
        $this->budgetPropose = $budgetPropose;
        $this->delaiPropose = $delaiPropose;
        $this->noteDecision = '';
        $this->dateDerniereModification = $dateDerniereModification;
        $this->dateDecision = $dateDecision;
        $this->responseMode = null;
        $this->dateDisponibilite = '';
        $this->conditionsCreateur = '';
        $this->cvPath = '';
        $this->portfolioUrl = '';
        $this->motifRefus = '';

        $this->setStatutCandidature($statutCandidature);
        $this->setMessageMotivation($messageMotivation);
        $this->setNoteDecision($noteDecision);
        $this->setResponseMode($responseMode !== null ? $responseMode : $this->responseMode);
        $this->setDateDisponibilite($dateDisponibilite !== null ? $dateDisponibilite : $this->dateDisponibilite);
        $this->setConditionsCreateur($conditionsCreateur !== null ? $conditionsCreateur : $this->conditionsCreateur);
        $this->setCvPath($cvPath !== null ? $cvPath : $this->cvPath);
        $this->setPortfolioUrl($portfolioUrl !== null ? $portfolioUrl : $this->portfolioUrl);
        $this->setMotifRefus($motifRefus !== null ? $motifRefus : $this->motifRefus);
    }

    public function getIdCandidature()
    {
        return $this->idCandidature;
    }

    public function setIdCandidature($idCandidature)
    {
        $this->idCandidature = $idCandidature;
    }

    public function getIdCreateur()
    {
        return $this->idCreateur;
    }

    public function setIdCreateur($idCreateur)
    {
        $this->idCreateur = $idCreateur;
    }

    public function getOrigineCandidature()
    {
        return $this->origineCandidature;
    }

    public function setOrigineCandidature($origineCandidature)
    {
        $this->origineCandidature = trim((string) $origineCandidature) ?: 'par_offre';
    }

    public function getIdSource()
    {
        return $this->idSource;
    }

    public function setIdSource($idSource)
    {
        $this->idSource = $idSource;
    }

    public function getDateCandidature()
    {
        return $this->dateCandidature;
    }

    public function setDateCandidature($dateCandidature)
    {
        $this->dateCandidature = $dateCandidature;
    }

    public function getStatutCandidature()
    {
        return $this->statutCandidature;
    }

    public function setStatutCandidature($statutCandidature)
    {
        $this->statutCandidature = trim((string) $statutCandidature) ?: 'brouillon';
        if ($this->responseMode === null) {
            $this->responseMode = $this->resolveResponseModeFromStatus($this->statutCandidature);
        }
    }

    public function getMessageMotivation()
    {
        return $this->messageMotivation;
    }

    public function setMessageMotivation($messageMotivation)
    {
        $parts = $this->splitMessagePayload($messageMotivation);
        $this->messageMotivation = $parts['messageMotivation'];

        foreach ($parts['meta'] as $field => $value) {
            switch ($field) {
                case 'dateDisponibilite':
                    $this->setDateDisponibilite($value);
                    break;
                case 'conditionsCreateur':
                    $this->setConditionsCreateur($value);
                    break;
                case 'cvPath':
                    $this->setCvPath($value);
                    break;
                case 'portfolioUrl':
                    $this->setPortfolioUrl($value);
                    break;
                case 'motifRefus':
                    $this->setMotifRefus($value);
                    break;
            }
        }
    }

    public function getMessageMotivationForStorage()
    {
        $messageMotivation = trim((string) $this->messageMotivation);
        $meta = array_filter([
            'dateDisponibilite' => $this->getDateDisponibilite(),
            'conditionsCreateur' => $this->getConditionsCreateur(),
            'cvPath' => $this->getCvPath(),
            'portfolioUrl' => $this->getPortfolioUrl(),
            'motifRefus' => $this->getMotifRefus(),
        ], static fn($value) => $value !== null && trim((string) $value) !== '');

        if (empty($meta)) {
            return $messageMotivation;
        }

        $payload = base64_encode(json_encode($meta, JSON_UNESCAPED_UNICODE));

        return $messageMotivation === ''
            ? '<!--cre8connect-condidature-form-meta:' . $payload . '-->'
            : $messageMotivation . "\n\n<!--cre8connect-condidature-form-meta:" . $payload . "-->";
    }

    public function getBudgetPropose()
    {
        return $this->budgetPropose;
    }

    public function setBudgetPropose($budgetPropose)
    {
        $this->budgetPropose = $budgetPropose;
    }

    public function getDelaiPropose()
    {
        return $this->delaiPropose;
    }

    public function setDelaiPropose($delaiPropose)
    {
        $this->delaiPropose = $delaiPropose;
    }

    public function getNoteDecision()
    {
        return $this->noteDecision;
    }

    public function setNoteDecision($noteDecision)
    {
        $parts = $this->splitNoteDecisionPayload($noteDecision);
        $this->noteDecision = $parts['noteDecision'];

        if ($parts['responseMode'] !== null) {
            $this->responseMode = $parts['responseMode'];
        }
    }

    public function getNoteDecisionForStorage()
    {
        $noteDecision = trim((string) $this->noteDecision);
        $meta = array_filter([
            'responseMode' => $this->getResponseMode(),
            'typeReponse' => $this->getTypeReponse(),
        ], static fn($value) => $value !== null && $value !== '');

        if (empty($meta)) {
            return $noteDecision;
        }

        $payload = base64_encode(json_encode($meta, JSON_UNESCAPED_UNICODE));

        return $noteDecision === ''
            ? '<!--cre8connect-condidature-meta:' . $payload . '-->'
            : $noteDecision . "\n\n<!--cre8connect-condidature-meta:" . $payload . "-->";
    }

    public function getDateDerniereModification()
    {
        return $this->dateDerniereModification;
    }

    public function setDateDerniereModification($dateDerniereModification)
    {
        $this->dateDerniereModification = $dateDerniereModification;
    }

    public function getDateDecision()
    {
        return $this->dateDecision;
    }

    public function setDateDecision($dateDecision)
    {
        $this->dateDecision = $dateDecision;
    }

    public function getResponseMode()
    {
        return $this->responseMode ?: $this->resolveResponseModeFromStatus($this->statutCandidature);
    }

    public function setResponseMode($responseMode)
    {
        $normalized = $this->normalizeResponseMode($responseMode);
        $this->responseMode = $normalized ?: $this->resolveResponseModeFromStatus($this->statutCandidature);
    }

    public function getDateDisponibilite()
    {
        return $this->dateDisponibilite;
    }

    public function setDateDisponibilite($dateDisponibilite)
    {
        $this->dateDisponibilite = trim((string) $dateDisponibilite);
    }

    public function getConditionsCreateur()
    {
        return $this->conditionsCreateur;
    }

    public function setConditionsCreateur($conditionsCreateur)
    {
        $this->conditionsCreateur = trim((string) $conditionsCreateur);
    }

    public function getCvPath()
    {
        return $this->cvPath;
    }

    public function setCvPath($cvPath)
    {
        $this->cvPath = trim((string) $cvPath);
    }

    public function getPortfolioUrl()
    {
        return $this->portfolioUrl;
    }

    public function setPortfolioUrl($portfolioUrl)
    {
        $this->portfolioUrl = trim((string) $portfolioUrl);
    }

    public function getMotifRefus()
    {
        return $this->motifRefus;
    }

    public function setMotifRefus($motifRefus)
    {
        $this->motifRefus = trim((string) $motifRefus);
    }

    public function getTypeReponse()
    {
        return match ($this->getResponseMode()) {
            'negotiate' => 'negociation',
            'decline' => 'refus',
            default => 'acceptation',
        };
    }

    public function getResponseTypeLabel()
    {
        if ($this->getResponseMode() === 'negotiate' && $this->statutCandidature === 'acceptee') {
            return 'Negotiated agreement';
        }

        return match ($this->getResponseMode()) {
            'negotiate' => 'Negotiation request',
            'decline' => 'Decline response',
            default => 'Acceptance response',
        };
    }

    public function getDisplayStatusLabel()
    {
        $mode = $this->getResponseMode();

        return match ($this->statutCandidature) {
            'brouillon' => $mode === 'negotiate'
                ? 'Negotiation draft'
                : ($mode === 'decline' ? 'Decline draft' : 'Response draft'),
            'envoyee' => 'Accepted invitation',
            'en_etude' => $mode === 'negotiate' ? 'Negotiation under review' : 'Response under review',
            'negociation' => 'Negotiation requested',
            'acceptee' => 'Accepted terms',
            'refusee' => 'Refused by brand',
            'retiree' => 'Declined invitation',
            default => ucwords(str_replace('_', ' ', (string) $this->statutCandidature)),
        };
    }

    public function isDraft()
    {
        return $this->statutCandidature === 'brouillon';
    }

    public function isNegotiation()
    {
        return $this->statutCandidature === 'negociation';
    }

    public function isReviewLocked()
    {
        return in_array($this->statutCandidature, ['en_etude', 'acceptee', 'refusee', 'retiree'], true);
    }

    public function isCreatorLocked()
    {
        return in_array($this->statutCandidature, ['envoyee', 'en_etude', 'acceptee', 'refusee', 'retiree'], true);
    }

    public function canCreatorEdit()
    {
        return in_array($this->statutCandidature, ['brouillon', 'negociation'], true);
    }

    public function canCreatorEditEverything()
    {
        return $this->statutCandidature === 'brouillon';
    }

    public function canCreatorEditNegotiationOnly()
    {
        return $this->statutCandidature === 'negociation';
    }

    public function canCreatorFinalizeNegotiation()
    {
        return $this->statutCandidature === 'negociation';
    }

    public function isFinal()
    {
        return in_array($this->statutCandidature, ['acceptee', 'refusee', 'retiree'], true);
    }

    public function isOfferResponse()
    {
        return $this->origineCandidature === 'par_offre';
    }

    public function isCampaignResponse()
    {
        return $this->origineCandidature === 'par_campagne';
    }

    public function canBrandNegotiate()
    {
        return in_array($this->statutCandidature, ['envoyee', 'en_etude', 'negociation'], true);
    }

    public function canBrandDecide()
    {
        return in_array($this->statutCandidature, ['envoyee', 'en_etude', 'negociation'], true);
    }

    private function splitMessagePayload($value)
    {
        $messageMotivation = trim((string) $value);
        $meta = [];

        if ($messageMotivation !== '' && preg_match(self::MESSAGE_META_PATTERN, $messageMotivation, $matches)) {
            $decoded = json_decode(base64_decode(trim($matches[1])), true);
            if (is_array($decoded)) {
                $meta = $decoded;
                $messageMotivation = trim((string) preg_replace(self::MESSAGE_META_PATTERN, '', $messageMotivation));
            }
        }

        return [
            'messageMotivation' => $messageMotivation,
            'meta' => $meta,
        ];
    }

    private function splitNoteDecisionPayload($value)
    {
        $noteDecision = trim((string) $value);
        $responseMode = null;

        if ($noteDecision !== '' && preg_match(self::DECISION_META_PATTERN, $noteDecision, $matches)) {
            $decoded = json_decode(base64_decode(trim($matches[1])), true);
            if (is_array($decoded)) {
                $responseMode = $this->normalizeResponseMode($decoded['responseMode'] ?? null);
                $noteDecision = trim((string) preg_replace(self::DECISION_META_PATTERN, '', $noteDecision));
            }
        }

        return [
            'noteDecision' => $noteDecision,
            'responseMode' => $responseMode,
        ];
    }

    private function normalizeResponseMode($responseMode)
    {
        $responseMode = strtolower(trim((string) $responseMode));

        return in_array($responseMode, ['accept', 'negotiate', 'decline'], true) ? $responseMode : null;
    }

    private function resolveResponseModeFromStatus($status)
    {
        return match (trim((string) $status)) {
            'negociation' => 'negotiate',
            'retiree' => 'decline',
            default => 'accept',
        };
    }

    public static function fromArray(array $data)
    {
        return new self(
            $data['idCandidature'] ?? null,
            $data['idCreateur'] ?? null,
            $data['origineCandidature'] ?? ($data['idOffre'] ?? null ? 'par_offre' : null),
            $data['idSource'] ?? ($data['idOffre'] ?? null),
            $data['dateCandidature'] ?? null,
            $data['statutCandidature'] ?? null,
            $data['messageMotivation'] ?? null,
            $data['budgetPropose'] ?? null,
            $data['delaiPropose'] ?? null,
            $data['noteDecision'] ?? null,
            $data['dateDerniereModification'] ?? null,
            $data['dateDecision'] ?? null,
            $data['responseMode'] ?? null,
            $data['dateDisponibilite'] ?? null,
            $data['conditionsCreateur'] ?? null,
            $data['cvPath'] ?? null,
            $data['portfolioUrl'] ?? null,
            $data['motifRefus'] ?? null
        );
    }
}

?>
