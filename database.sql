-- ============================================================
-- BASE DE DONNÉES : Module Gestion des Objets Perdus
-- ============================================================

CREATE DATABASE IF NOT EXISTS objets_perdus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE objets_perdus;

-- Table des catégories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    icone VARCHAR(50) DEFAULT 'fas fa-box',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des lieux
CREATE TABLE lieux (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    batiment VARCHAR(100),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des utilisateurs (admin/personnel + parents)
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','personnel','parent') NOT NULL DEFAULT 'parent',
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    tel VARCHAR(20),
    cin VARCHAR(30),
    actif TINYINT(1) DEFAULT 1,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des élèves
CREATE TABLE eleves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    classe VARCHAR(50),
    numero_etudiant VARCHAR(50),
    parent_id INT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- Table des annonces
CREATE TABLE annonces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('perdu','trouve') NOT NULL,
    categorie_id INT NOT NULL,
    lieu_id INT NOT NULL,
    description TEXT NOT NULL,
    photo VARCHAR(255),
    contact_email VARCHAR(150),
    contact_tel VARCHAR(20),
    date_objet DATE,
    statut ENUM('en_attente','valide','recupere','donne','archive') DEFAULT 'en_attente',
    ip_utilisateur VARCHAR(45),
    declarant_nom VARCHAR(150),
    declarant_email VARCHAR(150),
    user_id INT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id),
    FOREIGN KEY (lieu_id) REFERENCES lieux(id),
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- Table des demandes de récupération
CREATE TABLE demandes_recuperation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    annonce_id INT NOT NULL,
    parent_id INT NOT NULL,
    eleve_id INT NOT NULL,
    message TEXT,
    statut ENUM('en_attente','approuvee','refusee') DEFAULT 'en_attente',
    motif_refus TEXT,
    date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_traitement TIMESTAMP NULL,
    traite_par INT NULL,
    FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (eleve_id) REFERENCES eleves(id),
    FOREIGN KEY (traite_par) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- Table des matches (correspondances automatiques)
CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    objet_perdu_id INT NOT NULL,
    objet_trouve_id INT NOT NULL,
    score INT DEFAULT 0,
    statut ENUM('en_attente', 'valide', 'refuse') DEFAULT 'en_attente',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (objet_perdu_id) REFERENCES annonces(id) ON DELETE CASCADE,
    FOREIGN KEY (objet_trouve_id) REFERENCES annonces(id) ON DELETE CASCADE
);

-- Table des notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    lien VARCHAR(255),
    lu TINYINT(1) DEFAULT 0,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- ============================================================
-- DONNÉES DE DÉMONSTRATION
-- ============================================================

INSERT INTO categories (nom, icone) VALUES
('Vêtements', 'fas fa-tshirt'),
('Électronique', 'fas fa-mobile-alt'),
('Scolaire', 'fas fa-book'),
('Bijoux & Accessoires', 'fas fa-gem'),
('Clés', 'fas fa-key'),
('Sacs & Cartables', 'fas fa-backpack'),
('Sport', 'fas fa-futbol'),
('Alimentation', 'fas fa-apple-alt'),
('Divers', 'fas fa-box');

INSERT INTO lieux (nom, batiment) VALUES
('Cour principale', NULL),
('Réfectoire', 'Bâtiment A'),
('Salle informatique', 'Bâtiment B'),
('Gymnase', 'Annexe Sport'),
('Couloir Bâtiment A', 'Bâtiment A'),
('Bibliothèque', 'Bâtiment C'),
('Vestiaires', 'Annexe Sport'),
('Salle de classe 101', 'Bâtiment A'),
('Salle de classe 205', 'Bâtiment B'),
('Bureau CPE', 'Administration'),
('Entrée principale', NULL);

-- Utilisateurs (mot de passe = Admin1234 hashé en bcrypt)
INSERT INTO utilisateurs (login, mot_de_passe_hash, role, nom, prenom, email, tel) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Dupont', 'Jean', 'admin@ecole.fr', '0600000001'),
('cpe01', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'personnel', 'Martin', 'Sophie', 'cpe@ecole.fr', '0600000002'),
('parent.ali', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Benali', 'Hassan', 'h.benali@email.com', '0612345678'),
('parent.sara', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', 'Moussaoui', 'Sara', 's.moussaoui@email.com', '0698765432');

INSERT INTO eleves (nom, prenom, classe, numero_etudiant, parent_id) VALUES
('Benali', 'Youssef', '3ème B', 'E001', 3),
('Benali', 'Fatima', '5ème A', 'E002', 3),
('Moussaoui', 'Adam', '4ème C', 'E003', 4);

INSERT INTO annonces (type, categorie_id, lieu_id, description, statut, declarant_nom, date_objet) VALUES
('trouve', 2, 1, 'Téléphone Samsung Galaxy noir trouvé dans la cour, écran fissuré en bas', 'valide', 'M. Martin', '2024-01-15'),
('perdu', 3, 5, 'Trousse bleue avec prénom "Lucas" dessus, contient stylos et règle', 'valide', 'Parent Lucas', '2024-01-14'),
('trouve', 1, 4, 'Veste de survêtement rouge taille M, marque Nike', 'valide', 'Prof de sport', '2024-01-13'),
('perdu', 5, 2, 'Clés avec porte-clés étoile bleue, 3 clés attachées', 'valide', 'Elève Amine', '2024-01-12'),
('trouve', 6, 8, 'Cartable noir avec initiales "A.B." gravées sur la bretelle', 'en_attente', NULL, '2024-01-16');
