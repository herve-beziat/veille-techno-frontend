# 🗂️ Veille Techno - Kanban App

Application web Kanban permettant de gérer des tâches sous forme de colonnes (To Do, In Progress, Done...).
Projet développé dans le cadre de la veille technologique avec un **backend Symfony** et un **frontend Vue 3 (TypeScript, Pinia, Vite)**.

---

## 🚀 Stack Technique

- **Backend** : [Symfony 6](https://symfony.com/) + Doctrine + LexikJWTAuth  
- **Frontend** : [Vue 3](https://vuejs.org/) + [Vite](https://vitejs.dev/) + [Pinia](https://pinia.vuejs.org/)  
- **Authentification** : JWT (connexion / inscription / déconnexion)  
- **Base de données** : MariaDB 11  
- **Containerisation** : Docker & Docker Compose  
- **Reverse proxy Frontend** : Nginx

---

## 📂 Structure du projet

```
veille-techno-frontend/
│── backend/         # API Symfony
│── frontend/        # Application Vue 3
│── docker-compose.yml
└── README.md
```

---

## ⚙️ Installation & Lancement

### 1. Cloner le projet
```bash
git clone https://github.com/ton-repo/veille-techno-frontend.git
cd veille-techno-frontend
```

### 2. Lancer les containers
```bash
docker compose up -d --build
```
👉 Ça démarre le **backend Symfony** et la **DB MariaDB**.

### 3. Lancer le frontend en dev (hors Docker)
```bash
cd frontend
npm install
npm run dev
```
👉 Front dispo sur [http://localhost:5173](http://localhost:5173)  
👉 Backend dispo sur [http://localhost:8080](http://localhost:8080)

---

## 🔑 Authentification

- **Inscription** : `/api/register`  
- **Connexion** : `/api/login_check`  
- **Profil** : `/api/me` (protégé par JWT)

Exemple credentials pour tests :
```json
{
  "email": "test@example.com",
  "password": "secret123"
}
```

---

## 🛠️ Workflow Git

- Branches de features : `feature/...`  
  Exemple : `feature/kanban-board`
- Branches de fix : `fix/...`
- Commits clairs et concis :
  ```
  feat(auth): ajout du login et register avec JWT
  fix(db): correction du mapping Doctrine User
  ```

---

## ✅ Roadmap

- [x] Authentification (register, login, logout)  
- [x] Gestion de l’état global avec Pinia  
- [ ] Implémentation du tableau Kanban (colonnes + tâches)  
- [ ] Drag & drop entre colonnes  
- [ ] Amélioration UI/UX avec Tailwind CSS  

---

## 👨‍💻 Auteur

Projet développé par **Hervé Beziat** dans le cadre de la formation CDA.
