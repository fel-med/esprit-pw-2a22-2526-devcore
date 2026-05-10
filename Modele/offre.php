<?php

class Offre
{
    private const META_PATTERN = '/\s*<!--cre8connect-offre-meta:(.*?)-->\s*$/s';

    private $idOffre;
    private $idMarque;
    private $idCreateurCible;
    private $titre;
    private $description;
    private $objectif;
    private $budgetPropose;
    private $datePublication;
    private $dateLimite;
    private $statutOffre;
    private $raisonChoix;
    private $messagePersonnalise;
    private $attenteCollaboration;
    private $draftSansCreateur;

    public function __construct(
        $idOffre = null,
        $idMarque = null,
        $idCreateurCible = null,
        $titre = null,
        $description = null,
        $objectif = null,
        $budgetPropose = null,
        $datePublication = null,
        $dateLimite = null,
        $statutOffre = null,
        $raisonChoix = null,
        $messagePersonnalise = null,
        $attenteCollaboration = null,
        $draftSansCreateur = null
    ) {
        $this->idOffre = $idOffre;
        $this->idMarque = $idMarque;
        $this->idCreateurCible = $idCreateurCible;
        $this->titre = $titre;
        $this->objectif = $objectif;
        $this->budgetPropose = $budgetPropose;
        $this->datePublication = $datePublication;
        $this->dateLimite = $dateLimite;
        $this->statutOffre = $statutOffre;
        $this->raisonChoix = '';
        $this->messagePersonnalise = '';
        $this->attenteCollaboration = '';
        $this->draftSansCreateur = false;

        $this->setDescription($description);

        if ($raisonChoix !== null && trim((string) $raisonChoix) !== '') {
            $this->setRaisonChoix($raisonChoix);
        }
        if ($messagePersonnalise !== null && trim((string) $messagePersonnalise) !== '') {
            $this->setMessagePersonnalise($messagePersonnalise);
        }
        if ($attenteCollaboration !== null && trim((string) $attenteCollaboration) !== '') {
            $this->setAttenteCollaboration($attenteCollaboration);
        }
        if ($draftSansCreateur !== null) {
            $this->setDraftSansCreateur($draftSansCreateur);
        }
    }

    public function getIdOffre()
    {
        return $this->idOffre;
    }

    public function setIdOffre($idOffre)
    {
        $this->idOffre = $idOffre;
    }

    public function getIdMarque()
    {
        return $this->idMarque;
    }

    public function setIdMarque($idMarque)
    {
        $this->idMarque = $idMarque;
    }

    public function getIdCreateurCible()
    {
        return $this->idCreateurCible;
    }

    public function setIdCreateurCible($idCreateurCible)
    {
        $this->idCreateurCible = $idCreateurCible;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function setTitre($titre)
    {
        $this->titre = $titre;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $parts = $this->splitDescriptionPayload($description);
        $this->description = $parts['description'];
        $this->raisonChoix = $parts['raisonChoix'];
        $this->messagePersonnalise = $parts['messagePersonnalise'];
        $this->attenteCollaboration = $parts['attenteCollaboration'];
        $this->draftSansCreateur = $parts['draftSansCreateur'];
    }

    public function getDescriptionForStorage()
    {
        return trim((string) $this->description);
    }

    public function getObjectif()
    {
        return $this->objectif;
    }

    public function setObjectif($objectif)
    {
        $this->objectif = $objectif;
    }

    public function getBudgetPropose()
    {
        return $this->budgetPropose;
    }

    public function setBudgetPropose($budgetPropose)
    {
        $this->budgetPropose = $budgetPropose;
    }

    public function getBudgetMin()
    {
        return $this->budgetPropose;
    }

    public function setBudgetMin($budgetMin)
    {
        $this->budgetPropose = $budgetMin;
    }

    public function getBudgetMax()
    {
        return $this->budgetPropose;
    }

    public function setBudgetMax($budgetMax)
    {
        $this->budgetPropose = $budgetMax;
    }

    public function getDatePublication()
    {
        return $this->datePublication;
    }

    public function setDatePublication($datePublication)
    {
        $this->datePublication = $datePublication;
    }

    public function getDateLimite()
    {
        return $this->dateLimite;
    }

    public function setDateLimite($dateLimite)
    {
        $this->dateLimite = $dateLimite;
    }

    public function getStatutOffre()
    {
        return $this->statutOffre;
    }

    public function setStatutOffre($statutOffre)
    {
        $this->statutOffre = $statutOffre;
    }

    public function isPendingPublication($referenceDate = 'today')
    {
        if ((string) $this->statutOffre !== 'publiee') {
            return false;
        }

        $publicationDate = $this->normalizeComparableDate($this->datePublication);
        $comparisonDate = $this->normalizeComparableDate($referenceDate);

        if ($publicationDate === null || $comparisonDate === null) {
            return false;
        }

        return $publicationDate > $comparisonDate;
    }

    public function isLivePublication($referenceDate = 'today')
    {
        return (string) $this->statutOffre === 'publiee' && !$this->isPendingPublication($referenceDate);
    }

    public function getDisplayStatusKey($referenceDate = 'today')
    {
        return $this->isPendingPublication($referenceDate) ? 'pending' : (string) $this->statutOffre;
    }

    public function getRaisonChoix()
    {
        return $this->raisonChoix;
    }

    public function setRaisonChoix($raisonChoix)
    {
        $this->raisonChoix = trim((string) $raisonChoix);
    }

    public function getMessagePersonnalise()
    {
        return $this->messagePersonnalise;
    }

    public function setMessagePersonnalise($messagePersonnalise)
    {
        $this->messagePersonnalise = trim((string) $messagePersonnalise);
    }

    public function getAttenteCollaboration()
    {
        return $this->attenteCollaboration;
    }

    public function setAttenteCollaboration($attenteCollaboration)
    {
        $this->attenteCollaboration = trim((string) $attenteCollaboration);
    }

    public function hasCollaborationBrief()
    {
        return $this->raisonChoix !== '' || $this->messagePersonnalise !== '' || $this->attenteCollaboration !== '';
    }

    public function isDraftSansCreateur()
    {
        return $this->draftSansCreateur;
    }

    public function setDraftSansCreateur($draftSansCreateur)
    {
        $this->draftSansCreateur = (bool) $draftSansCreateur;
    }

    private function splitDescriptionPayload($value)
    {
        $description = trim((string) $value);
        $meta = [
            'raisonChoix' => '',
            'messagePersonnalise' => '',
            'attenteCollaboration' => '',
            'draftSansCreateur' => false,
        ];

        if ($description !== '' && preg_match(self::META_PATTERN, $description, $matches)) {
            $decoded = json_decode(base64_decode(trim($matches[1])), true);
            if (is_array($decoded)) {
                $meta['raisonChoix'] = trim((string) ($decoded['raisonChoix'] ?? ''));
                $meta['messagePersonnalise'] = trim((string) ($decoded['messagePersonnalise'] ?? ''));
                $meta['attenteCollaboration'] = trim((string) ($decoded['attenteCollaboration'] ?? ''));
                $meta['draftSansCreateur'] = !empty($decoded['draftSansCreateur']);
                $description = trim((string) preg_replace(self::META_PATTERN, '', $description));
            }
        }

        return [
            'description' => $description,
            'raisonChoix' => $meta['raisonChoix'],
            'messagePersonnalise' => $meta['messagePersonnalise'],
            'attenteCollaboration' => $meta['attenteCollaboration'],
            'draftSansCreateur' => $meta['draftSansCreateur'],
        ];
    }

    private function normalizeComparableDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $strictDate = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($strictDate && $strictDate->format('Y-m-d') === $value) {
            return $strictDate->format('Y-m-d');
        }

        try {
            return (new DateTimeImmutable($value))->format('Y-m-d');
        } catch (Exception $exception) {
            return null;
        }
    }

    public static function fromArray(array $data)
    {
        return new self(
            $data['idOffre'] ?? null,
            $data['idMarque'] ?? null,
            $data['idCreateurCible'] ?? null,
            $data['titre'] ?? null,
            $data['description'] ?? null,
            $data['objectif'] ?? null,
            $data['budgetPropose'] ?? ($data['budgetMin'] ?? $data['budgetMax'] ?? null),
            $data['datePublication'] ?? null,
            $data['dateLimite'] ?? null,
            $data['statutOffre'] ?? null,
            $data['raisonChoix'] ?? null,
            $data['messagePersonnalise'] ?? null,
            $data['attenteCollaboration'] ?? null,
            $data['draftSansCreateur'] ?? null
        );
    }
}

?>
