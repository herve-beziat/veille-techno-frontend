# Étape 1 : Build du frontend Vue
FROM node:20 AS build

WORKDIR /app

# Copier et installer uniquement les dépendances d'abord
COPY package*.json ./
RUN npm install --frozen-lockfile

# Copier le reste du code
COPY . .

# Générer le build (prend .env.production automatiquement)
RUN npm run build

# Étape 2 : Nginx pour servir le build
FROM nginx:alpine

# Supprimer la config par défaut et copier la nôtre
RUN rm /etc/nginx/conf.d/default.conf
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copier les fichiers du build Vue
COPY --from=build /app/dist /usr/share/nginx/html

# Exposer le port (bonnes pratiques, pas obligatoire)
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
