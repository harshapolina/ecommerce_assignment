import mongoose from 'mongoose'
import Cart from '../models/Cart.js'
import CartItem from '../models/CartItem.js'
import Item from '../models/Item.js'
import User from '../models/User.js'

export const addToCart = async (req, res) => {
  try {
    const { itemId } = req.body

    if (!itemId) {
      return res.status(400).json({ error: 'item id required' })
    }

    if (!req.user || !req.user._id) {
      return res.status(401).json({ error: 'authentication required' })
    }

    const user = await User.findById(req.user._id)
    if (!user) {
      return res.status(404).json({ error: 'user not found' })
    }

    if (!mongoose.Types.ObjectId.isValid(itemId)) {
      return res.status(400).json({ error: 'invalid item id' })
    }

    const item = await Item.findById(itemId)
    if (!item) {
      return res.status(404).json({ error: 'item not found' })
    }

    let cart = await Cart.findOne({ userId: user._id, status: 'active' })

    if (!cart) {
      const completedCart = await Cart.findOne({ userId: user._id, status: 'completed' })
      if (completedCart) {
        await CartItem.deleteMany({ cartId: completedCart._id })
        completedCart.status = 'active'
        await completedCart.save()
        cart = completedCart
      }
    }

    if (!cart) {
      try {
        cart = new Cart({ userId: user._id, status: 'active' })
        await cart.save()
      } catch (err) {
        if (err.code === 11000) {
          cart = await Cart.findOne({ userId: user._id, status: 'active' })
          if (!cart) {
            const existing = await Cart.findOne({ userId: user._id })
            if (existing) {
              if (existing.status === 'completed') {
                await CartItem.deleteMany({ cartId: existing._id })
                existing.status = 'active'
                await existing.save()
              }
              cart = existing
            }
          }
          if (!cart) {
            throw new Error('Failed to create cart')
          }
        } else {
          throw err
        }
      }
    }

    if (user.cartId !== cart._id) {
      user.cartId = cart._id
      await user.save()
    }

    const existing = await CartItem.findOne({ cartId: cart._id, itemId })

    if (existing) {
      existing.quantity += 1
      await existing.save()
      res.status(200).json({ cartId: cart._id, itemId })
    } else {
      try {
        const cartItem = new CartItem({ cartId: cart._id, itemId, quantity: 1 })
        await cartItem.save()
        res.status(201).json({ cartId: cart._id, itemId })
      } catch (err) {
        if (err.code === 11000) {
          const existing = await CartItem.findOne({ cartId: cart._id, itemId })
          if (existing) {
            existing.quantity += 1
            await existing.save()
            res.status(200).json({ cartId: cart._id, itemId })
          } else {
            throw err
          }
        } else {
          throw err
        }
      }
    }
  } catch (error) {
    res.status(500).json({ error: 'Failed to add to cart' })
  }
}

export const getMyCart = async (req, res) => {
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
      return res.json([])
    }

    const cartItems = await CartItem.find({ cartId: cart._id }).populate('itemId', 'name')

    if (!cartItems || cartItems.length === 0) {
      return res.json([])
    }

    res.json(cartItems.map(item => ({
      cartId: item.cartId,
      itemId: item.itemId?._id || item.itemId,
      itemName: item.itemId?.name || 'Unknown',
      quantity: item.quantity || 1
    })))
  } catch (error) {
    res.status(500).json({ error: 'Failed to get cart' })
  }
}

export const removeFromCart = async (req, res) => {
  try {
    const { itemId } = req.params

    if (!itemId) {
      return res.status(400).json({ error: 'item id required' })
    }

    if (!req.user || !req.user._id) {
      return res.status(401).json({ error: 'authentication required' })
    }

    const user = await User.findById(req.user._id)
    if (!user) {
      return res.status(404).json({ error: 'user not found' })
    }

    if (!mongoose.Types.ObjectId.isValid(itemId)) {
      return res.status(400).json({ error: 'invalid item id' })
    }

    const cart = await Cart.findOne({ userId: user._id, status: 'active' })
    if (!cart) {
      return res.status(404).json({ error: 'cart not found' })
    }

    const cartItem = await CartItem.findOne({ cartId: cart._id, itemId })
    if (!cartItem) {
      return res.status(404).json({ error: 'item not in cart' })
    }

    await CartItem.deleteOne({ cartId: cart._id, itemId })

    res.json({ itemId })
  } catch (error) {
    res.status(500).json({ error: 'Failed to remove item' })
  }
}

export const getCarts = async (req, res) => {
  try {
    const carts = await Cart.find().populate('userId', 'username')
    res.json(carts)
  } catch (error) {
    res.status(500).json({ error: 'Failed to get carts' })
  }
}

