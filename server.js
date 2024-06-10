const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const mysql = require('mysql');

const app = express();
const port = 3000;

app.use(cors());
app.use(bodyParser.json());

const db = mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: 'root',
    database: 'cartonaire'
});

db.connect((err) => {
    if (err) throw err;
    console.log('Connecté à la base de données');
});

app.post('/login', (req, res) => {
    const { identifiant, mot_de_passe } = req.body;
    if (!identifiant || !mot_de_passe) {
        return res.status(400).send('Identifiant et mot de passe sont requis.');
    }

    const query = 'SELECT * FROM user WHERE identifiant = ?';
    db.query(query, [identifiant], (err, results) => {
        if (err) throw err;
        if (results.length === 0) {
            return res.status(401).send('Utilisateur non trouvé.');
        }
        
        const utilisateur = results[0];
        if (utilisateur.mot_de_passe !== mot_de_passe) {
            return res.status(401).send('Mot de passe incorrect.');
        }

        res.send('Authentification réussie.');
    });
});

app.post('/send-message', (req, res) => {
    const { message, idReceveur } = req.body;
    const idEnvoyeur = 1;

    if (!message) {
        return res.status(400).send({ error: 'Le message est requis.' });
    }

    const query = 'INSERT INTO messagerie (id_envoyeur, id_receveur, contenu) VALUES (?, ?, ?)';
    db.query(query, [idEnvoyeur, idReceveur, message], (err, results) => {
        if (err) {
            console.error('Erreur lors de l\'insertion du message :', err);
            return res.status(500).send({ error: 'Erreur lors de l\'enregistrement du message.' });
        }

        res.json({ message: 'Message envoyé avec succès' });
    });
});

app.get('/test-insert', (req, res) => {
    const testMessage = "Message de test";
    const query = "INSERT INTO test (message) VALUES (?)";

    db.query(query, [testMessage], (err, result) => {
        if (err) {
            console.error('Erreur lors de l\'insertion dans la base de données :', err);
            return res.status(500).send({ error: 'Erreur lors de l\'enregistrement des données de test.' });
        }

        res.send({ success: 'Données de test insérées avec succès', id: result.insertId });
    });
});

app.listen(port, () => {
    console.log(`Serveur démarré sur http://localhost:${port}`);
});
