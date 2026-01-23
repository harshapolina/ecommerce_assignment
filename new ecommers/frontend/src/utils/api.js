const API_BASE = import.meta.env.VITE_API_URL || '/api'

const getToken = () => {
  return localStorage.getItem('token')
}

const apiCall = async (endpoint, options = {}) => {
  const token = getToken()
  const headers = {
    'Content-Type': 'application/json',
    ...options.headers
  }

  if (token) {
    headers.token = token
    headers.authorization = `Bearer ${token}`
  }

  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers
  })

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({ error: 'Request failed' }))
    throw new Error(errorData.error || 'Request failed')
  }

  return response.json()
}

export const register = async (username, password) => {
  return apiCall('/users', {
    method: 'POST',
    body: JSON.stringify({ username, password })
  })
}

export const login = async (username, password) => {
  return apiCall('/users/login', {
    method: 'POST',
    body: JSON.stringify({ username, password })
  })
}

export const getItems = async () => {
  return apiCall('/items')
}

export const updateItem = async (itemId, data) => {
  const id = typeof itemId === 'object' ? (itemId._id || itemId.toString()) : itemId
  return apiCall(`/items/${id}`, {
    method: 'PUT',
    body: JSON.stringify(data)
  })
}

export const addToCart = async (itemId) => {
  const id = typeof itemId === 'object' ? (itemId._id || itemId.toString()) : itemId
  return apiCall('/carts', {
    method: 'POST',
    body: JSON.stringify({ itemId: id })
  })
}

export const getCartItems = async () => {
  return apiCall('/carts/my-cart')
}

export const updateCartQuantity = async (itemId, quantity) => {
  return apiCall(`/carts/${itemId}`, {
    method: 'PUT',
    body: JSON.stringify({ quantity })
  })
}

export const removeCartItem = async (itemId) => {
  return apiCall(`/carts/${itemId}`, {
    method: 'DELETE'
  })
}

export const getOrders = async () => {
  return apiCall('/orders')
}

export const checkout = async () => {
  return apiCall('/orders', {
    method: 'POST'
  })
}

