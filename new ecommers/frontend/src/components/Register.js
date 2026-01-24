import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { register } from '../utils/api.js'
import './Login.css'

function Register() {
  const [username, setUsername] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const navigate = useNavigate()

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)

    try {
      await register(username, email, password)
      window.alert('Registration successful! Please login.')
      navigate('/login')
    } catch (error) {
      window.alert(error.message || 'Registration failed')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="login-container">
      <div className="login-box">
        <h1>Shopping Cart</h1>
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Username</label>
            <input
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
              disabled={loading}
            />
          </div>
          <div className="form-group">
            <label>Email</label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              disabled={loading}
            />
          </div>
          <div className="form-group">
            <label>Password</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              disabled={loading}
            />
          </div>
          <button type="submit" disabled={loading}>
            {loading ? 'Registering...' : 'Register'}
          </button>
          <p style={{ textAlign: 'center', marginTop: '15px', fontSize: '14px', color: '#666' }}>
            Already have an account? <Link to="/login" style={{ color: '#ffa500', textDecoration: 'none', fontWeight: '600' }}>Login</Link>
          </p>
        </form>
      </div>
    </div>
  )
}

export default Register

