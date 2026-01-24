import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { toast } from 'sonner'
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
      setItems(Array.isArray(data) ? data : [])
    } catch (error) {
      window.alert('Failed to load items')
      setItems([])
    } finally {
      setLoading(false)
    }
  }

  const getFilteredItems = () => {
    if (activeFilter === 'All Products') {
      return items
    }
    return items.filter(item => item.category === activeFilter)
  }

  const handleAddToCart = async (itemId, itemName) => {
    try {
      const id = itemId?._id || itemId?.id || itemId
      if (!id) {
        toast.error('Invalid item')
        return
      }
      await addToCart(id)
      toast.success(`${itemName || 'Item'} added to cart`)
    } catch (error) {
      toast.error(error.message || 'Failed to add to cart')
    }
  }

  const handleCartClick = () => {
    setShowCart(true)
  }

  const handleOrderHistoryClick = () => {
    setShowOrderHistory(true)
  }

  const handleCheckout = async () => {
    try {
      await checkout()
      toast.success('Order placed')
      setShowCart(false)
    } catch (error) {
      toast.error('Failed to checkout')
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

  return (
    <>
      <Navbar 
        onCartClick={handleCartClick}
        onHistoryClick={handleOrderHistoryClick}
        onCheckoutClick={handleCheckout}
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
            {items.length === 0 ? (
              <p className="empty-message">No items available.</p>
            ) : getFilteredItems().length === 0 ? (
              <p className="empty-message">No items available in this category</p>
            ) : (
              getFilteredItems().map(item => (
                <div 
                  key={item._id || item.id} 
                  className="product-card"
                  onClick={() => handleAddToCart(item._id || item.id, item.name)}
                >
                  <h3 className="product-name">{item.name}</h3>
                  <p className="product-category">{item.category || 'Other'}</p>
                  <p className="product-status">Status: {item.status || 'available'}</p>
                </div>
              ))
            )}
          </div>
        </div>
      </div>
      
      <CartModal 
        isOpen={showCart} 
        onClose={() => setShowCart(false)}
      />
      
      <OrderHistoryModal 
        isOpen={showOrderHistory} 
        onClose={() => setShowOrderHistory(false)}
      />
    </>
  )
}

export default Items

