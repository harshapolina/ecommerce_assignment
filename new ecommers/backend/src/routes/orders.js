import { Router } from 'express'
import { createOrder, getOrders, getAllOrders } from '../controllers/orderController.js'
import authenticate from '../middleware/auth.js'

const router = Router()

router.post('/', authenticate, createOrder)
router.get('/', authenticate, getOrders)
router.get('/all', authenticate, getAllOrders)

export default router

