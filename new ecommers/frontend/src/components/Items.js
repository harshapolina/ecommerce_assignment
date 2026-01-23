import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { getItems, addToCart, getCartItems, getOrders, checkout } from '../utils/api.js'
import Navbar from './Navbar.js'
import Hero from './Hero.js'
import CartModal from './CartModal.js'
import OrderHistoryModal from './OrderHistoryModal.js'
import './Items.css'

function Items() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [activeFilter, setActiveFilter] = useState('All Products')
  const [showCart, setShowCart] = useState(false)
  const [showOrderHistory, setShowOrderHistory] = useState(false)
  const [notification, setNotification] = useState(null)
  const [cartRefreshKey, setCartRefreshKey] = useState(0)
  const navigate = useNavigate()

  useEffect(() => {
    const token = localStorage.getItem('token')
    if (!token) {
      navigate('/login')
      return
    }
    fetchItems()
  }, [navigate])

  const fetchItems = async () => {
    try {
      setLoading(true)
      const data = await getItems()
      setItems(data)
    } catch (error) {
    } finally {
      setLoading(false)
    }
  }

  const getFilteredItems = () => {
    if (activeFilter === 'All Products') {
      return items
    }
    return items.filter(item => {
      if (activeFilter === 'Indoor Plants') return item.category === 'Indoor Plants'
      if (activeFilter === 'Outdoor Plants') return item.category === 'Outdoor Plants'
      if (activeFilter === 'Herbal Plants') return item.category === 'Herbal Plants'
      return true
    })
  }

  const handleAddToCart = async (itemId) => {
    try {
      const id = itemId?._id || itemId?.id || itemId
      if (!id) {
        showNotification('Invalid item ID', 'error')
        return
      }
      await addToCart(id)
      showNotification('Item added to cart!')
      setCartRefreshKey(prev => prev + 1)
    } catch (error) {
      showNotification(error.message || 'Failed to add item to cart', 'error')
    }
  }

  const showNotification = (message, type = 'success') => {
    setNotification({ message, type })
    setTimeout(() => setNotification(null), 3000)
  }

  const handleViewCart = () => {
    setShowCart(true)
  }

  const handleViewOrders = () => {
    setShowOrderHistory(true)
  }

  const handleCheckout = async () => {
    try {
      await checkout()
      showNotification('Order successful!', 'success')
      fetchItems()
      setShowCart(false)
    } catch (error) {
      showNotification(error.message || 'Failed to checkout', 'error')
    }
  }

  if (loading) {
    return (
      <>
        <Navbar />
        <div className="loading">Loading items...</div>
      </>
    )
  }

  const handleNavCart = () => {
    setShowCart(true)
  }

  const handleNavHistory = () => {
    handleViewOrders()
  }

  const handleNavCheckout = () => {
    handleCheckout()
  }

  return (
    <>
      <Navbar 
        onCartClick={handleNavCart}
        onHistoryClick={handleNavHistory}
        onCheckoutClick={handleNavCheckout}
      />
      <Hero />
      <div className="products-section">
        <div className="products-container">
          <h2 className="section-title">Our Products</h2>
          
          <div className="filter-buttons">
            <button 
              className={activeFilter === 'All Products' ? 'filter-btn active' : 'filter-btn'}
              onClick={() => setActiveFilter('All Products')}
            >
              All Products
            </button>
            <button 
              className={activeFilter === 'Indoor Plants' ? 'filter-btn active' : 'filter-btn'}
              onClick={() => setActiveFilter('Indoor Plants')}
            >
              Indoor Plants
            </button>
            <button 
              className={activeFilter === 'Outdoor Plants' ? 'filter-btn active' : 'filter-btn'}
              onClick={() => setActiveFilter('Outdoor Plants')}
            >
              Outdoor Plants
            </button>
            <button 
              className={activeFilter === 'Herbal Plants' ? 'filter-btn active' : 'filter-btn'}
              onClick={() => setActiveFilter('Herbal Plants')}
            >
              Herbal Plants
            </button>
          </div>

          <div className="items-grid">
            {getFilteredItems().length === 0 ? (
              <p className="empty-message">No items available in this category</p>
            ) : (
              getFilteredItems().map(item => (
                <div key={item._id} className="product-card">
                  <div className="product-image">
                    {item.image && item.image.trim() !== '' ? (
                      <img 
                        src={item.image} 
                        alt={item.name} 
                        className="product-img"
                        key={item.image}
                        onError={(e) => {
                          e.target.style.display = 'none'
                          if (e.target.nextSibling) {
                            e.target.nextSibling.style.display = 'flex'
                          }
                        }}
                        onLoad={(e) => {
                          if (e.target.nextSibling) {
                            e.target.nextSibling.style.display = 'none'
                          }
                        }}
                      />
                    ) : null}
                    <div className="product-placeholder" style={{ display: (item.image && item.image.trim() !== '') ? 'none' : 'flex' }}>
                      🌿
                    </div>
                  </div>
                  <h3 className="product-name">{item.name}</h3>
                  <p className="product-category">{item.category || 'Indoor Plants'}</p>
                  <p className="product-price">${(item.price || 25.00).toFixed(2)}</p>
                  <button 
                    onClick={() => handleAddToCart(item._id || item.id)}
                    className="add-to-cart-btn"
                  >
                    Add to Cart
                  </button>
                </div>
              ))
            )}
          </div>
        </div>
      </div>

      
      {notification && (
        <div className={`notification ${notification.type}`}>
          {notification.message}
        </div>
      )}
      
      <CartModal 
        isOpen={showCart} 
        onClose={() => setShowCart(false)}
        refreshKey={cartRefreshKey}
      />
      
      <OrderHistoryModal 
        isOpen={showOrderHistory} 
        onClose={() => setShowOrderHistory(false)}
      />
    </>
  )
}

export default Items

