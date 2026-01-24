import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { login } from '../utils/api.js'
import './Login.css'

function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
        const data = await login(username, password);
        localStorage.setItem('token', data.token);
        navigate('/items');
    } catch (error) {
      const errorMessage = error.message || 'Login failed'
      if (errorMessage.includes('already in use')) {
        window.alert("Account is already in use on another device")
      } else if (errorMessage.includes('Invalid') || errorMessage.includes('credentials') || errorMessage.includes('No user found')) {
        window.alert("Invalid username/password")
      } else {
        window.alert(errorMessage)
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-container">
      <div className="login-box">
        <h1>Shopping Cart</h1>
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Username or Email</label>
            <input
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
              disabled={loading}
              placeholder="Enter username or email"
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
            {loading ? 'Logging in...' : 'Login'}
          </button>
          <p style={{ textAlign: 'center', marginTop: '15px', fontSize: '14px', color: '#666' }}>
            Don't have an account? <Link to="/register" style={{ color: '#ffa500', textDecoration: 'none', fontWeight: '600' }}>Register</Link>
          </p>
        </form>
      </div>
    </div>
  )
}

export default Login

