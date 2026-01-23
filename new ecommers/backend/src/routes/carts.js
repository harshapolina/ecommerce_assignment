import { Router } from 'express'
import { addToCart, getMyCart, getCarts, updateCartItemQuantity, removeFromCart } from '../controllers/cartController.js'
import authenticate from '../middleware/auth.js'

const router = Router()

router.post('/', authenticate, addToCart)
router.get('/my-cart', authenticate, getMyCart)
router.put('/:itemId', authenticate, updateCartItemQuantity)
router.delete('/:itemId', authenticate, removeFromCart)
router.get('/', getCarts)

export default router

