<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "cartonaire";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

$content = file_get_contents("php://input");
error_log("Received content: " . $content);
$data = json_decode($content, true);
error_log("Decoded JSON: " . print_r($data, true));

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["error" => "Invalid JSON data: " . json_last_error_msg()]);
    exit;
}

if (!isset($data)) {
    echo json_encode(["error" => "No data received"]);
    exit;
}

$action = $data["action"] ?? null;

if ($action === "sendMessage") {
    $id_conversation = $_POST['id_conversation'];
    $idEnvoyeur = $_POST['idEnvoyeur'];
    $message = $_POST['message'];

    $stmt = $conn->prepare("INSERT INTO messagerie (id_envoyeur, id_conversation, contenu) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $idEnvoyeur, $id_conversation, $message);
    if ($stmt->execute()) {
        echo json_encode(["message" => "Message sent successfully"]);
    } else {
        echo json_encode(["error" => "Error sending message: " . $stmt->error]);
    }
    $stmt->close();
} elseif ($action === "getAllUsers") {
    $sql = "SELECT id_user, nom, prenom FROM user WHERE id_entreprise = 1";
    $result = $conn->query($sql);
    $users = [];

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode($users);
    } else {
        echo json_encode(["error" => "No users found"]);
    }
}elseif ($action === "getConversations") {
    $userId = $data["userId"] ?? 0;
    if ($userId) {
        $stmt = $conn->prepare("SELECT c.id_conversation, c.titre FROM conversation c JOIN participation p ON c.id_conversation = p.id_conversation WHERE p.id_user = ?");
        if (!$stmt) {
            error_log("SQL prepare error: " . $conn->error); // Log SQL preparation errors
            echo "<p>SQL Error: " . htmlspecialchars($conn->error) . "</p>";
            exit;
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $conversations = [];
            while ($row = $result->fetch_assoc()) {
                $conversations[] = $row;
            }
            echo json_encode($conversations);
        } else {
            echo json_encode(["error" => "No conversations found"]);
        }
    } else {
        echo json_encode(["error" => "User ID not provided"]);
    }
}
 elseif ($action === "addConversation") {
    $data = json_decode(file_get_contents("php://input"), true); // Récupération des données en JSON
    
    $idEnvoyeur = $data['idEnvoyeur'] ?? null;
    $idReceveur = $data['idReceveur'] ?? null;
    $titre = $data['titre'] ?? 'Conversation sans titre';

    if (!$idEnvoyeur) {
        echo json_encode(["error" => "ID de l'envoyeur manquant"]);
        exit;
    }
    if (!$idReceveur) {
        echo json_encode(["error" => "ID du receveur manquant"]);
        exit;
    }

    // Créer une nouvelle conversation
    $stmt = $conn->prepare("INSERT INTO conversation (titre) VALUES (?)");
    if (!$stmt) {
        echo json_encode(["error" => "SQL Error: " . $conn->error]);
        exit;
    }
    $stmt->bind_param("s", $titre);
    $stmt->execute();
    $id_conversation = $conn->insert_id;

    // Associer l'envoyeur et le receveur à la conversation
    $stmt = $conn->prepare("INSERT INTO participation (id_conversation, id_user) VALUES (?, ?), (?, ?)");
    $stmt->bind_param("iiii", $id_conversation, $idEnvoyeur, $id_conversation, $idReceveur);
    if ($stmt->execute()) {
        echo json_encode(["message" => "Conversation créée avec succès", "id_conversation" => $id_conversation, "titre" => $titre]);
    } else {
        echo json_encode(["error" => "Erreur lors de l'exécution de la requête: " . $stmt->error]);
    }
    $stmt->close();
}


 elseif ($action === "getMessages") {
    $idEnvoyeur = $data["idEnvoyeur"] ?? 0;
    $idReceveur = $data["idReceveur"] ?? 0;

    $sql = "SELECT * FROM messagerie WHERE (id_envoyeur = ? AND id_receveur = ?) OR (id_envoyeur = ? AND id_receveur = ?) ORDER BY heure ASC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["error" => "SQL Error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("iiii", $idEnvoyeur, $idReceveur, $idReceveur, $idEnvoyeur);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    echo json_encode($messages);
    $stmt->close();
} elseif ($action === "login") {
    $identifiant = $data["identifiant"] ?? '';
    $mot_de_passe = $data["mot_de_passe"] ?? '';

    $stmt = $conn->prepare("SELECT id_user, identifiant, id_entreprise FROM user WHERE identifiant = ? AND mot_de_passe = ?");
    if (!$stmt) {
        echo json_encode(["error" => "SQL Error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ss", $identifiant, $mot_de_passe);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "message" => "Connexion réussie", 
            "userId" => $row['id_user'],
            "userEnterpriseId" => $row['id_entreprise']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Identifiants invalides"]);
    }

    $stmt->close();

} elseif ($action === "addCompany") {
    $name = $data["name"] ?? '';
    $address = $data["address"] ?? '';
    $phone = $data["phone"] ?? '';

    $stmt = $conn->prepare("INSERT INTO entreprise (nom, adresse, numero_telephone) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo json_encode(["error" => "SQL Error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("sss", $name, $address, $phone);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Entreprise ajoutée avec succès"]);
    } else {
        echo json_encode(["error" => "Erreur lors de l'ajout de l'entreprise: " . $stmt->error]);
    }

    $stmt->close();
} elseif ($action === "addReservation") {
    $userId = $_POST["userId"] ?? null;
    $adresse = $_POST["adresse"] ?? '';
    $heure = $_POST["heure"] ?? '';
    $quantite = $_POST["quantite"] ?? 0;
    $uploadedFile = '';

    if (!empty($_FILES["photo"]["tmp_name"])) {
        $uploadDir = 'uploads/';
        $uploadedFile = $uploadDir . basename($_FILES["photo"]["name"]);
        if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $uploadedFile)) {
            echo json_encode(["error" => "Échec du téléchargement du fichier"]);
            exit;
        }
    }

    if (!$userId || !$adresse || !$heure || $quantite < 1) {
        echo json_encode(["error" => "Données de réservation incomplètes ou incorrectes"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO reservations (user_id, adresse, heure, quantite, photo) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(["error" => "Erreur SQL: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("issis", $userId, $adresse, $heure, $quantite, $uploadedFile);
    if ($stmt->execute()) {
        echo json_encode(["message" => "Réservation ajoutée avec succès"]);
    } else {
        echo json_encode(["error" => "Erreur lors de l'ajout de la réservation: " . $stmt->error]);
    }
    $stmt->close();
}


 elseif ($action === "loadProfile") {
    $userId = $data["userId"] ?? null;
    if ($userId) {
        $stmt = $conn->prepare("SELECT u.nom, u.prenom, e.nom, e.adresse, e.numero_telephone FROM user u INNER JOIN entreprise e ON u.id_entreprise = e.id_entreprise WHERE u.id_user = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            echo json_encode($user);
        } else {
            echo json_encode(["error" => "Profil non trouvé"]);
        }
    } else {
        echo json_encode(["error" => "Utilisateur non identifié"]);
    }
} elseif ($action === "updateProfile") {
    $userId = $data["userId"] ?? null;
    $username = $data["username"] ?? '';
    $email = $data["email"] ?? '';
    if ($userId) {
        $stmt = $conn->prepare("UPDATE user SET username = ?, email = ? WHERE id_user = ?");
        $stmt->bind_param("ssi", $username, $email, $userId);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Profil mis à jour avec succès"]);
        } else {
            echo json_encode(["error" => "Erreur lors de la mise à jour"]);
        }
    } else {
        echo json_encode(["error" => "Utilisateur non identifié"]);
    }
} elseif ($action === "getUserSubscription") {
    $userId = $data["userId"] ?? null;
    if ($userId) {
        // Préparation de la requête pour récupérer les informations d'abonnement
        $stmt = $conn->prepare("SELECT a.type, a.description, a.prix, u.id_entreprise FROM user u INNER JOIN abonnement a ON u.abonnement_id = a.id INNER JOIN trajet t ON u.id_entreprise = t.entreprise_id WHERE u.id_user = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($abonnement = $result->fetch_assoc()) {
            $stmt2 = $conn->prepare("SELECT COUNT(*) as trip_count, SUM(nbr_cartons) as totalCartons FROM trajet WHERE entreprise_id = ?");
            $stmt2->bind_param("i", $abonnement['id_entreprise']);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $trips = $result2->fetch_assoc();

            // Envoyer les données d'abonnement et le nombre de trajets et de cartons
            echo json_encode([
                "abonnement" => [
                    "type" => $abonnement["type"],
                    "description" => $abonnement["description"],
                    "prix" => $abonnement["prix"]
                ],
                "trips" => $trips["trip_count"],
                "totalCartons" => $trips["totalCartons"]
            ]);
        } else {
            echo json_encode(["error" => "Abonnement non trouvé"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["error" => "ID utilisateur non fourni"]);
    }
}

elseif ($action === "getUserInfo") {
    $userId = $data["userId"] ?? null;
    if ($userId) {
        $stmt = $conn->prepare("SELECT id_entreprise FROM user WHERE id_user = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            echo json_encode(["userEnterpriseId" => $user['id_entreprise']]);
        } else {
            echo json_encode(["error" => "Utilisateur non trouvé"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["error" => "ID utilisateur non fourni"]);
    }
} elseif ($action === "getAddress") {
    $userId = $data["userId"] ?? null;
    if ($userId) {
        $stmt = $conn->prepare("SELECT e.adresse FROM user u JOIN entreprise e ON u.id_entreprise = e.id_entreprise WHERE u.id_user = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode(["adresse" => $row['adresse']]);
        } else {
            echo json_encode(["error" => "Adresse not found"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["error" => "User ID not provided"]);
    }
}

elseif ($action === "getNextPassages") {
    $userId = $data["userId"] ?? 0;
    if ($userId) {
        $sql = "SELECT adresse, date, heure, photo FROM reservations WHERE user_id = ? AND date >= CURDATE() ORDER BY date ASC, heure ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["error" => "Erreur SQL: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $passages = [];
        while ($row = $result->fetch_assoc()) {
            $passages[] = $row;
        }
        echo json_encode($passages);
    } else {
        echo json_encode(["error" => "ID utilisateur non fourni"]);
    }
}

elseif ($action === "getLastPassages") {
    $userId = $data["userId"] ?? 0;
    if ($userId) {
        $sql = "SELECT adresse, date, heure, quantite, photo FROM historique_passages WHERE id_user = ? ORDER BY date DESC, heure DESC LIMIT 9";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["error" => "Erreur SQL: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $passages = [];
        while ($row = $result->fetch_assoc()) {
            $passages[] = $row;
        }
        echo json_encode($passages);
    } else {
        echo json_encode(["error" => "ID utilisateur non fourni"]);
    }
}

elseif ($action === "getTodayReservations") {
    $userId = $data["userId"] ?? 0;
    $date = $data["date"] ?? date('Y-m-d'); // Utilise la date envoyée
    $sql = "SELECT adresse, date, heure, photo FROM reservations WHERE date = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["error" => "Erreur SQL: " . $conn->error]);
        exit;
    }
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
    echo json_encode($reservations);
}


$conn->close();
?>