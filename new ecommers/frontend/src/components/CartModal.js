import { useState, useEffect } from 'react'
import { getCartItems, updateCartQuantity, removeCartItem } from '../utils/api.js'
import './CartModal.css'

function CartModal({ isOpen, onClose, refreshKey }) {
  const [cartItems, setCartItems] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (isOpen) {
      fetchCartItems()
    }
  }, [isOpen, refreshKey])

  const fetchCartItems = async (showLoading = true) => {
    try {
      if (showLoading) setLoading(true)
      const items = await getCartItems()
      setCartItems(Array.isArray(items) ? items : [])
    } catch (error) {
      setCartItems([])
    } finally {
      if (showLoading) setLoading(false)
    }
  }

  const handleQuantityChange = async (itemId, newQuantity) => {
    if (newQuantity < 1) return

    const previousItems = [...cartItems]
    setCartItems(prevItems => 
      prevItems.map(item => {
        const matches = item.itemId === itemId || 
                       item.itemId?._id === itemId || 
                       item.itemId?.toString() === itemId?.toString()
        return matches ? { ...item, quantity: newQuantity } : item
      })
    )

    try {
      await updateCartQuantity(itemId, newQuantity)
      fetchCartItems(false).catch(() => {
        setCartItems(previousItems)
      })
    } catch (error) {
      setCartItems(previousItems)
    }
  }

  const handleRemove = async (itemId) => {
    const previousItems = [...cartItems]
    setCartItems(prevItems => 
      prevItems.filter(item => {
        const matches = item.itemId === itemId || 
                       item.itemId?._id === itemId || 
                       item.itemId?.toString() === itemId?.toString()
        return !matches
      })
    )

    try {
      await removeCartItem(itemId)
      fetchCartItems(false).catch(() => {
        setCartItems(previousItems)
      })
    } catch (error) {
      setCartItems(previousItems)
    }
  }

  const totalItems = cartItems.reduce((sum, item) => sum + item.quantity, 0)
  const totalPrice = cartItems.reduce((sum, item) => sum + (item.quantity * (item.itemPrice || 25.00)), 0)

  if (!isOpen) return null

  return (
    <div className="cart-modal-overlay" onClick={onClose}>
      <div className="cart-modal" onClick={(e) => e.stopPropagation()}>
        <div className="cart-modal-header">
          <h2>Shopping Cart</h2>
          <button className="close-btn" onClick={onClose}>×</button>
        </div>

        <div className="cart-modal-body">
          {loading ? (
            <div className="cart-loading">Loading cart...</div>
          ) : cartItems.length === 0 ? (
            <div className="cart-empty">
              <p>Your cart is empty</p>
            </div>
          ) : (
            <div className="cart-items">
              {cartItems.map(item => (
                <div key={item._id} className="cart-item">
                  <div className="cart-item-info">
                    {item.itemImage ? (
                      <>
                        <img 
                          src={item.itemImage} 
                          alt={item.itemName} 
                          className="cart-item-image"
                          onError={(e) => {
                            e.target.style.display = 'none'
                            if (e.target.nextSibling) {
                              e.target.nextSibling.style.display = 'flex'
                            }
                          }}
                        />
                        <div className="cart-item-placeholder" style={{ display: 'none' }}>🌿</div>
                      </>
                    ) : (
                      <div className="cart-item-placeholder">🌿</div>
                    )}
                    <div className="cart-item-details">
                      <h3>{item.itemName}</h3>
                      <p className="cart-item-price">${(item.itemPrice || 25.00).toFixed(2)}</p>
                    </div>
                  </div>
                  <div className="cart-item-controls">
                    <button
                      className="quantity-btn"
                      onClick={() => handleQuantityChange(item.itemId, item.quantity - 1)}
                    >
                      −
                    </button>
                    <span className="quantity-display">{item.quantity}</span>
                    <button
                      className="quantity-btn"
                      onClick={() => handleQuantityChange(item.itemId, item.quantity + 1)}
                    >
                      +
                    </button>
                    <button
                      className="remove-btn"
                      onClick={() => handleRemove(item.itemId)}
                    >
                      🗑️
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {cartItems.length > 0 && (
          <div className="cart-modal-footer">
            <div className="cart-summary">
              <p>Total Items: {totalItems}</p>
              <p className="cart-total">Total: ${totalPrice.toFixed(2)}</p>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

export default CartModal

