import Item from '../models/Item.js'

export const createItem = async (req, res) => {
  try {
    const { name, status = 'available' } = req.body

    if (!name) {
      return res.status(400).json({ error: 'name required' })
    }

    const item = new Item({
      name,
      status
    })
    await item.save()

    res.status(201).json({
      id: item._id,
      name: item.name,
      status: item.status,
      createdAt: item.createdAt
    })
  } catch (error) {
    res.status(500).json({ error: 'Failed to create item' })
  }
}

export const updateItem = async (req, res) => {
  try {
    const itemId = req.params.id
    const updates = req.body

    const item = await Item.findById(itemId)
    if (!item) {
      return res.status(404).json({ error: 'item not found' })
    }

    if (updates.name) {
      item.name = updates.name
    }

    if (updates.status) {
      item.status = updates.status
    }

    await item.save()

    res.json(item)
  } catch (err) {
    res.status(500).json({ error: 'Failed to update item' })
  }
}

export const getItems = async (req, res) => {
  try {
    const { cursor, limit = 20 } = req.query
    const query = { status: 'available' }
    if (cursor) {
      query._id = { $gt: cursor }
    }
    const limitValue = Number(limit) || 20

    const items = await Item.find(query)
      .limit(limitValue)
      .sort({ _id: 1 })
    
    const itemsWithCategory = items.map(item => {
      const name = item.name.toLowerCase()
      let category = 'Other'
      if (name.includes('snake') || name.includes('monstera') || name.includes('peace') || name.includes('pothos') || name.includes('zz') || name.includes('aloe')) {
        category = 'Indoor Plants'
      } else if (name.includes('lavender') || name.includes('rose') || name.includes('sunflower')) {
        category = 'Outdoor Plants'
      } else if (name.includes('rosemary') || name.includes('basil') || name.includes('mint') || name.includes('herb')) {
        category = 'Herbal Plants'
      }
      return {
        ...item.toObject(),
        category
      }
    })
    
    res.json(itemsWithCategory)
  } catch (error) {
    res.status(500).json({ error: 'Failed to get items' })
  }
}

export const deleteItem = async (req, res) => {
  try {
    const { id } = req.params

    const item = await Item.findByIdAndDelete(id)
    if (!item) {
      return res.status(404).json({ error: 'item not found' })
    }

    res.json({ message: 'item deleted', id: item._id })
  } catch (error) {
    res.status(500).json({ error: 'Failed to delete item' })
  }
}

export const deleteAllItems = async (req, res) => {
  try {
    const deleteResult = await Item.deleteMany({})
    const deletedCount = deleteResult.deletedCount || 0
    res.json({
      message: `Deleted ${deletedCount} items`
    })
  } catch (err) {
    res.status(500).json({ error: 'Could not delete items' })
  }
}
