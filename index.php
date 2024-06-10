<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

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
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

$content = file_get_contents("php://input");
$data = json_decode($content, true);

if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["error" => "Invalid JSON data"]);
    exit;
}

$action = $data["action"] ?? null;

if ($action === "sendMessage") {
    $message = $data["message"] ?? '';
    $idReceveur = $data["idReceveur"] ?? 0;
    $idEnvoyeur = 1; // Supposons que cet ID soit défini par la session utilisateur ou un autre moyen

    $stmt = $conn->prepare("INSERT INTO messagerie (id_envoyeur, id_receveur, contenu) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo json_encode(["error" => "SQL Error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("iis", $idEnvoyeur, $idReceveur, $message);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Message sent successfully"]);
    } else {
        echo json_encode(["error" => "Error sending message: " . $stmt->error]);
    }

    $stmt->close();

} elseif ($action === "getConversations") {
    $idEnvoyeur = 1; // Idem, supposons que cet ID soit connu

    $sql = "SELECT DISTINCT id_receveur FROM messagerie WHERE id_envoyeur = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["error" => "SQL Error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $idEnvoyeur);
    $stmt->execute();
    $result = $stmt->get_result();

    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = $row['id_receveur'];
    }

    echo json_encode($conversations);
    
} elseif ($action === "getMessages") {
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
            "userEnterpriseId" => $row['id_entreprise'] // Assurez-vous que cette colonne est récupérée
        ]);
    } else {
        http_response_code(401); // Non autorisé
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
            die(json_encode(["error" => "Failed to upload file"]));
        }
    }

    $stmt = $conn->prepare("INSERT INTO reservations (user_id, adresse, heure, quantite, photo) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(["error" => "SQL Error: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("issis", $userId, $adresse, $heure, $quantite, $uploadedFile);
    if ($stmt->execute()) {
        echo json_encode(["message" => "Reservation added successfully"]);
    } else {
        echo json_encode(["error" => "Error adding reservation: " . $stmt->error]);
    }
    $stmt->close();
} elseif ($action === "loadProfile") {
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

$conn->close();
?>