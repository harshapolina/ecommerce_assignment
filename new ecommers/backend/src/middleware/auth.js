import User from '../models/User.js'

const authenticate = async (req, res, next) => {
  try {
    const token = req.headers.authorization?.replace('Bearer ', '') || req.headers.token

    if (!token) {
      return res.status(401).json({ error: 'Authentication required' })
    }

    const user = await User.findOne({ token })

    if (!user) {
      return res.status(401).json({ error: 'Invalid token' })
    }

    req.user = user
    next()
  } catch (error) {
    res.status(500).json({ error: 'Authentication failed' })
  }
}

export default authenticate

