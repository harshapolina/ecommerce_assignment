import { useState, useEffect } from 'react'
import { getOrders } from '../utils/api.js'
import './OrderHistoryModal.css'

function OrderHistoryModal({ isOpen, onClose }) {
  const [orders, setOrders] = useState([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    if (isOpen) {
      fetchOrders()
    }
  }, [isOpen])

  const fetchOrders = async () => {
    try {
      setLoading(true)
      const data = await getOrders()
      setOrders(Array.isArray(data) ? data : [])
    } catch (error) {
      setOrders([])
    } finally {
      setLoading(false)
    }
  }

  const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { 
      year: 'numeric', 
      month: 'short', 
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  }

  if (!isOpen) return null

  return (
    <div className="order-modal-overlay" onClick={onClose}>
      <div className="order-modal" onClick={(e) => e.stopPropagation()}>
        <div className="order-modal-header">
          <h2>Order History</h2>
          <button className="close-btn" onClick={onClose}>
            <i className="bi bi-x-lg"></i>
          </button>
        </div>

        <div className="order-modal-body">
          {loading ? (
            <div className="order-loading">Loading orders...</div>
          ) : orders.length === 0 ? (
            <div className="order-empty">
              <p>No orders found</p>
              <p className="order-empty-subtitle">Your order history will appear here</p>
            </div>
          ) : (
            <div className="orders-list">
              {orders.map((order, index) => (
                <div key={order.id || order._id || index} className="order-item">
                  <div className="order-item-header">
                    <h3>Order #{index + 1}</h3>
                    <span className="order-date">{formatDate(order.createdAt)}</span>
                  </div>
                  {order.items && order.items.length > 0 ? (
                    <div className="order-items-list">
                      {order.items.map((item, itemIndex) => (
                        <div key={itemIndex} className="order-item-row">
                          <div className="order-item-info">
                            <p className="order-item-name">{item.name || 'Unknown'}</p>
                            <p className="order-item-quantity">Quantity: {item.quantity || 1}</p>
                            <p className="order-item-id">Item ID: {item.itemId}</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p>No items found</p>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>

        {orders.length > 0 && (
          <div className="order-modal-footer">
            <p className="order-count">Total Orders: {orders.length}</p>
          </div>
        )}
      </div>
    </div>
  )
}

export default OrderHistoryModal

