import Item from '../models/Item.js'

export const createItem = async (req, res) => {
  try {
    const { name, category, image, price, status = 'active' } = req.body

    if (!name) {
      return res.status(400).json({ error: 'Item name required' })
    }

    const item = new Item({ 
      name, 
      category: category || 'Indoor Plants',
      image: image || '',
      price: price || 25.00,
      status 
    })
    await item.save()

    res.status(201).json({ 
      id: item._id, 
      name: item.name, 
      category: item.category,
      image: item.image,
      price: item.price,
      status: item.status, 
      createdAt: item.createdAt 
    })
  } catch (error) {
    res.status(500).json({ error: 'Failed to create item' })
  }
}

export const updateItem = async (req, res) => {
  try {
    const { id } = req.params
    const { name, category, image, price, status } = req.body

    const item = await Item.findById(id)
    if (!item) {
      return res.status(404).json({ error: 'Item not found' })
    }

    if (name) item.name = name
    if (category) item.category = category
    if (image !== undefined) item.image = image || ''
    if (price !== undefined) item.price = price
    if (status) item.status = status

    await item.save()

    res.json({ 
      id: item._id, 
      _id: item._id,
      name: item.name, 
      category: item.category,
      image: item.image,
      price: item.price,
      status: item.status, 
      createdAt: item.createdAt 
    })
  } catch (error) {
    res.status(500).json({ error: 'Failed to update item' })
  }
}

export const getItems = async (req, res) => {
  try {
    const { cursor, limit = 20 } = req.query
    const query = cursor ? { _id: { $gt: cursor } } : {}

    const items = await Item.find(query)
      .limit(parseInt(limit))
      .sort({ _id: 1 })

    res.json(items)
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch items' })
  }
}

export const deleteItem = async (req, res) => {
  try {
    const { id } = req.params

    const item = await Item.findByIdAndDelete(id)
    if (!item) {
      return res.status(404).json({ error: 'Item not found' })
    }

    res.json({ message: 'Item deleted', id: item._id })
  } catch (error) {
    res.status(500).json({ error: 'Failed to delete item' })
  }
}

export const deleteAllItems = async (req, res) => {
  try {
    const result = await Item.deleteMany({})
    res.json({ message: `Deleted ${result.deletedCount} items` })
  } catch (error) {
    res.status(500).json({ error: 'Failed to delete items' })
  }
}

