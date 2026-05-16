<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Modele/reclamation.php';


class ReclamationC {

    // ✔️ Ajouter une réclamation
    public function ajouterReclamation($reclamation) {
        $sql = "INSERT INTO reclamation 
                (idUtilisateur, description, statut, priorite) 
                VALUES (:idUtilisateur, :description, :statut, :priorite)";
        $db = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'idUtilisateur' => $reclamation->getIdUtilisateur(),
                'description'   => $reclamation->getDescription(),
                'statut'        => $reclamation->getStatut(),
                'priorite'      => $reclamation->getPriorite()
            ]);
        } catch (Exception $e) {
            die('Erreur ajout reclamation: ' . $e->getMessage());
        }
    }
public function afficherReclamationsAvecReponsesUser($idUtilisateur) {
    $sql = "SELECT 
                r.id,
                r.description,
                r.date_creation,
                r.statut,
                r.priorite,
                rep.contenu AS reponse,
                rep.date_reponse,
                rep.idAdmin AS id_admin_reponse,
                admin.nom AS admin_nom
            FROM reclamation r
            LEFT JOIN reponse rep ON r.id = rep.idReclamation
            LEFT JOIN utilisateur admin ON rep.idAdmin = admin.id
            WHERE r.idUtilisateur = :id
            ORDER BY r.date_creation DESC";

    $db = config::getConnexion();
    $req = $db->prepare($sql);
    $req->execute(['id' => $idUtilisateur]);

    return $req->fetchAll();
}
public function modifierReclamation($id, $description, $priorite) {
    $sql = "UPDATE reclamation 
            SET description = :description, priorite = :priorite
            WHERE id = :id";

    $db = config::getConnexion();
    $req = $db->prepare($sql);

    $req->execute([
        'id' => $id,
        'description' => $description,
        'priorite' => $priorite
    ]);
}
public function statistiques() {
    $sql = "SELECT 
                COUNT(*) AS total,
                SUM(statut = 'en_attente') AS en_attente,
                SUM(statut = 'traitee') AS traitee,
                SUM(priorite IN ('haute', 'high')) AS haute,
                SUM(priorite IN ('normale', 'normal', 'moyenne', 'medium')) AS normale,
                SUM(priorite IN ('faible', 'low', 'basse')) AS faible
            FROM reclamation";

    $db = config::getConnexion();
    $stats = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    $stats['moyenne'] = $stats['normale'] ?? 0; // backward-compatible key for old views
    $stats['basse'] = $stats['faible'] ?? 0;    // backward-compatible key for old views
    return $stats;
}

private function generateTimelineDates(int $days): array {
    $dates = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $dates[] = date('Y-m-d', strtotime("-{$i} days"));
    }
    return $dates;
}

public function getReclamationStatusTimeline(int $days = 14): array {
    $sql = "SELECT DATE(date_creation) AS day,
                   SUM(statut = 'en_attente') AS en_attente,
                   SUM(statut = 'traitee') AS traitee
            FROM reclamation
            WHERE date_creation >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(date_creation)
            ORDER BY DATE(date_creation)";

    $db = config::getConnexion();
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':days', $days - 1, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dateMap = [];
    foreach ($rows as $row) {
        $dateMap[$row['day']] = [
            'en_attente' => intval($row['en_attente']),
            'traitee' => intval($row['traitee'])
        ];
    }

    $timeline = [
        'dates' => [],
        'en_attente' => [],
        'traitee' => []
    ];

    foreach ($this->generateTimelineDates($days) as $date) {
        $timeline['dates'][] = $date;
        $timeline['en_attente'][] = $dateMap[$date]['en_attente'] ?? 0;
        $timeline['traitee'][] = $dateMap[$date]['traitee'] ?? 0;
    }

    return $timeline;
}

public function getReclamationPriorityTimeline(int $days = 14): array {
    $sql = "SELECT DATE(date_creation) AS day,
                   SUM(priorite IN ('haute', 'high')) AS haute,
                   SUM(priorite IN ('normale', 'normal', 'moyenne', 'medium')) AS normale,
                   SUM(priorite IN ('faible', 'low', 'basse')) AS faible
            FROM reclamation
            WHERE date_creation >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(date_creation)
            ORDER BY DATE(date_creation)";

    $db = config::getConnexion();
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':days', $days - 1, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dateMap = [];
    foreach ($rows as $row) {
        $dateMap[$row['day']] = [
            'haute' => intval($row['haute']),
            'normale' => intval($row['normale']),
            'faible' => intval($row['faible'])
        ];
    }

    $timeline = [
        'dates' => [],
        'haute' => [],
        'normale' => [],
        'faible' => [],
        // backward-compatible keys for old views
        'moyenne' => [],
        'basse' => []
    ];

    foreach ($this->generateTimelineDates($days) as $date) {
        $normalValue = $dateMap[$date]['normale'] ?? 0;
        $lowValue = $dateMap[$date]['faible'] ?? 0;

        $timeline['dates'][] = $date;
        $timeline['haute'][] = $dateMap[$date]['haute'] ?? 0;
        $timeline['normale'][] = $normalValue;
        $timeline['faible'][] = $lowValue;
        $timeline['moyenne'][] = $normalValue;
        $timeline['basse'][] = $lowValue;
    }

    return $timeline;
}


private function normalizePriorityValues($priorite): array {
    $key = strtolower(trim((string) $priorite));

    if (in_array($key, ['haute', 'high'], true)) {
        return ['haute', 'high'];
    }

    if (in_array($key, ['normale', 'normal', 'moyenne', 'medium'], true)) {
        return ['normale', 'normal', 'moyenne', 'medium'];
    }

    if (in_array($key, ['faible', 'low', 'basse'], true)) {
        return ['faible', 'low', 'basse'];
    }

    return [];
}

private function appendPriorityFilter(string &$sql, array $priorityValues, string $alias = 'r'): array {
    if (empty($priorityValues)) {
        return [];
    }

    $placeholders = [];
    $params = [];
    foreach ($priorityValues as $index => $value) {
        $paramName = ':priority_' . $index;
        $placeholders[] = $paramName;
        $params[$paramName] = $value;
    }

    $sql .= " AND {$alias}.priorite IN (" . implode(', ', $placeholders) . ")";
    return $params;
}

private function bindPriorityParams(PDOStatement $stmt, array $priorityParams): void {
    foreach ($priorityParams as $paramName => $value) {
        $stmt->bindValue($paramName, $value);
    }
}

public function afficherReclamationsAdmin($search = '', $priorite = '', $page = 1, $limit = 10) {
    $priorityValues = $this->normalizePriorityValues($priorite);

    $sql = "SELECT 
                r.id,
                u.nom,
                r.description,
                r.date_creation,
                r.statut,
                r.priorite,
                rep.contenu AS reponse,
                rep.date_reponse
            FROM reclamation r
            JOIN utilisateur u ON r.idUtilisateur = u.id
            LEFT JOIN reponse rep ON r.id = rep.idReclamation
            WHERE 1=1";

    if (!empty($search)) {
        $sql .= " AND (u.nom LIKE :search OR r.description LIKE :search)";
    }

    $priorityParams = $this->appendPriorityFilter($sql, $priorityValues);

    $sql .= " ORDER BY r.date_creation DESC";

    $db = config::getConnexion();

    // Backward compatibility: if the method is called without page/limit, return the raw statement.
    if (func_num_args() < 3) {
        $stmt = $db->prepare($sql);
        if (!empty($search)) {
            $searchTerm = "%$search%";
            $stmt->bindValue(':search', $searchTerm);
        }
        $this->bindPriorityParams($stmt, $priorityParams);
        $stmt->execute();
        return $stmt;
    }

    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM reclamation r
                 JOIN utilisateur u ON r.idUtilisateur = u.id
                 LEFT JOIN reponse rep ON r.id = rep.idReclamation
                 WHERE 1=1";

    if (!empty($search)) {
        $countSql .= " AND (u.nom LIKE :search OR r.description LIKE :search)";
    }

    $countPriorityParams = $this->appendPriorityFilter($countSql, $priorityValues);

    $countStmt = $db->prepare($countSql);

    if (!empty($search)) {
        $searchTerm = "%$search%";
        $countStmt->bindValue(':search', $searchTerm);
    }

    $this->bindPriorityParams($countStmt, $countPriorityParams);

    $countStmt->execute();
    $totalRecords = intval($countStmt->fetchColumn());

    // Add LIMIT and OFFSET
    $offset = ($page - 1) * $limit;
    $sql .= " LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);

    if (!empty($search)) {
        $stmt->bindValue(':search', $searchTerm);
    }

    $this->bindPriorityParams($stmt, $priorityParams);

    $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

    $stmt->execute();

    return [
        'stmt' => $stmt,
        'total' => $totalRecords,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => max(1, (int) ceil($totalRecords / max(1, $limit)))
    ];
}
    // ✔️ Afficher toutes les réclamations avec jointure
    public function afficherReclamations() {
        $sql = "SELECT 
                    r.id,
                    u.nom AS utilisateur_nom,
                    u.email,
                    r.description,
                    r.date_creation,
                    r.statut,
                    r.priorite
                FROM reclamation r
                JOIN utilisateur u ON r.idUtilisateur = u.id
                ORDER BY r.date_creation DESC";

        $db = config::getConnexion();
        return $db->query($sql);
    }

    // ✔️ Supprimer
    public function supprimerReclamation($id) {
        $sql = "DELETE FROM reclamation WHERE id = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->bindValue(':id', $id);
        $req->execute();
    }

    // ✔️ Modifier statut (ex: traitée)
    public function updateStatut($id, $statut) {
        $sql = "UPDATE reclamation SET statut = :statut WHERE id = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute([
            'statut' => $statut,
            'id'     => $id
        ]);
    }

    // ✔️ Récupérer une réclamation
    public function recupererReclamation($id) {
        $sql = "SELECT * FROM reclamation WHERE id = :id";
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->execute(['id' => $id]);
        return $req->fetch();
    }
}