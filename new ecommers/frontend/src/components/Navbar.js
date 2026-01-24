import { Link, useNavigate } from 'react-router-dom'
import { logout } from '../utils/api.js'
import './Navbar.css'

function Navbar({ onCartClick, onHistoryClick, onCheckoutClick }) {
  const navigate = useNavigate()
  const token = localStorage.getItem('token')

  const handleLogout = async () => {
    try {
      await logout()
    } catch (error) {
    } finally {
    localStorage.removeItem('token')
    navigate('/login')
    }
  }

  return (
    <nav className="navbar">
      <div className="nav-container">
        <Link to="/items" className="nav-logo">
          Shopping Cart
        </Link>
        <div className="nav-actions">
          {token ? (
            <>
              <button className="nav-action-btn" onClick={onCheckoutClick}>Checkout</button>
               <button className="nav-action-btn" onClick={onCartClick}>Cart</button>
              <button className="nav-action-btn" onClick={onHistoryClick}>Order History</button>
              <button className="nav-action-btn logout" onClick={handleLogout}>Logout</button>
            </>
          ) : (
            <Link to="/login" className="nav-action-btn">Login</Link>
          )}
        </div>
      </div>
    </nav>
  )
}

export default Navbar

