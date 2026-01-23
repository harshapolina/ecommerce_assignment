import bcrypt from 'bcryptjs'
import crypto from 'crypto'
import User from '../models/User.js'

export const createUser = async (req, res) => {
  try {
    const { username, password } = req.body

    if (!username || !password) {
      return res.status(400).json({ error: 'Username and password required' })
    }

    const existingUser = await User.findOne({ username })
    if (existingUser) {
      return res.status(400).json({ error: 'Username already exists' })
    }

    const hashedPassword = await bcrypt.hash(password, 10)
    const user = new User({
      username,
      password: hashedPassword
    })

    await user.save()
    res.status(201).json({ id: user._id, username: user.username, createdAt: user.createdAt })
  } catch (error) {
    res.status(500).json({ error: 'Failed to create user' })
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

    res.json(users)
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch users' })
  }
}

export const login = async (req, res) => {
  try {
    const { username, password } = req.body

    if (!username || !password) {
      return res.status(400).json({ error: 'Username and password required' })
    }

    const user = await User.findOne({ username })
    if (!user) {
      return res.status(401).json({ error: 'Invalid username or password' })
    }

    const isValidPassword = await bcrypt.compare(password, user.password)
    if (!isValidPassword) {
      return res.status(401).json({ error: 'Invalid username or password' })
    }

    const token = crypto.randomBytes(32).toString('hex')
    user.token = token
    await user.save()

    res.json({ token })
  } catch (error) {
    res.status(500).json({ error: 'Login failed' })
  }
}

