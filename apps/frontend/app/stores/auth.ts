import { defineStore } from 'pinia'

interface User {
  id: number
  name: string
  email: string
  role: string
  status: string
  rejection_reason?: string | null
  appeal_reason?: string | null
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)
  const apiBase = useRuntimeConfig().public.apiBase

  if (import.meta.client) {
    token.value = localStorage.getItem('auth_token')
    const savedUser = localStorage.getItem('auth_user')
    user.value = savedUser ? JSON.parse(savedUser) : null
  }

  async function fetchUser() {
    try {
      const res = await fetch(`${apiBase}/user`, {
        headers: { Authorization: `Bearer ${token.value}` }
      })
      if (!res.ok) throw new Error('Unauthorized')
      user.value = await res.json()
      if (import.meta.client) {
        localStorage.setItem('auth_user', JSON.stringify(user.value))
      }
    } catch {
      user.value = null
      token.value = null
      if (import.meta.client) {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('auth_user')
      }
    }
  }

  async function setAuth(newToken: string, userData?: User) {
    token.value = newToken
    if (import.meta.client) {
      localStorage.setItem('auth_token', newToken)
    }

    if (userData) {
      user.value = userData
      if (import.meta.client) {
        localStorage.setItem('auth_user', JSON.stringify(userData))
      }
    } else {
      await fetchUser()
    }
  }

  async function logout() {
    if (token.value) {
      await fetch(`${apiBase}/logout`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token.value}` }
      }).catch(() => {})
    }

    user.value = null
    token.value = null
    if (import.meta.client) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      await navigateTo('/')
    }
  }

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  return { user, token, fetchUser, setAuth, logout, isAuthenticated, isAdmin }
})
