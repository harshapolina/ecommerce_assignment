import { Router } from 'express'
import { createUser, getUsers, login } from '../controllers/userController.js'

const router = Router()

router.post('/', createUser)
router.get('/', getUsers)
router.post('/login', login)

export default router

