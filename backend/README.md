# 🚀 Veille Technologique Backend

## 📌 Présentation
Ce projet est un backend développé avec **Symfony**, exposant une API REST sécurisée avec **JWT**.  
Il a été conçu dans un contexte de comparaison technologique (**Symfony vs NestJS vs Spring Boot**), avec une mise en œuvre complète des fonctionnalités attendues :  
- Gestion des utilisateurs (inscription, connexion, rôles, mise à jour, suppression)  
- Gestion des listes (BoardList)  
- Gestion des cartes (Card)  
- Documentation interactive via Swagger (**NelmioApiDocBundle**)  

---

## ⚙️ Installation

1. Cloner le projet :
```bash
git clone https://github.com/herve-beziat/veille-techno-backend.git
cd veille-techno-backend
```

2. Lancer l’environnement Docker :
```bash
# Première fois (ou après modification du Dockerfile)
docker compose up -d --build

# Lancements suivants
docker compose up -d
```

3. Installer les dépendances PHP :
```bash
docker exec -it symfony-backend bash
composer install
```

4. Exécuter les migrations (dans le conteneur Symfony) :
```bash
docker exec -it symfony-backend php bin/console doctrine:migrations:migrate
```

---

## 🔑 Authentification

L’API utilise **JWT**.  
- **Inscription** : `POST /api/register`  
- **Connexion** : `POST /api/login_check` → retourne un token  
- **Récupérer profil** : `GET /api/me` (token requis)  

Dans Swagger, cliquer sur **Authorize 🔒** et entrer :  
```
Bearer <votre_token>
```

---

## 📖 Documentation

- **Swagger UI** : [http://localhost:8080/api/doc](http://localhost:8080/api/doc)  
- **Documentation PDF** :  
  - [API Tests (Swagger)](./docs/API_Documentation_Checklist.pdf)  
  - [Veille Technologique (Symfony vs NestJS vs Spring Boot)](./docs/rapport-veille-back.pdf)  

---

## 🛠️ Endpoints principaux

### 👤 Utilisateurs
- `POST /api/register` → inscription  
- `POST /api/login_check` → connexion  
- `GET /api/me` → profil utilisateur connecté  
- `PUT /api/me` → mise à jour profil  
- `DELETE /api/me` → suppression profil  
- `GET /api/users` (admin) → liste des utilisateurs  
- `PUT /api/users/{id}` (admin) → mise à jour d’un utilisateur  
- `DELETE /api/users/{id}` (admin) → suppression d’un utilisateur  

### 📋 BoardLists
- `POST /api/boardlists` → créer une liste  
- `GET /api/boardlists` → lister les listes  
- `PUT /api/boardlists/{id}` → mettre à jour une liste  
- `DELETE /api/boardlists/{id}` → supprimer une liste  

### 🃏 Cards
- `POST /api/cards` → créer une carte  
- `GET /api/cards?list_id=xx` → lister les cartes d’une liste  
- `PUT /api/cards/{id}` → mettre à jour une carte  
- `DELETE /api/cards/{id}` → supprimer une carte  

---

## 📌 Choix technologique

Le projet a retenu **Symfony** après une étude comparative avec **NestJS** et **Spring Boot**.  
➡️ Voir le document : [Veille Technologique](./docs/rapport-veille-back.pdf)

---

## 📜 Licence
Projet académique – usage pédagogique.
