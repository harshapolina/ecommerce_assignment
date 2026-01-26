import { Router } from 'express'
import { createUser, getUsers, login, logout } from '../controllers/userController.js'
import authenticate from '../middleware/auth.js'

const router = Router()

router.post('/', createUser)
router.get('/', getUsers)
router.post('/login', login)
router.post('/logout', authenticate, logout)

export default router

