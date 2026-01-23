import mongoose from 'mongoose'

const itemSchema = new mongoose.Schema({
  name: {
    type: String,
    required: true,
    trim: true
  },
  category: {
    type: String,
    default: 'Indoor Plants'
  },
  image: {
    type: String,
    default: ''
  },
  price: {
    type: Number,
    default: 25.00
  },
  status: {
    type: String,
    default: 'active'
  },
  createdAt: {
    type: Date,
    default: Date.now
  }
})

export default mongoose.model('Item', itemSchema)

