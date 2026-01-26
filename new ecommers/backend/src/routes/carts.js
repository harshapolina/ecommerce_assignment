import express from 'express'
import { addToCart, getMyCart, getCarts, removeFromCart } from '../controllers/cartController.js'
import authenticate from '../middleware/auth.js'

const router = express.Router()

router.post('/', authenticate, addToCart)
router.get('/my-cart', authenticate, getMyCart)
router.delete('/:itemId', authenticate, removeFromCart)
router.get('/', getCarts)

export default router

