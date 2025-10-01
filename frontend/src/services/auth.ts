import api from './api'

// Sauvegarde du token JWT dans le localStorage
export function saveToken(token: string) {
  localStorage.setItem('token', token)
}

// Récupération du token JWT
export function getToken(): string | null {
  return localStorage.getItem('token')
}

// Suppression du token JWT
export function clearToken() {
  localStorage.removeItem('token')
}

// Vérifie si un token est présent
export function isAuthenticated(): boolean {
  return !!getToken()
}

// Récupère les infos de l’utilisateur courant via /api/me
export async function fetchMe() {
  const { data } = await api.get('/me')
  return data
}

// Connexion via /api/login_check
export async function login(email: string, password: string) {
  const { data } = await api.post('/login_check', { email, password })
  const token = data.token || data.jwt // selon ta config LexikJWT
  if (!token) throw new Error('Token JWT manquant')
  saveToken(token)
  return token
}

// Déconnexion → supprime le token
export function logout() {
  clearToken()
}
