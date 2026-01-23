import { Router } from 'express'
import { createItem, getItems, updateItem, deleteItem, deleteAllItems } from '../controllers/itemController.js'

const router = Router()

router.post('/', createItem)
router.get('/', getItems)
router.put('/:id', updateItem)
router.delete('/:id', deleteItem)
router.delete('/', deleteAllItems)

export default router

