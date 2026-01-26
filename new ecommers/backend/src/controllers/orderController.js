import Order from '../models/Order.js'
import Cart from '../models/Cart.js'
import CartItem from '../models/CartItem.js'
import User from '../models/User.js'

export const createOrder = async (req, res) => {
  try {
    if (!req.user || !req.user._id) {
      return res.status(401).json({ error: 'authentication required' })
    }

    const user = await User.findById(req.user._id)
    if (!user) {
      return res.status(404).json({ error: 'user not found' })
    }

    const cart = await Cart.findOne({ userId: user._id, status: 'active' })
    if (!cart) {
      return res.status(404).json({ error: 'cart not found' })
    }

    const cartItems = await CartItem.find({ cartId: cart._id })
    if (cartItems.length === 0) {
      return res.status(400).json({ error: 'cart is empty' })
    }

    const order = new Order({
      userId: user._id,
      cartId: cart._id
    })
    await order.save()

    cart.status = 'completed'
    await cart.save()

    user.cartId = null
    await user.save()

    res.status(201).json({ id: order._id, userId: order.userId, cartId: order.cartId, createdAt: order.createdAt })
  } catch (error) {
    res.status(500).json({ error: 'order not created' })
  }
}

export const getOrders = async (req, res) => {
  try {
    if (!req.user || !req.user._id) {
      return res.status(401).json({ error: 'authentication required' })
    }

    const limit = Number(req.query.limit) || 20
    const cursor = req.query.cursor
    const query = { userId: req.user._id }
    if (cursor) {
      query._id = { $gt: cursor }
    }
    
    const orders = await Order.find(query)
      .sort({ createdAt: -1 })
      .limit(limit)

    const ordersWithItems = await Promise.all(
      orders.map(async (order) => {
      const cartItems = await CartItem.find({ cartId: order.cartId }).populate('itemId', 'name')
        const items = cartItems.map(cartItem => ({
          itemId: cartItem.itemId?._id || cartItem.itemId,
          name: cartItem.itemId?.name || 'Unknown',
          quantity: cartItem.quantity || 1
        }))

        return {
          id: order._id,
        _id: order._id,
        createdAt: order.createdAt,
        items
        }
      })
    )

    res.json(ordersWithItems)
  } catch (err) {
    res.status(500).json({ error: 'Failed to get orders' })
  }
}

export const getAllOrders = async (req, res) => {
  try {
    const { cursor, limit = 20 } = req.query
    const query = cursor ? { _id: { $gt: cursor } } : {}

    const orders = await Order.find(query)
      .limit(parseInt(limit))
      .sort({ createdAt: -1 })
      .populate('userId', 'username')
      .populate('cartId')

    res.json(orders.map(order => ({
      id: order._id,
      _id: order._id,
      userId: order.userId?._id || order.userId,
      cartId: order.cartId?._id || order.cartId,
      createdAt: order.createdAt
    })))
  } catch (error) {
    res.status(500).json({ error: 'Failed to get orders' })
  }
}

