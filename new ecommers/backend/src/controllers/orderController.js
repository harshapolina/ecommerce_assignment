import Order from '../models/Order.js'
import Cart from '../models/Cart.js'
import CartItem from '../models/CartItem.js'

export const createOrder = async (req, res) => {
  try {
    const cart = await Cart.findOne({ userId: req.user._id, status: 'active' })

    if (!cart) {
      return res.status(400).json({ error: 'No active cart found' })
    }

    const cartItems = await CartItem.find({ cartId: cart._id })
    if (cartItems.length === 0) {
      return res.status(400).json({ error: 'Cart is empty' })
    }

    const order = new Order({
      userId: req.user._id,
      cartId: cart._id
    })
    await order.save()

    cart.status = 'completed'
    await cart.save()

    res.status(201).json({ id: order._id, userId: order.userId, cartId: order.cartId, createdAt: order.createdAt })
  } catch (error) {
    res.status(500).json({ error: 'Failed to create order' })
  }
}

export const getOrders = async (req, res) => {
  try {
    const { cursor, limit = 20 } = req.query
    const query = { userId: req.user._id }
    if (cursor) {
      query._id = { $gt: cursor }
    }

    const orders = await Order.find(query)
      .limit(parseInt(limit))
      .sort({ createdAt: -1 })
      .populate('cartId')

    const CartItem = (await import('../models/CartItem.js')).default

    const ordersWithDetails = await Promise.all(orders.map(async (order) => {
      let total = 0
      let itemCount = 0
      
      if (order.cartId) {
        const cartItems = await CartItem.find({ cartId: order.cartId._id }).populate('itemId', 'name price image')
        itemCount = cartItems.length
        total = cartItems.reduce((sum, cartItem) => {
          const price = cartItem.itemId?.price || 0
          const quantity = cartItem.quantity || 1
          return sum + (price * quantity)
        }, 0)
      }

      return {
        id: order._id,
        _id: order._id,
        createdAt: order.createdAt,
        total: total,
        itemCount: itemCount
      }
    }))

    res.json(ordersWithDetails)
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch orders' })
  }
}

