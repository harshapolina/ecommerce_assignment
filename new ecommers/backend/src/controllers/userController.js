import bcrypt from 'bcryptjs'
import jwt from 'jsonwebtoken'
import User from '../models/User.js'

export const createUser = async (req, res) => {
  try {
    const { username, email, password } = req.body

    if (!username || !password) {
      return res.status(400).json({ error: 'Username and password required' })
    }

    const existingUser = await User.findOne({
      $or: [{ username }, { email: email?.toLowerCase() }]
    })
    if (existingUser) {
      if (existingUser.username === username) {
        return res.status(400).json({ error: 'Username already exists' })
      }
      if (existingUser.email === email?.toLowerCase()) {
        return res.status(400).json({ error: 'email already exists' })
      }
    }

    const hashedPassword = await bcrypt.hash(password, 10)
    const user = new User({
      username,
      email: email ? email.toLowerCase() : undefined,
      password: hashedPassword
    })

    await user.save()

    res.status(201).json({ id: user._id, username: user.username, createdAt: user.createdAt })
  } catch (error) {
    if (error.code === 11000) {
      return res.status(400).json({ error: 'Username already exists' })
    }
    res.status(500).json({ error: 'failed to create user' })
  }
}

export const getUsers = async (req, res) => {
  try {
    const { cursor, limit = 20 } = req.query
    const query = cursor ? { _id: { $gt: cursor } } : {}
    
    const users = await User.find(query)
      .select('-password -token')
      .limit(parseInt(limit))
      .sort({ _id: 1 })

    res.json(users.map(user => ({
      id: user._id,
      _id: user._id,
      username: user.username,
      email: user.email,
      cartId: user.cartId,
      createdAt: user.createdAt
    })))
  } catch (error) {
    res.status(500).json({ error: 'Failed to bring users' })
  }
}

export const login = async (req, res) => {
  try {
    const { username, password } = req.body

    if (!username || !password) {
      return res.status(400).json({ error: 'Username and password required' })
    }

    const user = await User.findOne({
      $or: [{ username }, { email: username.toLowerCase() }]
    })
    if (!user) {
      return res.status(401).json({ error: 'No user found, register first' })
    }

    const isValidPassword = await bcrypt.compare(password, user.password)
    if (!isValidPassword) {
      return res.status(401).json({ error: 'invalid details' })
    }

    if (user.token && user.token.trim() !== '') {
      return res.status(403).json({ error: 'account is already in use on another device' })
    }

    const jwtSecret = process.env.JWT_SECRET || 'default-secret-key-change-in-production'
    if (!process.env.JWT_SECRET) {
      console.warn('Warning: JWT_SECRET not set in environment variables. Using default secret. This is insecure for production!')
    }
    const token = jwt.sign({ id: user._id }, jwtSecret, { expiresIn: '7d' })
    user.token = token
    await user.save()

    res.json({ token })
  } catch (error) {
    res.status(500).json({ error: 'login failed' })
  }
}

export const logout = async (req, res) => {
  try {
    if (!req.user || !req.user._id) {
      return res.status(401).json({ error: 'authentication needed' })
    }

    const user = await User.findById(req.user._id)
    if (user) {
      user.token = null
      await user.save()
    }

    res.json({ message: 'logged out successfully' })
  } catch (error) {
    res.status(500).json({ error: 'logout failed' })
  }
}

