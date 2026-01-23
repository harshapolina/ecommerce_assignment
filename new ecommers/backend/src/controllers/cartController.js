import Cart from '../models/Cart.js'
import CartItem from '../models/CartItem.js'
import Item from '../models/Item.js'

export const addToCart = async (req, res) => {
  try {
    const { itemId } = req.body

    if (!itemId) {
      return res.status(400).json({ error: 'Item ID required' })
    }

    if (!req.user || !req.user._id) {
      return res.status(401).json({ error: 'Authentication required' })
    }

    const item = await Item.findById(itemId)
    if (!item) {
      return res.status(404).json({ error: 'Item not found' })
    }

    let cart = await Cart.findOne({ userId: req.user._id, status: 'active' })

    if (!cart) {
      try {
        cart = new Cart({ userId: req.user._id, status: 'active' })
        await cart.save()
      } catch (createError) {
        if (createError.code === 11000) {
          cart = await Cart.findOne({ userId: req.user._id })
          if (cart) {
            cart.status = 'active'
            await cart.save()
          } else {
            throw createError
          }
        } else {
          throw createError
        }
      }
    }

    const existingCartItem = await CartItem.findOne({ cartId: cart._id, itemId })
    if (existingCartItem) {
      existingCartItem.quantity += 1
      await existingCartItem.save()
      return res.status(200).json({ cartId: cart._id, itemId, quantity: existingCartItem.quantity })
    }

    const cartItem = new CartItem({ cartId: cart._id, itemId, quantity: 1 })
    await cartItem.save()

    res.status(201).json({ cartId: cart._id, itemId, quantity: 1 })
  } catch (error) {
    if (error.code === 11000) {
      try {
        let cart = await Cart.findOne({ userId: req.user._id, status: 'active' })
        if (!cart) {
          return res.status(500).json({ error: 'Failed to add item to cart' })
        }
        const existingCartItem = await CartItem.findOne({ cartId: cart._id, itemId: req.body.itemId })
        if (existingCartItem) {
          existingCartItem.quantity += 1
          await existingCartItem.save()
          return res.status(200).json({ cartId: cart._id, itemId: req.body.itemId, quantity: existingCartItem.quantity })
        }
      } catch (retryError) {
      }
    }
    res.status(500).json({ error: 'Failed to add item to cart' })
  }
}

export const updateCartItemQuantity = async (req, res) => {
  try {
    const { itemId } = req.params
    const { quantity } = req.body

    if (!itemId || quantity === undefined) {
      return res.status(400).json({ error: 'Item ID and quantity required' })
    }

    if (quantity < 1) {
      return res.status(400).json({ error: 'Quantity must be at least 1' })
    }

    const cart = await Cart.findOne({ userId: req.user._id, status: 'active' })
    if (!cart) {
      return res.status(404).json({ error: 'Cart not found' })
    }

    const cartItem = await CartItem.findOne({ cartId: cart._id, itemId })
    if (!cartItem) {
      return res.status(404).json({ error: 'Item not found in cart' })
    }

    cartItem.quantity = quantity
    await cartItem.save()

    res.json({ cartId: cart._id, itemId, quantity: cartItem.quantity })
  } catch (error) {
    res.status(500).json({ error: 'Failed to update cart item' })
  }
}

export const removeFromCart = async (req, res) => {
  try {
    const { itemId } = req.params

    const cart = await Cart.findOne({ userId: req.user._id, status: 'active' })
    if (!cart) {
      return res.status(404).json({ error: 'Cart not found' })
    }

    const cartItem = await CartItem.findOneAndDelete({ cartId: cart._id, itemId })
    if (!cartItem) {
      return res.status(404).json({ error: 'Item not found in cart' })
    }

    res.json({ message: 'Item removed from cart' })
  } catch (error) {
    res.status(500).json({ error: 'Failed to remove item from cart' })
  }
}

export const getMyCart = async (req, res) => {
  try {
    const cart = await Cart.findOne({ userId: req.user._id, status: 'active' })
    
    if (!cart) {
      return res.json([])
    }

    const cartItems = await CartItem.find({ cartId: cart._id }).populate('itemId', 'name status image price')
    res.json(cartItems
      .filter(item => item.itemId)
      .map(item => ({ 
        _id: item._id,
        cartId: item.cartId, 
        itemId: item.itemId._id || item.itemId,
        itemName: item.itemId.name || 'Unknown Item',
        itemImage: item.itemId.image || '',
        itemPrice: item.itemId.price || 25.00,
        quantity: item.quantity || 1
      })))
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch cart items' })
  }
}

export const getCarts = async (req, res) => {
  try {
    const { cursor, limit = 20 } = req.query
    const query = cursor ? { _id: { $gt: cursor } } : {}

    const carts = await Cart.find(query)
      .limit(parseInt(limit))
      .sort({ _id: 1 })
      .populate('userId', 'username')

    res.json(carts)
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch carts' })
  }
}

