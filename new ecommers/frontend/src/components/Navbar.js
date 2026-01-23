import { Link, useNavigate } from 'react-router-dom'
import './Navbar.css'

function Navbar({ onCartClick, onHistoryClick, onCheckoutClick }) {
  const navigate = useNavigate()
  const token = localStorage.getItem('token')

  const handleLogout = () => {
    localStorage.removeItem('token')
    navigate('/login')
  }

  return (
    <nav className="navbar">
      <div className="nav-container">
        <Link to="/items" className="nav-logo">
          Shopping Cart
        </Link>
        <div className="nav-menu">
          <Link to="/items" className="nav-link">Home</Link>
          <Link to="/items" className="nav-link">Shop</Link>
          <Link to="/items" className="nav-link">About</Link>
          <Link to="/items" className="nav-link">Contact</Link>
        </div>
        <div className="nav-actions">
          {token ? (
            <>
              <button className="nav-action-btn" onClick={onCartClick}>Cart</button>
              <button className="nav-action-btn" onClick={onHistoryClick}>History</button>
              <button className="nav-action-btn" onClick={onCheckoutClick}>Checkout</button>
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

